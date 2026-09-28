/* Generated from Resources/Private/TypeScript — do not edit. */
const leaflet = () => window.LeafletObject;
const DEFAULTS = {
  center: [51.1657, 10.4515],
  zoom: 6,
  maxZoom: 18,
  padding: 50,
  tileUrl: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ'
};
const numberBetween = (raw, minimum, maximum) => {
  const trimmed = (raw == null ? void 0 : raw.trim()) ?? "";
  const value = Number(trimmed);
  if (trimmed === "" || !Number.isFinite(value) || value < minimum || value > maximum) {
    return null;
  }
  return value;
};
const text = (raw) => {
  const trimmed = (raw == null ? void 0 : raw.trim()) ?? "";
  return trimmed === "" ? null : trimmed;
};
const readConfiguration = (element) => {
  const data = (element == null ? void 0 : element.dataset) ?? {};
  const latitude = numberBetween(data.academicPartnersCenterLat, -90, 90);
  const longitude = numberBetween(data.academicPartnersCenterLng, -180, 180);
  return {
    // One value: half of a centre is a place nobody chose.
    center: latitude !== null && longitude !== null ? [latitude, longitude] : DEFAULTS.center,
    zoom: numberBetween(data.academicPartnersZoom, 0, Infinity) ?? DEFAULTS.zoom,
    maxZoom: numberBetween(data.academicPartnersMaxZoom, 0, Infinity) ?? DEFAULTS.maxZoom,
    padding: numberBetween(data.academicPartnersPadding, 0, Infinity) ?? DEFAULTS.padding,
    tileUrl: text(data.academicPartnersTileUrl) ?? DEFAULTS.tileUrl,
    attribution: text(data.academicPartnersAttribution) ?? DEFAULTS.attribution
  };
};
const initializeMap = () => {
  const library = leaflet();
  const partnerContainer = document.getElementById("map-partners");
  if (library === void 0 || partnerContainer === null) {
    return;
  }
  const configuration = readConfiguration(document.getElementById("map"));
  const tiles = library.tileLayer(
    configuration.tileUrl,
    {
      maxZoom: configuration.maxZoom,
      attribution: configuration.attribution
    }
  );
  const map = library.map("map", { zoom: configuration.zoom, maxZoom: configuration.maxZoom, layers: [tiles] });
  const markers = library.markerClusterGroup({ chunkedLoading: true });
  partnerContainer.querySelectorAll(".map-partner").forEach((partner) => {
    var _a, _b;
    const rawLatitude = ((_a = partner.dataset.lat) == null ? void 0 : _a.trim()) ?? "";
    const rawLongitude = ((_b = partner.dataset.lng) == null ? void 0 : _b.trim()) ?? "";
    const latitude = Number(rawLatitude);
    const longitude = Number(rawLongitude);
    const unusable = rawLatitude === "" || rawLongitude === "" || !Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude === 0 && longitude === 0;
    if (unusable) {
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
    map.fitBounds(markers.getBounds(), { padding: [configuration.padding, configuration.padding] });
  } else {
    map.setView(configuration.center, configuration.zoom);
  }
};
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeMap, { once: true });
} else {
  initializeMap();
}
export {
  initializeMap
};
