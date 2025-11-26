/**
 * Adds markers to the map highlighting the locations
 * @param  {H.Map} map      A HERE Map instance within the application
 */

function addMarkersToMap(map, H) {

    var markers = [];
    var group = new H.map.Group();

    $.each(markersa, function(ele) {

        var lat = parseFloat(this.ltd);
        var lng = parseFloat(this.lgt);

        markers[ele] = new H.map.Marker({lat:lat, lng:lng});


        var outerElement = document.createElement('div'),
            innerElement = document.createElement('div');

        outerElement.style.userSelect = 'none';
        outerElement.style.webkitUserSelect = 'none';
        outerElement.style.msUserSelect = 'none';
        outerElement.style.mozUserSelect = 'none';
        outerElement.style.cursor = 'default';

        innerElement.style.color = 'red';
        innerElement.style.backgroundColor = 'blue';
        innerElement.style.border = '2px solid black';
        innerElement.style.font = 'normal 12px arial';
        innerElement.style.lineHeight = '12px'

        innerElement.style.paddingTop = '2px';
        innerElement.style.paddingLeft = '4px';

        // add negative margin to inner element
        // to move the anchor to center of the div
        innerElement.style.marginTop = '-10px';
        innerElement.style.marginLeft = '-10px';

        outerElement.appendChild(innerElement);

        // Add text to the DOM element
        var ie = "" +
            "<a href='"+this.groupurl+"#l"+this.id+"' alt='"+this.group+"' title='"+this.group+"'>" +
                "<img src='"+this.image+"'/>" +
            "</a>";
        innerElement.innerHTML = ie;

        function changeOpacity(evt) {
            evt.target.style.opacity = 0.6;
        };

        function changeOpacityToOne(evt) {
            evt.target.style.opacity = 1;
        };

        //create dom icon and add/remove opacity listeners
        var domIcon = new H.map.DomIcon(outerElement, {
            // the function is called every time marker enters the viewport
            onAttach: function(clonedElement, domIcon, domMarker) {
                clonedElement.addEventListener('mouseover', changeOpacity);
                clonedElement.addEventListener('mouseout', changeOpacityToOne);
            },
            // the function is called every time marker leaves the viewport
            onDetach: function(clonedElement, domIcon, domMarker) {
                clonedElement.removeEventListener('mouseover', changeOpacity);
                clonedElement.removeEventListener('mouseout', changeOpacityToOne);
            }
        });

        // Marker for Chicago Bears home
        markers[ele] = new H.map.DomMarker({lat:lat, lng:lng}, {
            icon: domIcon
        });



        //console.log({lat: parseFloat(this.ltd), lng: parseFloat(this.lgt)});
    });

    group.addObjects(markers);
    map.addObject(group);
    map.setViewBounds(group.getBounds());
}

/**
 * Boilerplate map initialization code starts below:
 */

//Step 1: initialize communication with the platform
var platform = new H.service.Platform({
    app_id: 'rNZQbBfxTA0QPHCPhS5A',
    app_code: 'HyGwZdIjeNPrw31CsEuNJw',
    useHTTPS: true
});
var pixelRatio = window.devicePixelRatio || 1;
var defaultLayers = platform.createDefaultLayers({
    tileSize: pixelRatio === 1 ? 256 : 512,
    ppi: pixelRatio === 1 ? undefined : 320
});

//Step 2: initialize a map - this map is centered over Europe
var map = new H.Map(document.getElementById('map'),
    defaultLayers.normal.map,{
        center: {lat:50, lng:5},
        zoom: 4,
        pixelRatio: pixelRatio
    });

//Step 3: make the map interactive
// MapEvents enables the event system
// Behavior implements default interactions for pan/zoom (also on mobile touch environments)
var behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(map));

// Create the default UI components
var ui = H.ui.UI.createDefault(map, defaultLayers);

// Now use the map as required...
addMarkersToMap(map, H);