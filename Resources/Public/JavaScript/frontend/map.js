/* Generated from Resources/Private/TypeScript — do not edit. */
const leaflet = () => window.LeafletObject;
document.addEventListener("DOMContentLoaded", () => {
  const library = leaflet();
  const partnerContainer = document.getElementById("map-partners");
  if (library === void 0 || partnerContainer === null) {
    return;
  }
  const tiles = library.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
      maxZoom: 18,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ'
    }
  );
  const map = library.map("map", { zoom: 6, layers: [tiles] });
  const markers = library.markerClusterGroup({ chunkedLoading: true });
  partnerContainer.querySelectorAll(".map-partner").forEach((partner) => {
    const latitude = Number(partner.dataset.lat);
    const longitude = Number(partner.dataset.lng);
    if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
      console.warn("Invalid coordinates for partner:", partner.dataset.name);
      return;
    }
    const name = partner.dataset.name ?? "";
    const link = partner.dataset.link ?? "";
    markers.addLayer(
      library.marker([latitude, longitude]).bindPopup(`<a href='${link}'><b>${name}</b></a>`)
    );
  });
  map.addLayer(markers);
  if (markers.getLayers().length > 0) {
    map.fitBounds(markers.getBounds(), { padding: [50, 50] });
  } else {
    map.setView([51.1657, 10.4515], 6);
  }
});
