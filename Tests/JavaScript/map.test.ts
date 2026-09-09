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

interface StubMap {
  readonly elementId: string;
  readonly layers: unknown[];
  readonly fitted: { padding: [number, number] } | null;
  readonly view: [number, number] | null;
}

const installLeafletStub = (): { map: StubMap | null; markers: StubMarker[] } => {
  const recorded: { map: StubMap | null; markers: StubMarker[] } = {
    map: null,
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
    tileLayer: (): object => ({ tiles: true }),
    map: (elementId: string, options: { layers: unknown[] }): StubMap => {
      const map = {
        elementId,
        layers: [...options.layers],
        fitted: null as { padding: [number, number] } | null,
        view: null as [number, number] | null,
        addLayer(layer: unknown): void {
          this.layers.push(layer);
        },
        fitBounds(_bounds: unknown, options: { padding: [number, number] }): void {
          this.fitted = options;
        },
        setView(center: [number, number]): void {
          this.view = center;
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

describe("the partner map", () => {
  it("draws itself when the module is evaluated after parsing finished", async () => {
    resetBody(
      '<div id="map"></div>' +
        '<ul id="map-partners">' +
        '<li class="map-partner" data-lat="47.195131" data-lng="8.526731"' +
        ' data-name="TYPO3 Association" data-link="/partner/typo3-association">' +
        "<span>TYPO3 Association</span></li>" +
        "</ul>",
    );

    // The state the defect needs. A module that waits for "DOMContentLoaded"
    // unconditionally waits here for an event that has already fired.
    assert.notEqual(document.readyState, "loading");

    const recorded = installLeafletStub();

    await import("@fgtclb/academic-partners/frontend/map.js");

    assert.ok(recorded.map !== null, "the map was never created");
    assert.equal(recorded.map.elementId, "map");
    assert.equal(recorded.markers.length, 1);
    assert.deepEqual(recorded.markers[0].position, [47.195131, 8.526731]);
    assert.match(recorded.markers[0].popup ?? "", /TYPO3 Association/);
    assert.deepEqual(recorded.map.fitted, { padding: [50, 50] });
  });
});
