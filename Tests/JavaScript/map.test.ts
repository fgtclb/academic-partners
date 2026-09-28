import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { resetBody } from "../../../../../Build/tests/dom.mjs";
import { recorded, resetLeaflet } from "../../../../../Build/tests/stubs/leaflet.mjs";

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
 * "leaflet" and "leaflet.markercluster" resolve to the recording stubs of the
 * harness (Build/tests/stubs/). This file imports the Leaflet stub by its path,
 * which is the same module instance the map module receives, and reads what
 * the map asked of Leaflet from "recorded".
 */
const SPECIFIER = "@fgtclb/academic-partners/frontend/map.js";

/** The text of a popup, whether the map handed Leaflet an element or a string. */
const popupText = (popup: HTMLElement | string | null): string =>
  popup instanceof HTMLElement ? popup.textContent ?? "" : popup ?? "";

/**
 * Puts the markup in the document and draws the map on it.
 *
 * Node hands every test of this file the same module instance, and the module
 * draws the map when it is evaluated. The first test is the one that evaluates
 * it, and every test after it starts the module through the exported
 * initialiser.
 *
 * The recording is one object for the whole file, "recorded" of the stub. Each
 * draw resets it, so a test reads what it needs before it draws again.
 */
const draw = async (markup: string): Promise<typeof recorded> => {
  resetBody(markup);
  resetLeaflet();
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

    resetLeaflet();

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
    assert.match(popupText(recorded.markers[0].popup), /TYPO3 Association/);
    assert.deepEqual(recorded.markers[1].position, [51.477928, 0]);
    assert.match(popupText(recorded.markers[1].popup), /Royal Observatory/);
    assert.deepEqual(recorded.map.fitted, { padding: [50, 50] });
    // One cluster group, loading in chunks, on the map.
    assert.equal(recorded.clusterGroups.length, 1);
    assert.deepEqual(recorded.clusterGroups[0]?.options, { chunkedLoading: true });
    assert.ok(recorded.map.layers.includes(recorded.clusterGroups[0]));

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
    // One cluster group, loading in chunks, on the map.
    assert.equal(recorded.clusterGroups.length, 1);
    assert.deepEqual(recorded.clusterGroups[0]?.options, { chunkedLoading: true });
    assert.ok(recorded.map.layers.includes(recorded.clusterGroups[0]));
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

  it("opens a popup with the partner title in bold, linked to the partner page", async () => {
    const recorded = await draw('<div id="map"></div>' + ONE_PARTNER);

    const popup = recorded.markers[0]?.popup;
    assert.ok(popup instanceof HTMLElement, "the popup is not an element");
    assert.equal(popup.tagName, "A");
    assert.equal(popup.getAttribute("href"), "/partner/typo3-association");
    assert.equal(popup.firstElementChild?.tagName, "B");
    assert.equal(popup.textContent, "TYPO3 Association");
  });

  it("shows a partner title as it is written", async () => {
    // The attribute holds the title as Fluid writes it, and "dataset" hands the
    // module the title the editor typed.
    const recorded = await draw(
      '<div id="map"></div>' +
        '<ul id="map-partners">' +
        '<li class="map-partner" data-lat="47.195131" data-lng="8.526731"' +
        ' data-name="Smith &amp; Sons &lt;Ltd&gt;" data-link="/partner/smith-sons">' +
        "<span>Smith &amp; Sons &lt;Ltd&gt;</span></li>" +
        "</ul>",
    );

    const popup = recorded.markers[0]?.popup;
    assert.ok(popup instanceof HTMLElement, "the popup is not an element");
    assert.equal(popup.textContent, "Smith & Sons <Ltd>");
    assert.equal(popup.firstElementChild?.childElementCount, 0);
  });

  it("takes the marker images from the directory the map names", async () => {
    const recorded = await draw(
      '<div id="map" data-academic-partners-marker-icon="/_assets/1a2b/Images/Map/marker-icon.png?1790000000"></div>' +
        ONE_PARTNER,
    );

    assert.equal(recorded.markers[0]?.icon?.options.imagePath, "/_assets/1a2b/Images/Map/");
  });

  it("leaves the marker images to Leaflet when the map names none", async () => {
    const recorded = await draw('<div id="map"></div>' + ONE_PARTNER);

    assert.equal(recorded.markers[0]?.icon, null);
  });
});
