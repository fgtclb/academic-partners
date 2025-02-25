document.addEventListener('DOMContentLoaded', () => {

    const tiles = L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ'
        }
    );

    const map = L.map('map', {zoom: 6, layers: [tiles]});

    var markers = L.markerClusterGroup({chunkedLoading: true});
    var partnerList = document.getElementById("partners").querySelectorAll("li");

    for (var i = 0; i < partnerList.length; i++) {

        var partner = partnerList[i];
        var lat = partner.dataset.lat;
        var lng = partner.dataset.lng;
        var name = partner.dataset.name;
        var description = partner.dataset.description;
        var link = partner.dataset.link;

        var marker = L.marker([lat, lng]).bindPopup("<b>" + name + "</b><br>" + truncateString(description, 80) + "<br><br>" + "<a href='" + link + "'>Mehr Informationen</a>");
        markers.addLayer(marker);
    }

    map.addLayer(markers);
});

function truncateString(str, num) {
    if (str.length > num) {
      return str.slice(0, num) + "...";
    } else {
      return str;
    }
  }
