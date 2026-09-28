/**
 * Draws the partner map.
 *
 * Leaflet and its marker cluster plugin are classic scripts built from their
 * npm packages (see "Build/vendor.mjs"). They publish the global
 * "LeafletObject" rather than "L", so they cannot collide with another Leaflet
 * on the page, and this module reads that global.
 *
 * Only what is actually called is typed.
 */
interface LeafletBounds {
    readonly _southWest?: unknown;
}

interface LeafletMarker {
    bindPopup(content: HTMLElement | string): LeafletMarker;
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
    map(elementId: string, options: { zoom: number; layers: unknown[] }): LeafletMap;
    markerClusterGroup(options: { chunkedLoading: boolean }): LeafletMarkerClusterGroup;
    marker(position: [number, number]): LeafletMarker;
}

const leaflet = (): LeafletStatic | undefined =>
    (window as unknown as { LeafletObject?: LeafletStatic }).LeafletObject;

/**
 * The popup of a partner: its title in bold, linked to its page.
 *
 * Built from elements, so a title such as "Smith & Sons" is shown as it is
 * written.
 */
const popupFor = (name: string, link: string): HTMLElement => {
    const anchor = document.createElement('a');
    anchor.href = link;
    const title = document.createElement('b');
    title.textContent = name;
    anchor.append(title);

    return anchor;
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

    const tiles = library.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
        },
    );

    const map = library.map('map', { zoom: 6, layers: [tiles] });
    const markers = library.markerClusterGroup({ chunkedLoading: true });

    partnerContainer.querySelectorAll<HTMLElement>('.map-partner').forEach((partner): void => {
        const rawLatitude = partner.dataset.lat?.trim() ?? '';
        const rawLongitude = partner.dataset.lng?.trim() ?? '';
        const latitude = Number(rawLatitude);
        const longitude = Number(rawLongitude);

        // `Number('')` is `0`, not `NaN`, so an absent coordinate walked straight
        // through the previous `Number.isNaN()` check and was drawn at 0/0 - open
        // ocean off Africa (ACE-708). Absence is therefore tested on the raw
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

        markers.addLayer(
            library
                .marker([latitude, longitude])
                .bindPopup(popupFor(partner.dataset.name ?? '', partner.dataset.link ?? '')),
        );
    });

    map.addLayer(markers);

    if (markers.getLayers().length > 0) {
        map.fitBounds(markers.getBounds(), { padding: [50, 50] });
    } else {
        map.setView([51.1657, 10.4515], 6);
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

export {};
