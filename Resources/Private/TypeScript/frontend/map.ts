/**
 * Draws the partner map.
 *
 * Leaflet and its marker cluster plugin are ES modules built from their npm
 * packages into "Resources/Public/JavaScript/vendor/" and published under the
 * bare specifiers imported below, see "Build/vendor.mjs". Their types are the
 * small surface declared in "_dependencies.d.ts".
 */
import { Icon, map as createMap, marker, tileLayer } from 'leaflet';
import { MarkerClusterGroup } from 'leaflet.markercluster';

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
    /** The directory of the marker images, or null for the ones of Leaflet. */
    readonly markerImages: string | null;
}

const DEFAULTS: MapConfiguration = {
    center: [51.1657, 10.4515],
    zoom: 6,
    maxZoom: 18,
    padding: 50,
    tileUrl: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
    markerImages: null,
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

/**
 * The directory of the marker images, from the URL of the marker icon. Leaflet
 * loads "marker-icon.png", "marker-icon-2x.png" and "marker-shadow.png" from
 * there. A query string, the cache buster of TYPO3, is not part of it.
 */
const directoryOf = (url: string | null): string | null => {
    if (url === null) {
        return null;
    }
    const path = url.split(/[?#]/)[0] ?? '';

    return path.slice(0, path.lastIndexOf('/') + 1);
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
        markerImages: directoryOf(text(data.academicPartnersMarkerIcon)),
    };
};

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
    const partnerContainer = document.getElementById('map-partners');

    // A page that renders the module without the partner list has no map to
    // draw, and throwing would take the rest of the page's scripts down.
    if (partnerContainer === null) {
        return;
    }

    const configuration = readConfiguration(document.getElementById('map'));

    const tiles = tileLayer(
        configuration.tileUrl,
        {
            maxZoom: configuration.maxZoom,
            attribution: configuration.attribution,
        },
    );

    // The maximum on the map as well: "fitBounds" is capped by the map, which
    // otherwise takes the maximum of the layers it happens to hold.
    const map = createMap('map', { zoom: configuration.zoom, maxZoom: configuration.maxZoom, layers: [tiles] });
    const markers = new MarkerClusterGroup({ chunkedLoading: true });
    // Per marker, not on Leaflet's class: another module of the page may load
    // the same Leaflet through the import map.
    const icon = configuration.markerImages === null
        ? undefined
        : new Icon.Default({ imagePath: configuration.markerImages });

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

        markers.addLayer(
            marker([latitude, longitude], icon === undefined ? {} : { icon })
                .bindPopup(popupFor(partner.dataset.name ?? '', partner.dataset.link ?? '')),
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
