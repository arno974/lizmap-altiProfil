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
        hideElement('#altiProfil .menu-content #altiProfil-chart');
        initAltiProfil();
    }
});

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

/**
 * Native JSON GET — replacement for $.get(url, params, cb, 'json').fail(onError).
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
    .catch(function () {        
        if (onError) { 
            console.error('altiProfil: request failed', err)
            onError(); 
        }
    });
}

/**
 * Write text into ALL the dock's <span> elements.
 * Reproduces $('#altiProfil .menu-content span').html(text): jQuery wrote to the
 * whole selection (including #alt-p1 / #alt-p2), which is how the "..." reset
 * also clears the displayed elevations. textContent: plain text content.
 */
function setTextSpans(text) {
    document.querySelectorAll('#altiProfil .menu-content p#altiProfil-coords span').forEach(function (el) {
        el.textContent = text;
    });
}

function setAltiError(text) {
    const el = document.querySelector('#altiProfil .menu-content span#altiProfil-error');
    if (!el) { return; }
    el.textContent = text;
    hideElement('#altiProfil .menu-content #altiProfil-chart .spinner');
    hideElement('#altiProfil .menu-content #altiProfil-chart');
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

    const showAlti = function (text) {
        const target = document.getElementById('alt-p' + numFeat);
        if (target) { target.textContent = text; }
    };

    fetchJson(
        URLAJAXALTICOORD,
        qParams, 
        function(data){            
            if (!data || data['error msg'] || !data['elevations'] || !data['elevations'][0]) {
                showAlti('-');
                return;
            }
            showAlti(`${Number(data['elevations'][0]['z']).toFixed(2)} m`);
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

/** HTML escaping (defense in depth for altisource in the annotation). */
function escapeHtml(s){
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

/**
 * Calls the profile endpoint: shows the spinner, and on network/HTTP failure
 * hides it + shows a localized message (onError). The success callback receives
 * the dataset.
 * 
 * Steps: reproject to 4326 -> build request params -> call backend -> (in the
 * callback) compute D+/D- and min/max in one pass -> layout + annotations ->
 * filled trace -> Plotly.newPlot -> chart<->map hover interactions. 
 */
function getProfil(p1,p2){
    showElement('#altiProfil .menu-content #altiProfil-chart');
    showElement('#altiProfil .menu-content #altiProfil-chart .spinner');

    const p1clone = projTransform(p1, lizMap.map.projection.projCode, 'EPSG:4326');
    const p2clone = projTransform(p2, lizMap.map.projection.projCode, 'EPSG:4326');

    const p1coord = p1.getCoordinates();
    const p2coord = p2.getCoordinates();
    
    const line = new lizMap.ol.geom.LineString([p1coord, p2coord]);
    const distance = Math.round(line.getLength());
    const sampling = Math.round(distance <= 100 ? distance - 2 : (distance <= 500 ? distance / 5 : distance / 25)); 
  
    const qParams = {
        'p1Lon': p1clone.getCoordinates()[0],
        'p1Lat': p1clone.getCoordinates()[1],
        'p2Lon': p2clone.getCoordinates()[0],
        'p2Lat': p2clone.getCoordinates()[1],
        'srs': lizMap.map.projection.projCode,
        'repository': lizUrls.params.repository,
        'project': lizUrls.params.project,
        'sampling' : sampling /* Only use with french mapping Agency (IGN) web service  */,
        'distance' : distance
    }

    fetchJson(
        URLAJAXALTIPROFIL,
        qParams, 
        function(data){ //ON SUCCESS
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

            const denivText = `${LOCALES_ALTI_DPLUS} : ${Math.round(dPlus)} m | ${LOCALES_ALTI_DMINUS} : ${Math.round(dMinus)} m`;

            const layout = {
                font: { size: 10 },
                title: '<b>'+LOCALES_ALTI_PROFIL+'</b>',
                xaxis: {
                    title: {
                        text: LOCALES_ALTI_DISTANCE +' (m)',
                        font: { size: 12 }
                    }
                },
                yaxis: {
                    title: {
                        text: LOCALES_ALTI_ELEVATION,
                        font: { size: 12 }
                    }
                },
                hovermode:'closest',
                annotations: [{
                    font: {
                        size: 11
                    },
                    align:'center',
                    xref:'paper',
                    yref:'paper',
                    y: 1.16,
                    showarrow: false,
                    text: `point 1 (${Math.round(p1coord[0])}, ${Math.round(p1coord[1])}) | point 2 (${Math.round(p2coord[0])}, ${Math.round(p2coord[1])})`
                },{
                    font: {
                        size: 10
                    },
                    align:'right',
                    xref:'paper',
                    yref:'paper',
                    x: 1.02,
                    y: -0.21,
                    xanchor: 'right',
                    showarrow: false,
                    text: denivText
                },{
                    font: {
                        size: 10
                    },
                    align:'left',
                    xref:'paper',
                    yref:'paper',
                    x: -0.02,
                    y: -0.21,
                    showarrow: false,
                    text: `<i>${LOCALES_ALTI_DATASOURCE} : ${escapeHtml(_altisource)}</i>`
                }],
                showlegend: false,
                autosize: true
            };

            //add extra info if datasource from DB
            if(ALTI_PROVIDER == "database"){
                const _resolution = data[0]['resolution'];
                const _slope = data[0]['slope'];

                layout['title'] = '<b>'+LOCALES_ALTI_PROFIL+' ('+ LOCALES_ALTI_RESOLUTION +' ' +_resolution+ 'm)</b>';
                layout['annotations'].push(
                    {
                        font: {
                            size: 11
                        },
                        align:'center',
                        xref:'paper',
                        yref:'paper',
                        y: 1.10,
                        showarrow:false,
                        text: `${LOCALES_ALTI_SLOPE} ${LOCALES_ALTI_UNIT}  min :  ${_slope.min_slope} | max : ${_slope.max_slope} | ${LOCALES_ALTI_MEAN} : ${_slope.mean_slope}`
                    }
                )
            }
            
            layout.annotations.push(
                { x: _x[iMax], y: _y[iMax], text: '▲ ' + Number(_y[iMax]).toFixed(0) + ' m',
                    showarrow: true, arrowhead: 6, ax: 0, ay: -30,
                    font: { color: '#d62728', size: 11 } 
                }
            );            
            
            if (iMin !== iMax) {
                layout.annotations.push(
                    { x: _x[iMin], y: _y[iMin], text: '▼ ' + Number(_y[iMin]).toFixed(0) + ' m',
                        showarrow: true, arrowhead: 6, ax: 0, ay: 30,
                        font: { color: '#1f77b4', size: 11 } 
                    }
                );
            }

            const slopeHover = (ALTI_PROVIDER == "database") ? ` <b>Pente</b> : %{customdata[0].slope}${LOCALES_ALTI_UNIT_ABRV}`: '';
            
            const profilLine = [{
                    // Invisible baseline at the lowest elevation: with a single trace,
                    // 'tonexty' falls back to 'tozeroy' and forces the y axis to
                    // include 0, flattening high-altitude profiles.
                    x: [_x[0], _x[_x.length - 1]],
                    y: [vMin, vMin],
                    mode: 'lines',
                    line: { width: 0 },
                    hoverinfo: 'skip',
                    showlegend: false
                },{
                    x: _x,
                    y: _y,
                    customdata:_customdata,
                    fill: 'tonexty',
                    fillcolor: 'rgba(230, 204, 176, 0.7)',
                    mode: 'lines',
                    line: {
                    color: 'rgb(117, 66, 0)',
                    width: 1
                    }
                    ,hovertemplate: `<b>Altitude</b>: %{y}${slopeHover}<br /><b>lon</b> : %{customdata[0].lon:.2f} / <b>lat</b> : %{customdata[0].lat:.2f}<extra></extra>`
                }
            ];

            const plotLocale = lizMap.config.datavizLayers.locale.substr(0,2).toLowerCase();
            const config = {
                showlegend: false,
                displaylogo: false,
                responsive: true,
                locale: plotLocale,
                toImageButtonOptions: {
                format: 'jpeg', // one of png, svg, jpeg, webp
                filename: 'profil',
                height: 500,
                width: 700,
                scale: 1 // Multiply title/legend/axis/canvas sizes by this factor
                },
                modeBarButtonsToRemove: ['zoom2d', 'pan2d','select2d','lasso2d','resetScale2d', 'zoomIn2d', 'zoomOut2d', 'autoScale2d',
                    'resetScale2d', 'hoverClosestGl2d', 'hoverClosestPie', 'toggleHover', 'resetViews',
                    'sendDataToCloud', 'toggleSpikelines', 'resetViewMapbox', 'hoverClosestCartesian', 'hoverCompareCartesian']
            };
            
            Plotly.purge('altiProfil-chart-container'); //remove handlers plotly_hover/plotly_unhover
            
            Plotly.newPlot('altiProfil-chart-container', profilLine, layout, config)
            .then(function () {
                const xtitle = document.getElementsByClassName('xtitle')[0];
                if (xtitle) { xtitle.y.baseVal[0].value -= 20; }
                resizePlot('altiProfil-chart-container');
            });

            hideElement('#altiProfil .menu-content #altiProfil-chart .spinner');
            const myPlot = document.getElementById('altiProfil-chart-container');

            //Add a geo point on the map when hovering the chart
            myPlot.on('plotly_hover', function(plotData){
                const p = plotData.points[0].customdata[0];
                const layers = lizMap.mainLizmap.map.getAllLayers();
                // searching for altiProfil layer
                layers.forEach( function (layer) {
                    if (layer.get('altiprofil') == true) {
                        // add a point to the layer corresponding to chart click
                        const features = layer.getSource().getFeatures();
                        const pCoord = new lizMap.ol.geom.Point([p.lon, p.lat]);
                        pCoord.transform('EPSG:'+_srs,lizMap.map.projection.projCode );
                        // remove last inserted feature if more than 3 (2 points + 1 line)
                        if(features.length > 3){
                            layer.getSource().removeFeature(features[features.length-1]);
                        }
                        layer.getSource().addFeature(  new lizMap.ol.Feature({
                            geometry: pCoord,
                            name: 'My point on plotly',
                        }) );
                    }
                });
            });

            //Remove the point on the map when unhovering the chart
            myPlot.on('plotly_unhover', function(){
                const layers = lizMap.mainLizmap.map.getAllLayers();
                layers.forEach( function (layer) {
                    if (layer.get('altiprofil') == true) {
                        const features = layer.getSource().getFeatures();
                        if(features.length > 3){
                            layer.getSource().removeFeature(features[features.length-1]);
                        }
                        
                    }
                });
            });
        },
        function(){ //ON ERROR
            // Network error (403 / 5xx)
            setAltiError(LOCALES_ALTI_ERROR_REQUEST);
        }
    );


}

/**
 * Initializes the marker vector layer (profile points/line) and wires all the
 * dock events (open/close for the 3 types) and the map click.
 */
function initAltiProfil() {
    //minimum distance between two points to compute a profile (in map units)
    const MIN_PROFILE_LENGTH = 1;
    // Style of the marker-layer features (red cross).
    const styles = new lizMap.ol.style.Style({
        stroke: new lizMap.ol.style.Stroke({color: 'red', width :2}),
        fill: new lizMap.ol.style.Fill({color: 'red', width :2}),
        image: new lizMap.ol.style.RegularShape({
          fill: new lizMap.ol.style.Fill({color: 'red', width :2}),
          stroke: new lizMap.ol.style.Stroke({color: 'red', width: 5}),
          points: 4,
          radius: 10,
          radius2: 0,
          angle: 0,
        }),
    });
    const altiProfilSource= new lizMap.ol.source.Vector();
    const altiProfilLayer = new  lizMap.ol.layer.Vector({
        style: styles,
        source: altiProfilSource,
        projection : lizMap.map.projection,
        properties : {"altiprofil" : true},
        visible : false
    });
    lizMap.mainLizmap.map.addToolLayer(altiProfilLayer);

    // Dock opened: disable the Lizmap popup and show the layer.
    function onAltiDockOpened() {
        // disable popup
        if (lizMap?.mainLizmap?.popup) {
            lizMap.mainLizmap.popup.active = false;
        }
        altiProfilLayer.setVisible(true);
    }

    // Dock closed: re-enable the popup, clear the chart and the layer.
    function onAltiDockClosed() {
        // enable popup
        if (lizMap?.mainLizmap?.popup) {
            lizMap.mainLizmap.popup.active = true;
        }        
        const container = document.getElementById('altiProfil-chart-container');
        if (container) { container.innerHTML = ''; }
        setTextSpans('...');
        altiProfilSource.clear();
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

    function getPoints() {
          return altiProfilSource.getFeatures()
              .filter(f => f.getGeometry().getType() === 'Point');
      }

    function segmentLength(c1, c2) {
        const length = lizMap.ol.sphere.getLength(new lizMap.ol.geom.LineString([c1, c2]), {projection: lizMap.map.projection.projCode});
        return length;
    }

    function addPoint(coord, pointNumber) {
        const feature = new lizMap.ol.Feature({
            geometry: new lizMap.ol.geom.Point(coord),
            name: 'AltiPoint' + pointNumber,
        });
        altiProfilSource.addFeature(feature);
        getAlti(feature, pointNumber);
        return feature;
    }

    function drawLine(p1, p2) {
        altiProfilSource.addFeature(new lizMap.ol.Feature({
            geometry: new lizMap.ol.geom.LineString([p1, p2]),
            style: new lizMap.ol.style.Style({
                stroke: new lizMap.ol.style.Stroke({ color: 'red', width: 4 }),
            }),
            name: 'AltiLine',
        }));
    }

    function reset() {
        altiProfilSource.clear();
        hideElement('#altiProfil .menu-content #altiProfil-chart');
        const container = document.getElementById('altiProfil-chart-container');
        if (container) { container.innerHTML = ''; }
        setTextSpans('...');
    }


    lizMap.mainLizmap.map.on('singleclick', evt => {
        if (!altiProfilLayer.getVisible()) { return; }

        const points = getPoints();

        switch (points.length) {            
            case 0: // first point add it
                addPoint(evt.coordinate, 1);
                break;

            case 1: { // second point add it and compute the profile
                const firstCoord = points[0].getGeometry().getCoordinates();                
                if (segmentLength(firstCoord, evt.coordinate) < MIN_PROFILE_LENGTH) {
                    return;   
                }
                const second = addPoint(evt.coordinate, 2);
                drawLine(firstCoord, evt.coordinate);
                getProfil(points[0].getGeometry(), second.getGeometry());
                break;
            }

            // Two points, reseat all and start a new point
            default:
                reset();
                addPoint(evt.coordinate, 1);
                break;
        }
    });
}
