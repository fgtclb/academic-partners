/**
 * Draws the partner map.
 *
 * Leaflet and its marker cluster plugin are vendored, minified and **patched**:
 * their global was renamed from "L" to "LeafletObject" so it cannot collide
 * with another Leaflet on the page. They have no sources here and are therefore
 * not part of this build — they are still loaded as classic scripts, and this
 * module reads the global they define.
 *
 * Only what is actually called is typed. A full set of Leaflet types would be a
 * dependency, and it would describe a build that is not the one on the page.
 */
interface LeafletBounds {
    readonly _southWest?: unknown;
}

interface LeafletMarker {
    bindPopup(content: string): LeafletMarker;
}

interface LeafletMarkerClusterGroup {
    addLayer(marker: LeafletMarker): void;
    getLayers(): unknown[];
    getBounds(): LeafletBounds;
}

interface LeafletMap {
    addLayer(layer: LeafletMarkerClusterGroup): void;
    fitBounds(bounds: LeafletBounds, options: { padding: [number, number] }): void;
    setView(center: [number, number], zoom: number): void;
}

interface LeafletStatic {
    tileLayer(urlTemplate: string, options: { maxZoom: number; attribution: string }): unknown;
    map(elementId: string, options: { zoom: number; maxZoom: number; layers: unknown[] }): LeafletMap;
    markerClusterGroup(options: { chunkedLoading: boolean }): LeafletMarkerClusterGroup;
    marker(position: [number, number]): LeafletMarker;
}

const leaflet = (): LeafletStatic | undefined =>
    (window as unknown as { LeafletObject?: LeafletStatic }).LeafletObject;

/**
 * What the map is drawn with. The site settings of the map set reach the module
 * as data attributes of "#map", and every value falls back to the one the map
 * was drawn with before it could be configured. Those are the only values an
 * overridden template without the attributes gets.
 */
interface MapConfiguration {
    readonly center: [number, number];
    readonly zoom: number;
    readonly maxZoom: number;
    readonly padding: number;
    readonly tileUrl: string;
    readonly attribution: string;
}

const DEFAULTS: MapConfiguration = {
    center: [51.1657, 10.4515],
    zoom: 6,
    maxZoom: 18,
    padding: 50,
    tileUrl: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
};

/**
 * A number between the two bounds, or null for anything else.
 *
 * `Number('')` is `0`, so an empty attribute is told apart from a written zero
 * on the raw value, as the partner coordinates below are.
 */
const numberBetween = (raw: string | undefined, minimum: number, maximum: number): number | null => {
    const trimmed = raw?.trim() ?? '';
    const value = Number(trimmed);

    if (trimmed === '' || !Number.isFinite(value) || value < minimum || value > maximum) {
        return null;
    }

    return value;
};

const text = (raw: string | undefined): string | null => {
    const trimmed = raw?.trim() ?? '';

    return trimmed === '' ? null : trimmed;
};

const readConfiguration = (element: HTMLElement | null): MapConfiguration => {
    const data = element?.dataset ?? {};
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
    };
};

const initializeMap = (): void => {
    const library = leaflet();
    const partnerContainer = document.getElementById('map-partners');

    // The original assumed both. A page that renders the plugin without the
    // vendored scripts, or without the partner list, threw and took the rest of
    // the page's scripts down with it.
    if (library === undefined || partnerContainer === null) {
        return;
    }

    const configuration = readConfiguration(document.getElementById('map'));

    const tiles = library.tileLayer(
        configuration.tileUrl,
        {
            maxZoom: configuration.maxZoom,
            attribution: configuration.attribution,
        },
    );

    // The maximum on the map as well: "fitBounds" is capped by the map, which
    // otherwise takes the maximum of the layers it happens to hold.
    const map = library.map('map', { zoom: configuration.zoom, maxZoom: configuration.maxZoom, layers: [tiles] });
    const markers = library.markerClusterGroup({ chunkedLoading: true });

    partnerContainer.querySelectorAll<HTMLElement>('.map-partner').forEach((partner): void => {
        const rawLatitude = partner.dataset.lat?.trim() ?? '';
        const rawLongitude = partner.dataset.lng?.trim() ?? '';
        const latitude = Number(rawLatitude);
        const longitude = Number(rawLongitude);

        // `Number('')` is `0`, not `NaN`, so an absent coordinate walked straight
        // through the previous `Number.isNaN()` check and was drawn at 0/0 - open
        // ocean off Africa (ACE-562). Absence is therefore tested on the raw
        // attribute, before the conversion that hides it.
        //
        // The pair 0/0 is refused whatever spelling it arrives in, because it means
        // "nothing was written" rather than a place. A single zero is kept: longitude
        // 0 runs through the United Kingdom, France, Spain and Ghana, and latitude 0
        // is the equator.
        const unusable = rawLatitude === ''
            || rawLongitude === ''
            || !Number.isFinite(latitude)
            || !Number.isFinite(longitude)
            || (latitude === 0 && longitude === 0);

        if (unusable) {
            // Kept from the original: a partner record with unusable coordinates
            // is an editorial mistake, and silence would make it invisible.
            // eslint-disable-next-line no-console
            console.warn('Invalid coordinates for partner:', partner.dataset.name);
            return;
        }

        const name = partner.dataset.name ?? '';
        const link = partner.dataset.link ?? '';

        markers.addLayer(
            library
                .marker([latitude, longitude])
                .bindPopup(`<a href='${link}'><b>${name}</b></a>`),
        );
    });

    map.addLayer(markers);

    if (markers.getLayers().length > 0) {
        map.fitBounds(markers.getBounds(), { padding: [configuration.padding, configuration.padding] });
    } else {
        map.setView(configuration.center, configuration.zoom);
    }
};

// `f:asset.module` renders every module with `async`, so this file is not
// ordered against document parsing and regularly runs after
// `DOMContentLoaded` has already fired. Waiting for that event unconditionally
// would then wait forever and the map would never be drawn.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMap, { once: true });
} else {
    initializeMap();
}

export { initializeMap };
