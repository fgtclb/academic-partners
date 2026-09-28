import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { resetBody } from "../../../../../Build/tests/dom.mjs";

/**
 * The partner map, driven the way a browser drives it.
 *
 * "f:asset.module" renders every module with "async", so this file is not
 * ordered against document parsing: it regularly runs once the document is
 * already "complete". The harness window is in exactly that state - jsdom
 * finishes parsing the markup it is constructed from before a test sees it -
 * so importing the module here reproduces the late load without arranging
 * anything.
 *
 * Leaflet and its cluster plugin are vendored, minified classic scripts that
 * publish the global "LeafletObject". They have no module specifier, so the
 * stub below is a global rather than a stub module, and it records the calls
 * the map is expected to make.
 */
interface StubMarker {
  readonly position: [number, number];
  readonly popup: string | null;
  bindPopup(content: string): StubMarker;
}

interface StubTileLayer {
  readonly urlTemplate: string;
  readonly options: { maxZoom: number; attribution: string };
}

interface StubMap {
  readonly elementId: string;
  readonly options: { zoom: number; maxZoom: number };
  readonly layers: unknown[];
  readonly fitted: { padding: [number, number] } | null;
  readonly view: { center: [number, number]; zoom: number } | null;
}

interface Recorded {
  map: StubMap | null;
  tiles: StubTileLayer | null;
  markers: StubMarker[];
}

const SPECIFIER = "@fgtclb/academic-partners/frontend/map.js";

const installLeafletStub = (): Recorded => {
  const recorded: Recorded = {
    map: null,
    tiles: null,
    markers: [],
  };

  const markerGroup = {
    added: [] as StubMarker[],
    addLayer(marker: StubMarker): void {
      this.added.push(marker);
      recorded.markers.push(marker);
    },
    getLayers(): unknown[] {
      return this.added;
    },
    getBounds(): object {
      return { _southWest: {} };
    },
  };

  // On the window, not on "globalThis": the module reads
  // "(window as ...).LeafletObject", and the harness installs the jsdom window
  // as "globalThis.window" rather than merging it into the global object.
  (window as unknown as { LeafletObject: unknown }).LeafletObject = {
    tileLayer: (urlTemplate: string, options: { maxZoom: number; attribution: string }): StubTileLayer => {
      const tiles = { urlTemplate, options: { ...options } };
      recorded.tiles = tiles;
      return tiles;
    },
    map: (elementId: string, options: { zoom: number; maxZoom: number; layers: unknown[] }): StubMap => {
      const map = {
        elementId,
        options: { zoom: options.zoom, maxZoom: options.maxZoom },
        layers: [...options.layers],
        fitted: null as { padding: [number, number] } | null,
        view: null as { center: [number, number]; zoom: number } | null,
        addLayer(layer: unknown): void {
          this.layers.push(layer);
        },
        fitBounds(_bounds: unknown, options: { padding: [number, number] }): void {
          this.fitted = options;
        },
        setView(center: [number, number], zoom: number): void {
          this.view = { center, zoom };
        },
      };
      recorded.map = map;
      return map;
    },
    markerClusterGroup: (): unknown => markerGroup,
    marker: (position: [number, number]): StubMarker => {
      const marker = {
        position,
        popup: null as string | null,
        bindPopup(content: string): StubMarker {
          this.popup = content;
          return this;
        },
      };
      return marker;
    },
  };

  return recorded;
};

/**
 * Puts the markup in the document and draws the map on it.
 *
 * Node hands every test of this file the same module instance, and the module
 * draws the map when it is evaluated. The first test is the one that evaluates
 * it, and every test after it starts the module through the exported
 * initialiser.
 */
const draw = async (markup: string): Promise<Recorded> => {
  resetBody(markup);
  const recorded = installLeafletStub();
  const { initializeMap } = await import(SPECIFIER);
  initializeMap();
  return recorded;
};

const ONE_PARTNER =
  '<ul id="map-partners">' +
  '<li class="map-partner" data-lat="47.195131" data-lng="8.526731"' +
  ' data-name="TYPO3 Association" data-link="/partner/typo3-association">' +
  "<span>TYPO3 Association</span></li>" +
  "</ul>";

const DEFAULT_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
const DEFAULT_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ';

describe("the partner map", () => {
  it("draws itself when the module is evaluated after parsing finished", async () => {
    resetBody(
      '<div id="map"></div>' +
        '<ul id="map-partners">' +
        '<li class="map-partner" data-lat="47.195131" data-lng="8.526731"' +
        ' data-name="TYPO3 Association" data-link="/partner/typo3-association">' +
        "<span>TYPO3 Association</span></li>" +
        // Never geocoded. "Number('')" is 0, not NaN, so this used to be drawn
        // at 0/0 instead of being skipped (ACE-562).
        '<li class="map-partner" data-lat="" data-lng=""' +
        ' data-name="Without Coordinates" data-link="/partner/without-coordinates">' +
        "<span>Without Coordinates</span></li>" +
        // A zero pair in a spelling the SQL rule does not catch: the query matches
        // the literal "0" the command writes, so this one reaches the module.
        '<li class="map-partner" data-lat="0.0" data-lng="0"' +
        ' data-name="Null Island" data-link="/partner/null-island">' +
        "<span>Null Island</span></li>" +
        // A single zero is a real coordinate: this one is on the prime meridian
        // and has to survive the check that removes the two above.
        '<li class="map-partner" data-lat="51.477928" data-lng="0"' +
        ' data-name="Royal Observatory" data-link="/partner/royal-observatory">' +
        "<span>Royal Observatory</span></li>" +
        "</ul>",
    );

    // The state the defect needs. A module that waits for "DOMContentLoaded"
    // unconditionally waits here for an event that has already fired.
    assert.notEqual(document.readyState, "loading");

    // The module reports an unusable record on the console. Captured rather than
    // silenced, so the test can assert it still happens.
    const warnings: string[] = [];
    /* eslint-disable no-console -- capturing the module's own report is the point. */
    const originalWarn = console.warn;
    console.warn = (...args: unknown[]): void => {
      warnings.push(String(args[1]));
    };
    /* eslint-enable no-console */

    const recorded = installLeafletStub();

    try {
      await import(SPECIFIER);
    } finally {
      // eslint-disable-next-line no-console
      console.warn = originalWarn;
    }

    assert.ok(recorded.map !== null, "the map was never created");
    assert.equal(recorded.map.elementId, "map");
    assert.equal(recorded.markers.length, 2);
    assert.deepEqual(recorded.markers[0].position, [47.195131, 8.526731]);
    assert.match(recorded.markers[0].popup ?? "", /TYPO3 Association/);
    assert.deepEqual(recorded.markers[1].position, [51.477928, 0]);
    assert.match(recorded.markers[1].popup ?? "", /Royal Observatory/);
    assert.deepEqual(recorded.map.fitted, { padding: [50, 50] });

    // Both unusable entries were reported rather than silently dropped.
    assert.equal(warnings.length, 2);
    assert.deepEqual(warnings, ["Without Coordinates", "Null Island"]);
  });

  it("uses the values it always had when the map carries no configuration", async () => {
    // What an overridden template renders that predates the configuration.
    const recorded = await draw('<div id="map"></div><ul id="map-partners"></ul>');

    assert.ok(recorded.map !== null && recorded.tiles !== null);
    assert.equal(recorded.tiles.urlTemplate, DEFAULT_TILE_URL);
    assert.deepEqual(recorded.tiles.options, { maxZoom: 18, attribution: DEFAULT_ATTRIBUTION });
    assert.deepEqual(recorded.map.options, { zoom: 6, maxZoom: 18 });
    assert.deepEqual(recorded.map.view, { center: [51.1657, 10.4515], zoom: 6 });
  });

  it("centres a map without partners on the configured centre and zoom", async () => {
    const recorded = await draw(
      '<div id="map" data-academic-partners-center-lat="47.5162" data-academic-partners-center-lng="14.5501" data-academic-partners-zoom="10"></div>' +
        '<ul id="map-partners"></ul>',
    );

    assert.ok(recorded.map !== null);
    assert.equal(recorded.map.options.zoom, 10);
    assert.deepEqual(recorded.map.view, { center: [47.5162, 14.5501], zoom: 10 });
    assert.equal(recorded.map.fitted, null);
  });

  it("hands the maximum zoom, the padding, the tiles and the attribution to Leaflet", async () => {
    const recorded = await draw(
      '<div id="map" data-academic-partners-max-zoom="12" data-academic-partners-padding="20"' +
        ' data-academic-partners-tile-url="https://tiles.example.org/{z}/{x}/{y}.png"' +
        ' data-academic-partners-attribution="&lt;a href=&quot;https://tiles.example.org&quot;&gt;Example tiles&lt;/a&gt;"></div>' +
        ONE_PARTNER,
    );

    assert.ok(recorded.map !== null && recorded.tiles !== null);
    assert.equal(recorded.tiles.urlTemplate, "https://tiles.example.org/{z}/{x}/{y}.png");
    assert.deepEqual(recorded.tiles.options, {
      maxZoom: 12,
      attribution: '<a href="https://tiles.example.org">Example tiles</a>',
    });
    // On the map as well: the map caps "fitBounds" at its own maximum, which it
    // would otherwise take from the layers it happens to hold.
    assert.equal(recorded.map.options.maxZoom, 12);
    assert.deepEqual(recorded.map.fitted, { padding: [20, 20] });
    assert.equal(recorded.markers.length, 1);
  });

  it("keeps the value it always had for every attribute it cannot use", async () => {
    const recorded = await draw(
      // "Number('')" is 0 and "Number(' ')" too, so an empty value has to be
      // told apart from a written zero before the conversion.
      '<div id="map" data-academic-partners-zoom="far" data-academic-partners-max-zoom=""' +
        ' data-academic-partners-center-lat="95" data-academic-partners-center-lng="12"' +
        ' data-academic-partners-tile-url=" " data-academic-partners-attribution=""></div>' +
        '<ul id="map-partners"></ul>',
    );

    assert.ok(recorded.map !== null && recorded.tiles !== null);
    assert.equal(recorded.tiles.urlTemplate, DEFAULT_TILE_URL);
    assert.deepEqual(recorded.tiles.options, { maxZoom: 18, attribution: DEFAULT_ATTRIBUTION });
    assert.deepEqual(recorded.map.options, { zoom: 6, maxZoom: 18 });
    // The centre is one value: a latitude outside of the globe discards the
    // longitude next to it as well, rather than centring on a place nobody chose.
    assert.deepEqual(recorded.map.view, { center: [51.1657, 10.4515], zoom: 6 });
  });

  it("keeps the padding it always had for a negative one", async () => {
    // The padding is only read when there are partners to fit.
    const recorded = await draw('<div id="map" data-academic-partners-padding="-5"></div>' + ONE_PARTNER);

    assert.ok(recorded.map !== null);
    assert.deepEqual(recorded.map.fitted, { padding: [50, 50] });
  });

  it("uses a written zero", async () => {
    const withoutPartners = await draw(
      '<div id="map" data-academic-partners-zoom="0" data-academic-partners-max-zoom="0"' +
        ' data-academic-partners-center-lat="0" data-academic-partners-center-lng="0"></div>' +
        '<ul id="map-partners"></ul>',
    );

    assert.ok(withoutPartners.map !== null && withoutPartners.tiles !== null);
    assert.deepEqual(withoutPartners.map.options, { zoom: 0, maxZoom: 0 });
    assert.equal(withoutPartners.tiles.options.maxZoom, 0);
    assert.deepEqual(withoutPartners.map.view, { center: [0, 0], zoom: 0 });

    const withPartners = await draw('<div id="map" data-academic-partners-padding="0"></div>' + ONE_PARTNER);

    assert.ok(withPartners.map !== null);
    assert.deepEqual(withPartners.map.fitted, { padding: [0, 0] });
  });
});
