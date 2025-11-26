$(document).ready(function () {

    var currentobject = null;

    $(".submitbutton").click(function () {
        var $this = $(this);
        console.log($this);
        if ($this.data('text') != "")
            $this.find("span").text($this.data('text'));
        window[$this.data('function')]();
        $this.off('click');
    });

    $('.map-wrapper').toggle();

    $('#color1').val(rgb2hex($("#bluething").css('fill')));
    //$('#color2').val(rgb2hex($("#greenthing").css('fill')));
    //$('#color3').val(rgb2hex($(".layerG").first().css('fill')));
    //$('#color4').val(rgb2hex($(".circleG").css('fill')));


    $("#geologo circle, #geologo path").click(function () {
        console.log(this);
        currentobject = this;
        $('#color1').val(rgb2hex($(this).css('fill')));
        console.log($('#color1').val());

        $("#color1").spectrum({
            flat: true,
            showInput: true,
            preferredFormat: "hex",
            clickoutFiresChange: false,
            change: function(color) {
                $(currentobject).css('fill', color.toHexString());
            }
        });
    });

});

function rgb2hex(rgb) {
    console.log(rgb);
    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    console.log(rgb);
    return "#" +
        ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[3],10).toString(16)).slice(-2);
}

function addMarkersToMapOsm(map) {

    $.each(markersa, function(ele) {
        var lat = parseFloat(this.ltd);
        var lng = parseFloat(this.lgt);
        var marker = L.marker([lat, lng]).addTo(map);
        var ie = "" +
            "<a href='"+this.groupurl+"#l"+this.id+"' alt='"+this.group+"' title='"+this.group+"'>" +
            "<img src='"+this.image+"'/>" +
            "</a>";
        marker.bindPopup(ie).openPopup();
        markersg.push(marker);
    });

}

function getLocation() {
    console.log(123);
    if (navigator.geolocation) {
        var url = window.location.href;
        console.log(url.substr(4, 1));
        if (url.substr(4, 1) == "s") {
            navigator.geolocation.getCurrentPosition(showPosition);
        }
        else {
            var position = {
                coords: {
                    latitude: 11.1111,
                    longitude: 11.1111
                }
            };
            showPosition(position);
        }
    } else {
        console.log(3);
        alert("Geolocation is not supported by this browser.");
    }
}

function getAviableLocationsClick() {
    console.log(345);

    if (navigator.geolocation) {
        var url = window.location.href;
        console.log(url.substr(4, 1));
        if (url.substr(4, 1) == "s") {
            navigator.geolocation.getCurrentPosition(getAviableLocations);
        }
        else {
            var position = {
                coords: {
                    latitude: 11.1111,
                    longitude: 11.1111
                }
            };
            getAviableLocations(position);
        }
    } else {
        console.log(3);
        alert("Geolocation is not supported by this browser.");
    }
}

function pressSubmitButton(f, t) {

    console.log(t);
    $(this).off('click');
    //f();

}

function getAviableLocations(position) {
    console.log(position);

    /*$.ajax({
        type: "POST",
        url: "getaviablelocations",
        data: {
            ltd: position.coords.latitude,
            lgt: position.coords.longitude
        },
        success: function(data) {

            console.log(data);


        }
    });*/

    redirectPost('/location/getaviablelocations', {
        ltd: position.coords.latitude,
        lgt: position.coords.longitude
    });
}

function showPosition(position) {
    console.log(555);
    document.getElementById("ltd").value = position.coords.latitude;
    document.getElementById("lgd").value = position.coords.longitude;
    document.getElementById('myForm').submit();
}

function submitForm() {
    console.log("submitting form");
    return getLocation();
}

function redirectPost(location, args)
{
    var form = $('<form></form>');
    form.attr("method", "post");
    form.attr("action", location);

    $.each( args, function( key, value ) {
        var field = $('<input></input>');

        field.attr("type", "hidden");
        field.attr("name", key);
        field.attr("value", value);

        form.append(field);
    });
    $(form).appendTo('body').submit();
}