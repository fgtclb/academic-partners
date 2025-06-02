document.addEventListener('DOMContentLoaded', () => {
    const tiles = LeafletObject.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ'
        }
    );

    const map = LeafletObject.map('map', {zoom: 6, layers: [tiles]});

    var markers = LeafletObject.markerClusterGroup({chunkedLoading: true});
    var partnerList = document.getElementById("map-partners").querySelectorAll(".map-partner");

    for (var i = 0; i < partnerList.length; i++) {

        var partner = partnerList[i];
        var lat = partner.dataset.lat;
        var lng = partner.dataset.lng;

        if (isNaN(lat) || isNaN(lng)) {
            console.warn("Invalid coordinates for partner:", partner.dataset.name);
            continue;
        }

        var name = partner.dataset.name;
        var link = partner.dataset.link;

        var marker = LeafletObject.marker([lat, lng]).bindPopup("<a href='" + link + "'><b>" + name + "</b></a>");
        markers.addLayer(marker);
    }

    map.addLayer(markers);

    if (markers.getLayers().length > 0) {
        map.fitBounds(markers.getBounds(), {
            padding: [50, 50]
        });
    } else {
        map.setView([51.1657, 10.4515], 6);
    }
  });
