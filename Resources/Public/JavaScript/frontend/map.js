/* Generated from Resources/Private/TypeScript — do not edit. */
import { Icon, map as createMap, marker, tileLayer } from "leaflet";
import { MarkerClusterGroup } from "leaflet.markercluster";
const DEFAULTS = {
  center: [51.1657, 10.4515],
  zoom: 6,
  maxZoom: 18,
  padding: 50,
  tileUrl: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
  markerImages: null
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
const directoryOf = (url) => {
  if (url === null) {
    return null;
  }
  const path = url.split(/[?#]/)[0] ?? "";
  return path.slice(0, path.lastIndexOf("/") + 1);
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
    attribution: text(data.academicPartnersAttribution) ?? DEFAULTS.attribution,
    markerImages: directoryOf(text(data.academicPartnersMarkerIcon))
  };
};
const popupFor = (name, link) => {
  const anchor = document.createElement("a");
  anchor.href = link;
  const title = document.createElement("b");
  title.textContent = name;
  anchor.append(title);
  return anchor;
};
const initializeMap = () => {
  const partnerContainer = document.getElementById("map-partners");
  if (partnerContainer === null) {
    return;
  }
  const configuration = readConfiguration(document.getElementById("map"));
  const tiles = tileLayer(
    configuration.tileUrl,
    {
      maxZoom: configuration.maxZoom,
      attribution: configuration.attribution
    }
  );
  const map = createMap("map", { zoom: configuration.zoom, maxZoom: configuration.maxZoom, layers: [tiles] });
  const markers = new MarkerClusterGroup({ chunkedLoading: true });
  const icon = configuration.markerImages === null ? void 0 : new Icon.Default({ imagePath: configuration.markerImages });
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
    markers.addLayer(
      marker([latitude, longitude], icon === void 0 ? {} : { icon }).bindPopup(popupFor(partner.dataset.name ?? "", partner.dataset.link ?? ""))
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
