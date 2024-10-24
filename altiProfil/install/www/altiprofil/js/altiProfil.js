lizMap.events.on({
    'uicreated': function(e) {
        $('#profil-stop').click(function(){
            $('#button-altiProfil').click();
        });
        $('#altiProfil .menu-content #profil-chart').hide();
        initAltiProfil();
    }
});

function getAltiJsonResponse(params, aCallback){
    $.get(
        URLAJAXALTICOORD,
        params,
        function(data) {
            if(aCallback){
                    aCallback(data);
            }
        }
        ,'json'
    );
}

function getAlti(lon,lat, numFeat){
    //IGN Web Service only allows coordinates in 4326
    if(lizMap.map.projection.projCode != "EPSG:4326"){
        var fromProjection = new OpenLayers.Projection(lizMap.map.projection.projCode);
        var toProjection = new OpenLayers.Projection("EPSG:4326");
        var convertedPoint = new OpenLayers.LonLat(lon, lat);
        convertedPoint.transform(fromProjection, toProjection);
        lon = convertedPoint.lon;
        lat = convertedPoint.lat;    }

    var qParams = {
        'lon': lon,
        'lat':lat,
        'srs': lizMap.map.projection.projCode,
        'repository': lizUrls.params.repository,
        'project': lizUrls.params.project
    }
    getAltiJsonResponse(qParams, function(data){
        var alt = data['elevations'][0]['z'];
        $('#altiProfil .menu-content #alt-p'+numFeat).html( alt );
    });
}

function getProfilJsonResponse(params, aCallback){
    $('#altiProfil .menu-content #profil-chart .spinner').show();
    $.get(
        URLAJAXALTIPROFIL,
        params,
        function(data) {
            if(aCallback){
                    aCallback(data);
            }
        }
        ,'json'
    );
}

function resizePlot(id){
    $('#'+id)
    .css({
        width: '100%',
        margin: '0px'
    });
    Plotly.Plots.resize($('#'+id)[0]);
}

function getProfil(p1,p2){
    var p1Point = new OpenLayers.LonLat(p1.x, p1.y);
    var p2Point = new OpenLayers.LonLat(p2.x, p2.y);
    if(lizMap.map.projection.projCode != "EPSG:4326"){
        var fromProjection = new OpenLayers.Projection(lizMap.map.projection.projCode);
        var toProjection = new OpenLayers.Projection("EPSG:4326");
        p1Point.transform(fromProjection, toProjection);
        p2Point.transform(fromProjection, toProjection);
    }

    var distance = Math.round(p1.distanceTo(p2));
    var sampling = Math.round(distance <= 100 ? distance - 2 : (distance <= 500 ? distance / 5 : distance / 25));

    var qParams = {
        'p1Lon': p1Point.lon,
        'p1Lat': p1Point.lat,
        'p2Lon': p2Point.lon,
        'p2Lat': p2Point.lat,
        'srs': lizMap.map.projection.projCode,
        'repository': lizUrls.params.repository,
        'project': lizUrls.params.project,
        'sampling' : sampling /* Only use with french mapping Agency (IGN) web service  */,
        'distance' : distance
    }

    getProfilJsonResponse(qParams, function(data){
        var _x = data[0]['x'];
        var _y = data[0]['y'];
        var _customdata = data[0]['customdata'];
        var _srs = data[0]['srid'];
        var _altisource = data[0]['altisource'];

        var layout = {
            title: '<b>'+LOCALES_ALTI_PROFIL+'</b>',
            xaxis: {
                title: LOCALES_ALTI_DISTANCE +' (m)',
                showaxeslabels:false
            },
            yaxis: {
                title: LOCALES_ALTI_ELEVATION
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
                text: `point 1 (${Math.round(p1.x)},${Math.round(p1.y)}) | point 2 (${Math.round(p2.x)},${Math.round(p2.y)})`
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
                text: `<i>${LOCALES_ALTI_DATASOURCE} : ${_altisource}</i>`
            }],
            showlegend: false,
            autosize: true
        };

        //add extra info if datasource from DB
        if ( ALTI_PROVIDER == "database"){
            var _resolution = data[0]['resolution'];
            var _slope = data[0]['slope'];

            layout['title'] = '<b>Profil ('+ LOCALES_ALTI_RESOLUTION +' ' +_resolution+ 'm)';
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

        var profilLine = {
            x: _x,
            y: _y,
            customdata:_customdata,
            mode: 'lines',
            line: {
              color: 'rgb(128, 0, 128)',
              width: 1
            }
            ,hovertemplate: '<b>Altitude</b>: %{y}' +
            '<br /><b>lon</b> : %{customdata[0].lon:.2f} / <b>lat</b> : %{customdata[0].lat:.2f}</b>'+
            '<extra></extra>'
          };
        var data = [profilLine];

        var plotLocale = lizMap.config.datavizLayers.locale.substr(0,2).toLowerCase();
        var config = {
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
        Plotly.newPlot('profil-chart-container', data, layout, config);
        $('#altiProfil .menu-content #profil-chart .spinner').hide();
        var myPlot = document.getElementById('profil-chart-container');

        myPlot.on('plotly_click', function(data){
            p = data.points[0].customdata[0];
            var fromProjection = new OpenLayers.Projection('EPSG:'+_srs);
            var toProjection = new OpenLayers.Projection(lizMap.map.projection.projCode);
            var p1ConvertedPoint = new OpenLayers.LonLat(p.lon, p.lat);
            p1ConvertedPoint.transform(fromProjection, toProjection);
            var layer = lizMap.map.getLayersByName('altiProfilLayer')[0];
            if(layer.features.length>3){
                layer.removeFeatures(layer.features[layer.features.length-1]);
            }
            layer.addFeatures([
                new OpenLayers.Feature.Vector(
                    new OpenLayers.Geometry.Point(p1ConvertedPoint.lon, p1ConvertedPoint.lat)
                )
            ]);
        });
        document.getElementsByClassName('xtitle')[0].y.baseVal[0].value = document.getElementsByClassName('xtitle')[0].y.baseVal[0].value - 20;
        resizePlot('profil-chart-container')
    });
}

function initAltiProfil() {
    var map = lizMap.map;
    //Layer to display clic location
    altiProfilLayer = map.getLayersByName('altiProfilLayer');
    if ( altiProfilLayer.length == 0 ) {
        altiProfilLayer = new OpenLayers.Layer.Vector('altiProfilLayer',{
            styleMap: new OpenLayers.StyleMap({
                graphicName: 'cross',
                pointRadius: 6,
                fill: true,
                fillColor: 'white',
                fillOpacity: 1,
                stroke: true,
                strokeWidth: 2,
                strokeColor: 'red',
                strokeOpacity: 1,
                orientation: true
            })
        });
        map.addLayer(altiProfilLayer);
        altiProfilLayer.setVisibility(true);
    }

    // add altiprofilCtrl prop to Control with value true to distiguish it
    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
        'altiprofilCtrl' :true,
        defaultHandlerOptions: {
            'single': true,
            'double': false,
            'pixelTolerance': 0,
            'stopSingle': false,
            'stopDouble': false
        },
        initialize: function(options) {
            this.handlerOptions = OpenLayers.Util.extend(
            {}, this.defaultHandlerOptions
            );
            OpenLayers.Control.prototype.initialize.apply(
            this, arguments
            );
            this.handler = new OpenLayers.Handler.Click(
            this, {
                'click': this.trigger
            }, this.handlerOptions
            );
        },
        trigger: function(e) {
            if(altiProfilLayer.features.length>=2){
                altiProfilLayer.destroyFeatures();
                $('#altiProfil .menu-content #profil-chart').hide();
                $('#altiProfil .menu-content #profil-chart-container').empty();
                $('#altiProfil .menu-content span').html( "..." );
            }
            var lonlat = map.getLonLatFromPixel(e.xy);
            altiProfilLayer.addFeatures([
                new OpenLayers.Feature.Vector(
                    new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat)
                )
            ]);
            numFeat = altiProfilLayer.features.length;
            getAlti(lonlat.lon,lonlat.lat, numFeat);
            if(altiProfilLayer.features.length==2){
                p1Geom = altiProfilLayer.features[0].geometry.getCentroid();
                p2Geom = altiProfilLayer.features[1].geometry.getCentroid();
                altiProfilLayer.addFeatures(
                    [new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString([p1Geom, p2Geom]))]
                );

                getProfil(p1Geom, p2Geom);
                //setTimeout(() => { getProfil(p1Geom, p2Geom); }, 5000);

                $('#altiProfil .menu-content #profil-chart').show();
            }
        }
    });
    var profilClick = new OpenLayers.Control.Click();
    map.addControl(profilClick);

    function onAltiDockOpened() {
        var controls = lizMap.map.controls;
        controls.forEach(function (ctrl) {
            if (ctrl.CLASS_NAME == 'OpenLayers.Control.WMSGetFeatureInfo'){
                ctrl.deactivate();
            }
            // desactivate existing control which handle single click (TODO : should store previous state if multiple controls of this kind)
            if (ctrl.CLASS_NAME == 'OpenLayers.Control' && ctrl.defaultHandlerOptions?.single == true && ctrl?.altiprofilCtrl != true) {
                ctrl.deactivate();
            }
        });
        altiProfilLayer.setVisibility(true);
        profilClick.activate();
    }

    function onAltiDockClosed() {
        var controls = lizMap.map.controls;
        controls.forEach(function (ctrl) {
            if (ctrl.CLASS_NAME == 'OpenLayers.Control.WMSGetFeatureInfo'){
                ctrl.activate();
            }
            // activate existing control which handle single click
            if (ctrl.CLASS_NAME == 'OpenLayers.Control' && ctrl.defaultHandlerOptions?.single == true && ctrl?.altiprofilCtrl != true) {
                ctrl.activate();
            }
        });
        $('#altiProfil .menu-content #profil-chart-container').empty();
        $('#altiProfil .menu-content span').html( "..." );
        altiProfilLayer.destroyFeatures();
        altiProfilLayer.setVisibility(false);
        profilClick.deactivate();
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



}
