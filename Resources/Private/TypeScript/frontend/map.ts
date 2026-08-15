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
    map(elementId: string, options: { zoom: number; layers: unknown[] }): LeafletMap;
    markerClusterGroup(options: { chunkedLoading: boolean }): LeafletMarkerClusterGroup;
    marker(position: [number, number]): LeafletMarker;
}

const leaflet = (): LeafletStatic | undefined =>
    (window as unknown as { LeafletObject?: LeafletStatic }).LeafletObject;

document.addEventListener('DOMContentLoaded', (): void => {
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
        const latitude = Number(partner.dataset.lat);
        const longitude = Number(partner.dataset.lng);

        if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
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
        map.fitBounds(markers.getBounds(), { padding: [50, 50] });
    } else {
        map.setView([51.1657, 10.4515], 6);
    }
});

export {};
