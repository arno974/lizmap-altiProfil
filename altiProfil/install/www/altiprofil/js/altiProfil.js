/**
 * Entry point: fired when the Lizmap UI is created.
 */
lizMap.events.on({
    'uicreated': function() {
        const profilStop = document.getElementById('profil-stop');
        if (profilStop) {
            profilStop.addEventListener('click', function () {
                const btn = document.getElementById('button-altiProfil');
                if (btn) { btn.click(); }
            });
        }
        initAltiProfil();
    }
});

/** Accent colour shared by the A/B pins (panel and map) and the lowest point. */
const ALTI_ACCENT = '#43718c';
/** Colour of the highest point (chart marker and stat bullet). */
const ALTI_HIGH = '#b5532d';
/** Hypsometric tints, from the lowest band to the highest one. */
const ALTI_TINTS = ['#c5d6a8', '#dcdba6', '#e9d3a2', '#dcb58a', '#c79c78', '#ad9585', '#cfc8c1'];

/** Show an element: display='' removes the inline style */
function showElement(selector) {
    const el = document.querySelector(selector);
    if (el) { el.style.display = ''; }
}

/** Hide an element */
function hideElement(selector) {
    const el = document.querySelector(selector);
    if (el) { el.style.display = 'none'; }
}

/** Set the text of an element (plain text, never HTML). */
function setText(selector, text) {
    const el = document.querySelector(selector);
    if (el) { el.textContent = text; }
}

/**
 * Native JSON GET - replacement for $.get(url, params, cb, 'json').fail(onError).
 */
function fetchJson(url, params, onSuccess, onError) {
    const query = new URLSearchParams(params).toString();
    fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + query, {
        /* 
        * ajaxCtrl::checkParams() (PHP) appelle jRequest::isAjax(), 
        * qui teste l'en-tête "X-Requested-With: XMLHttpRequest". 
        * jQuery l'envoie automatiquement ; fetch() NON. 
        * Sans lui, getAlti/getProfil répondent "Wrong lon/lat values" (branche
        * ========= NE PAS RETIRER =========
        */
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (response) {
        if (!response.ok) { throw new Error('HTTP ' + response.status); }
        return response.json();
    })
    .then(function (data) {
        if (onSuccess) { onSuccess(data); }
    })
    .catch(function (err) {
        console.error('altiProfil: request failed', err);
        if (onError) { onError(); }
    });
}

/** Lizmap locale as a BCP 47 tag ("fr_FR" -> "fr-FR"), "en-US" if unknown. */
function altiLocale() {
    return (typeof ALTI_LOCALE !== 'undefined' && ALTI_LOCALE) ? ALTI_LOCALE.replace('_', '-') : 'en-US';
}

/** "2 426 m" in French, "2,426 m" in English. */
function formatMeters(value) {
    return new Intl.NumberFormat(altiLocale(), { maximumFractionDigits: 0 }).format(value) + ' m';
}

/** Distance in meters below 2 km, in kilometers (1 decimal) above. */
function formatDistance(meters) {
    if (meters < 2000) {
        return formatMeters(meters);
    }
    return new Intl.NumberFormat(altiLocale(), { minimumFractionDigits: 1, maximumFractionDigits: 1 })
        .format(meters / 1000) + ' km';
}

/** WGS 84 latitude / longitude, 5 decimals (about 1 m): "-21,01234 / 55,27123". */
function formatLatLon(lonLat) {
    const nf = new Intl.NumberFormat(altiLocale(), { minimumFractionDigits: 5, maximumFractionDigits: 5, useGrouping: false });
    return nf.format(lonLat[1]) + ' / ' + nf.format(lonLat[0]);
}

/** Plotly "separators" (decimal + thousands) matching the Lizmap locale. */
function plotlySeparators() {
    const parts = new Intl.NumberFormat(altiLocale()).formatToParts(12345.6);
    const decimal = parts.find(p => p.type === 'decimal');
    const group = parts.find(p => p.type === 'group');
    return (decimal ? decimal.value : '.') + (group ? group.value : ',');
}

/**
 * Panel state:
 *   0 - nothing placed: help "place A", no summary, no chart
 *   1 - A placed: help "place B", A elevation shown
 *   2 - A and B placed: help "click again", distance + chart
 */
function setPanelStep(step) {
    const help = [LOCALES_ALTI_HELP_START, LOCALES_ALTI_HELP_END, LOCALES_ALTI_HELP_AGAIN];
    setText('#altiProfil-help', help[step]);
    setText('#altiProfil-error', '');
    if (step === 0) {
        hideElement('#altiProfil-ends');
        hideElement('#altiProfil-chart');
        setText('#alt-p1', '');
        setText('#alt-p2', '');
        setText('#altiProfil-coord-p1', '');
        setText('#altiProfil-coord-p2', '');
        setText('#altiProfil-distance', '');
        clearChart();
    } else if (step === 1) {
        showElement('#altiProfil-ends');
        setText('#alt-p1', '…');
        setText('#alt-p2', '…');
        setText('#altiProfil-coord-p2', '');
        setText('#altiProfil-distance', '');
    } else {
        showElement('#altiProfil-chart');
        showElement('#altiProfil-chart .spinner');
        hideElement('#altiProfil-result');
    }
}

/** Remove the chart (and its plotly_hover handlers), the stats and the source. */
function clearChart() {
    const container = document.getElementById('altiProfil-chart-container');
    if (container && window.Plotly) { Plotly.purge(container); }
    const stats = document.getElementById('altiProfil-stats');
    if (stats) { stats.innerHTML = ''; }
    setText('#altiProfil-source', '');
    setText('#altiProfil-slope', '');
}

function setAltiError(text) {
    setText('#altiProfil-error', text);
    hideElement('#altiProfil-chart');
}

function projTransform(feature, fromProjCode, toProjCode) {
    return feature.clone().transform(fromProjCode, toProjCode);
}

/**
 * Elevation of a clicked point: reproject to 4326 if needed, query the backend,
 * write "<alt> m" (or '-' on error / off-coverage) into the #alt-p{numFeat} span.
 */
function getAlti(feature, numFeat){
    //IGN Web Service only allows coordinates in 4326
    const pProj = projTransform(feature.getGeometry(), lizMap.map.projection.projCode, 'EPSG:4326');

    const qParams = {
        'lon': pProj.getCoordinates()[0],
        'lat': pProj.getCoordinates()[1],
        'srs': lizMap.map.projection.projCode,
        'repository': lizUrls.params.repository,
        'project': lizUrls.params.project
    }

    setText('#altiProfil-coord-p' + numFeat, formatLatLon(pProj.getCoordinates()));

    const showAlti = function (text) {
        setText('#alt-p' + numFeat, text);
    };

    fetchJson(
        URLAJAXALTICOORD,
        qParams, 
        function(data){            
            if (!data || data['error msg'] || !data['elevations'] || !data['elevations'][0]
                || data['elevations'][0]['z'] === null) {
                showAlti('-');
                return;
            }
            showAlti(formatMeters(Number(data['elevations'][0]['z'])));
        },
        function(){
            // Network error (403 / 5xx)
            showAlti('-');
        }
    );
}

/** Force width to 100% and hand the resize over to Plotly. */
function resizePlot(id){
    const el = document.getElementById(id);
    if (!el) { return; }
    el.style.width = '100%';
    el.style.margin = '0px';
    Plotly.Plots.resize(el);
}

/** HTML escaping (defense in depth for the labels written in the stats). */
function escapeHtml(s){
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

/**
 * Upper bounds of the hypsometric bands: the "nice" step (1, 2, 2.5 or 5 x 10^n)
 * giving the number of bands closest to 5 between vMin and vMax (rounding up
 * instead could leave only 2 or 3 tints). The last bound is Infinity so the
 * top band always reaches the profile line.
 */
function hypsometricBounds(vMin, vMax) {
    const span = Math.max(vMax - vMin, 1);
    const raw = span / 5;
    const mag = Math.pow(10, Math.floor(Math.log10(raw)));
    const step = [1, 2, 2.5, 5, 10].map(m => m * mag)
        .reduce((best, s) => Math.abs(span / s - 5) < Math.abs(span / best - 5) ? s : best);
    const bounds = [];
    for (let b = Math.floor(vMin / step) * step + step; b < vMax; b += step) {
        bounds.push(b);
    }
    bounds.push(Infinity);
    return bounds;
}

/** Fill the 4 figures under the chart. */
function renderStats(vMax, vMin, dPlus, dMinus) {
    const stats = document.getElementById('altiProfil-stats');
    if (!stats) { return; }
    const cell = (cls, label, value) =>
        `<div class="${cls}"><dt>${escapeHtml(label)}</dt><dd>${value}</dd></div>`;
    stats.innerHTML =
        cell('is-high', LOCALES_ALTI_HIGH, formatMeters(vMax)) +
        cell('is-low', LOCALES_ALTI_LOW, formatMeters(vMin)) +
        cell('', LOCALES_ALTI_GAIN, '+' + formatMeters(dPlus)) +
        cell('', LOCALES_ALTI_LOSS, '−' + formatMeters(dMinus));
}

/**
 * Calls the profile endpoint. On success: compute D+/D- and min/max in one
 * pass -> summary and stats -> hypsometric traces -> Plotly.newPlot ->
 * chart<->map hover interactions.
 *
 * @param {number} distance geodesic length of the segment, in meters
 */
function getProfil(p1, p2, distance){
    const p1clone = projTransform(p1, lizMap.map.projection.projCode, 'EPSG:4326');
    const p2clone = projTransform(p2, lizMap.map.projection.projCode, 'EPSG:4326');

    const roundedDistance = Math.round(distance);
    const sampling = Math.max(1, Math.round(roundedDistance <= 100 ? roundedDistance - 2 : (roundedDistance <= 500 ? roundedDistance / 5 : roundedDistance / 25)));

    const qParams = {
        'p1Lon': p1clone.getCoordinates()[0],
        'p1Lat': p1clone.getCoordinates()[1],
        'p2Lon': p2clone.getCoordinates()[0],
        'p2Lat': p2clone.getCoordinates()[1],
        'srs': lizMap.map.projection.projCode,
        'repository': lizUrls.params.repository,
        'project': lizUrls.params.project,
        'sampling' : sampling /* Only use with french mapping Agency (IGN) web service  */,
        'distance' : roundedDistance
    }

    fetchJson(
        URLAJAXALTIPROFIL,
        qParams,
        function(data){ //ON SUCCESS
            hideElement('#altiProfil-chart .spinner');
            if (!data || data['error msg'] || !Array.isArray(data) || !data[0]) {
                setAltiError(LOCALES_ALTI_ERROR_REQUEST);
                return;
            }

            const _x = data[0]['x'];
            const _y = data[0]['y'];
            const _customdata = data[0]['customdata'];
            const _srs = data[0]['srid'];
            const _altisource = data[0]['altisource'];

            // Cumulative elevation gain (D+) and loss (D-) between consecutive
            // valid samples. A null gap breaks the chain: no delta is computed
            // across missing data. Totals depend on the sampling resolution
            // (finer steps catch more micro-relief and increase both values).
            let dPlus = 0, dMinus = 0, prevY = null;
            let iMin = -1, iMax = -1, vMin = Infinity, vMax = -Infinity;
            for (let k = 0; k < _y.length; k++) {
                // Plotly's hovertemplate warns on null: show a dash instead
                if (_customdata[k] && _customdata[k][0] && _customdata[k][0].slope == null) {
                    _customdata[k][0].slope = '-';
                }
                if (_y[k] === null) { prevY = null; continue; }
                const v = Number(_y[k]);
                if (isNaN(v)) { prevY = null; continue; }
                if (prevY !== null) {
                    const d = v - prevY;
                    if (d > 0) { dPlus += d; } else { dMinus -= d; }
                }
                prevY = v;
                if (v > vMax) { vMax = v; iMax = k; }
                if (v < vMin) { vMin = v; iMin = k; }
            }

            if (iMax === -1) {
              setAltiError(LOCALES_ALTI_ERROR_PROFIL);
              return;
            }

            // Summary (distance of the x axis, so both always match) and figures
            const total = _x[_x.length - 1];
            setText('#altiProfil-distance', formatDistance(total));
            renderStats(vMax, vMin, dPlus, dMinus);
            showElement('#altiProfil-result');

            let sourceText = `${LOCALES_ALTI_DATASOURCE} : ${_altisource}`;
            if (ALTI_PROVIDER == "database") {
                const _slope = data[0]['slope'];
                sourceText += ` · ${LOCALES_ALTI_RESOLUTION} ${data[0]['resolution']} m`;
                if (_slope) {
                    setText('#altiProfil-slope',
                        `${LOCALES_ALTI_SLOPE} ${LOCALES_ALTI_UNIT} : ${LOCALES_ALTI_MEAN} ${_slope.mean_slope} · max ${_slope.max_slope}`);
                }
            }
            setText('#altiProfil-source', sourceText);

            // x axis in km above 2 km
            const useKm = total >= 2000;
            const xs = useKm ? _x.map(v => v / 1000) : _x;
            const unit = useKm ? 'km' : 'm';

            // y axis fixed on [min, max] (+ margins): autorange would stretch it
            // to 0 on high-altitude profiles.
            const span = Math.max(vMax - vMin, 10);
            const yBase = vMin - span * 0.06;

            // Hypsometric tints: one trace per altitude band, each clipped to its
            // upper bound and filled down to the previous one ('tonexty'). The
            // first trace is an invisible baseline under the lowest point.
            const traces = [{
                x: [xs[0], xs[xs.length - 1]],
                y: [yBase, yBase],
                mode: 'lines',
                line: { width: 0 },
                hoverinfo: 'skip'
            }];
            hypsometricBounds(vMin, vMax).forEach(function (bound, k) {
                traces.push({
                    x: xs,
                    y: _y.map(v => v === null ? null : Math.min(v, bound)),
                    mode: 'lines',
                    line: { width: 0 },
                    fill: 'tonexty',
                    fillcolor: ALTI_TINTS[Math.min(k, ALTI_TINTS.length - 1)],
                    hoverinfo: 'skip'
                });
            });

            const slopeHover = (ALTI_PROVIDER == "database")
                ? `<br>${LOCALES_ALTI_SLOPE} : %{customdata[0].slope}${LOCALES_ALTI_UNIT_ABRV}` : '';
            traces.push({
                x: xs,
                y: _y,
                customdata: _customdata,
                mode: 'lines',
                line: { color: '#4a3b2c', width: 1.6 },
                hovertemplate: `<b>%{y:,.0f} m</b>${slopeHover}<extra></extra>`
            });

            // Highest and lowest points; cliponaxis: false keeps a marker sitting
            // on the chart edge (e.g. the lowest point on A) fully visible.
            traces.push({
                x: [xs[iMax]], y: [vMax], mode: 'markers', cliponaxis: false, hoverinfo: 'skip',
                marker: { size: 11, color: ALTI_HIGH, line: { color: '#fff', width: 1.5 } }
            });
            traces.push({
                x: [xs[iMin]], y: [vMin], mode: 'markers', cliponaxis: false, hoverinfo: 'skip',
                marker: { size: 11, color: ALTI_ACCENT, line: { color: '#fff', width: 1.5 } }
            });

            const layout = {
                height: 210,
                // l and r are mirrored in altiProfil.css (.ap-ends) so the A/B
                // pins sit above the start and the end of the profile.
                margin: { l: 46, r: 10, t: 8, b: 26 },
                showlegend: false,
                font: { size: 10, color: '#6b7c88' },
                separators: plotlySeparators(),
                paper_bgcolor: '#fff',
                plot_bgcolor: '#fff',
                hovermode: 'x',
                hoverlabel: { bgcolor: '#2b3a45', bordercolor: '#2b3a45', font: { color: '#fff', size: 11 } },
                xaxis: {
                    range: [0, xs[xs.length - 1]],
                    ticksuffix: ' ' + unit,
                    hoverformat: useKm ? ',.1f' : ',.0f',
                    showgrid: false,
                    zeroline: false,
                    showline: true,
                    linecolor: '#b8c4cc',
                    ticks: 'outside',
                    ticklen: 3,
                    tickcolor: '#b8c4cc',
                    nticks: 6,
                    showspikes: true,
                    spikemode: 'across',
                    spikethickness: 1,
                    spikecolor: '#2b3a45',
                    spikedash: 'dot',
                    spikesnap: 'cursor'
                },
                yaxis: {
                    range: [yBase, vMax + span * 0.08],
                    ticksuffix: ' m',
                    tickformat: ',.0f',
                    gridcolor: '#eef2f5',
                    zeroline: false,
                    nticks: 5
                }
            };

            const config = {
                displaylogo: false,
                responsive: true,
                locale: altiLocale().substr(0, 2).toLowerCase(),
                toImageButtonOptions: {
                    format: 'png',
                    filename: 'profil',
                    height: 420,
                    width: 720,
                    scale: 2
                },
                modeBarButtonsToRemove: ['zoom2d', 'pan2d','select2d','lasso2d','resetScale2d', 'zoomIn2d', 'zoomOut2d', 'autoScale2d',
                    'hoverClosestGl2d', 'hoverClosestPie', 'toggleHover', 'resetViews',
                    'sendDataToCloud', 'toggleSpikelines', 'resetViewMapbox', 'hoverClosestCartesian', 'hoverCompareCartesian']
            };

            Plotly.purge('altiProfil-chart-container'); //remove handlers plotly_hover/plotly_unhover

            Plotly.newPlot('altiProfil-chart-container', traces, layout, config)
            .then(function () {
                resizePlot('altiProfil-chart-container');
            });

            const myPlot = document.getElementById('altiProfil-chart-container');

            //Show the hovered sample on the map
            myPlot.on('plotly_hover', function(plotData){
                const p = plotData.points[0].customdata && plotData.points[0].customdata[0];
                if (!p) { return; }
                const source = altiProfilSource();
                if (!source) { return; }
                const pCoord = new lizMap.ol.geom.Point([p.lon, p.lat]);
                pCoord.transform('EPSG:'+_srs, lizMap.map.projection.projCode);
                let hover = source.getFeatureById('altiHover');
                if (!hover) {
                    hover = new lizMap.ol.Feature({ name: 'AltiHover' });
                    hover.setId('altiHover');
                    source.addFeature(hover);
                }
                hover.setGeometry(pCoord);
            });

            //Remove it when leaving the chart
            myPlot.on('plotly_unhover', function(){
                const source = altiProfilSource();
                const hover = source && source.getFeatureById('altiHover');
                if (hover) { source.removeFeature(hover); }
            });
        },
        function(){ //ON ERROR
            // Network error (403 / 5xx)
            hideElement('#altiProfil-chart .spinner');
            setAltiError(LOCALES_ALTI_ERROR_REQUEST);
        }
    );
}

/** Source of the altiProfil tool layer (the layer carrying 'altiprofil': true). */
function altiProfilSource() {
    const layer = lizMap.mainLizmap.map.getAllLayers().find(l => l.get('altiprofil') == true);
    return layer ? layer.getSource() : null;
}

/**
 * Initializes the marker vector layer (profile points/line) and wires all the
 * dock events (open/close for the 3 types) and the map click.
 */
function initAltiProfil() {
    //minimum distance between two points to compute a profile (in meters)
    const MIN_PROFILE_LENGTH = 1;

    // Map styles: A/B pins matching the panel, line with a white halo,
    // small ring for the sample hovered on the chart.
    const ol = lizMap.ol;
    const pinStyle = function (label) {
        return new ol.style.Style({
            zIndex: 2,
            image: new ol.style.Circle({
                radius: 10,
                fill: new ol.style.Fill({ color: ALTI_ACCENT }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            }),
            text: new ol.style.Text({
                text: label,
                font: 'bold 11px sans-serif',
                fill: new ol.style.Fill({ color: '#fff' })
            })
        });
    };
    const styles = {
        AltiPoint1: pinStyle('1'),
        AltiPoint2: pinStyle('2'),
        AltiLine: [
            new ol.style.Style({ zIndex: 1, stroke: new ol.style.Stroke({ color: '#fff', width: 6 }) }),
            new ol.style.Style({ zIndex: 1, stroke: new ol.style.Stroke({ color: ALTI_ACCENT, width: 3 }) })
        ],
        AltiHover: new ol.style.Style({
            zIndex: 3,
            image: new ol.style.Circle({
                radius: 6,
                fill: new ol.style.Fill({ color: '#fff' }),
                stroke: new ol.style.Stroke({ color: '#2b3a45', width: 2 })
            })
        })
    };

    const altiProfilSource = new ol.source.Vector();
    const altiProfilLayer = new ol.layer.Vector({
        style: feature => styles[feature.get('name')],
        source: altiProfilSource,
        projection : lizMap.map.projection,
        properties : {"altiprofil" : true},
        visible : false
    });
    lizMap.mainLizmap.map.addToolLayer(altiProfilLayer);

    setPanelStep(0);

    // Dock opened: disable the Lizmap popup and show the layer.
    function onAltiDockOpened() {
        // disable popup
        if (lizMap?.mainLizmap?.popup) {
            lizMap.mainLizmap.popup.active = false;
        }
        altiProfilLayer.setVisible(true);
    }

    // Dock closed: re-enable the popup, clear the panel and the layer.
    function onAltiDockClosed() {
        // enable popup
        if (lizMap?.mainLizmap?.popup) {
            lizMap.mainLizmap.popup.active = true;
        }
        altiProfilSource.clear();
        setPanelStep(0);
        altiProfilLayer.setVisible(false);
    }

    lizMap.events.on({
        // Dock opened
        dockopened: function(e) {
            if ( e.id == 'altiProfil' ) {
                onAltiDockOpened();
            }
        },
        minidockopened: function(e) {
            if ( e.id == 'altiProfil' ) {
                onAltiDockOpened();
            }
        },
        rightdockopened: function(e) {
            if ( e.id == 'altiProfil' ) {
                onAltiDockOpened();
            }
        },
        // Dock closed
        dockclosed: function(e) {
            if ( e.id == 'altiProfil' ) {
                onAltiDockClosed();
            }
        },
        minidockclosed: function(e) {
            if ( e.id == 'altiProfil' ) {
                onAltiDockClosed();
            }
        },
        rightdockclosed: function(e) {
            if ( e.id == 'altiProfil' ) {
                onAltiDockClosed();
            }
        }
    });

    // Only the A and B pins (the hovered sample is also a Point)
    function getPoints() {
        return altiProfilSource.getFeatures()
            .filter(f => String(f.get('name')).startsWith('AltiPoint'));
    }

    // Geodesic length in meters (a projected length, e.g. in EPSG:3857, is
    // stretched by 1/cos(latitude)).
    function segmentLength(c1, c2) {
        return ol.sphere.getLength(new ol.geom.LineString([c1, c2]), {projection: lizMap.map.projection.projCode});
    }

    function addPoint(coord, pointNumber) {
        const feature = new ol.Feature({
            geometry: new ol.geom.Point(coord),
            name: 'AltiPoint' + pointNumber,
        });
        altiProfilSource.addFeature(feature);
        getAlti(feature, pointNumber);
        return feature;
    }

    function drawLine(p1, p2) {
        altiProfilSource.addFeature(new ol.Feature({
            geometry: new ol.geom.LineString([p1, p2]),
            name: 'AltiLine',
        }));
    }

    lizMap.mainLizmap.map.on('singleclick', evt => {
        if (!altiProfilLayer.getVisible()) { return; }

        const points = getPoints();

        switch (points.length) {
            case 0: // first point add it
                setPanelStep(1);
                addPoint(evt.coordinate, 1);
                break;

            case 1: { // second point add it and compute the profile
                const firstCoord = points[0].getGeometry().getCoordinates();
                const length = segmentLength(firstCoord, evt.coordinate);
                if (length < MIN_PROFILE_LENGTH) {
                    return;
                }
                setPanelStep(2);
                const second = addPoint(evt.coordinate, 2);
                drawLine(firstCoord, evt.coordinate);
                getProfil(points[0].getGeometry(), second.getGeometry(), length);
                break;
            }

            // Two points, reset all and start a new point
            default:
                altiProfilSource.clear();
                setPanelStep(0);
                setPanelStep(1);
                addPoint(evt.coordinate, 1);
                break;
        }
    });
}