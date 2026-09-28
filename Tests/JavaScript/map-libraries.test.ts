import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { describe, it } from "node:test";
import { runClassicScripts } from "../../../../../Build/tests/dom.mjs";

/**
 * The two classic scripts the partner map loads before its module: Leaflet and its
 * marker cluster plugin, built from their npm packages by "Build/vendor.mjs".
 *
 * Unlike every other test of this suite, this one reads the built files rather than
 * a source: the files are the source here, taken from the pinned packages, and
 * "checkJsBuildClean" keeps them equal to what the build writes. They run in order,
 * in a window of their own that evaluates classic scripts in its global scope, as a
 * browser does.
 */
interface Leaflet {
  readonly version: string;
  readonly SVG: { pointsToPath(rings: Array<Array<{ x: number; y: number }>>, closed: boolean): string };
  readonly MarkerClusterGroup?: unknown;
  readonly markerClusterGroup?: unknown;
  Marker: unknown;
  noConflict(): Leaflet;
}

const script = (name: string): string =>
  readFileSync(new URL(`../../Resources/Public/JavaScript/${name}`, import.meta.url), "utf8");

interface Loaded {
  readonly window: Window & { eval(code: string): unknown };
  /** The globals of that window, which the scripts publish. */
  readonly globals: Record<string, unknown>;
}

const load = (...before: string[]): Loaded => {
  const window = runClassicScripts(...before, script("leaflet.js"), script("markerCluster.js"));

  return { window, globals: window as unknown as Record<string, unknown> };
};

describe("the map libraries", () => {
  it("publish Leaflet as LeafletObject and leaflet, and nothing as L", () => {
    const { globals } = load();
    const leaflet = globals.LeafletObject as Leaflet | undefined;

    assert.ok(leaflet !== undefined, "LeafletObject was not published");
    assert.equal(leaflet.version, "1.9.4");
    assert.equal(globals.leaflet, leaflet);
    assert.equal(globals.L, undefined);
  });

  it("leave a Leaflet the page already has as L alone", () => {
    const { globals } = load('window.L = "another Leaflet";');

    assert.equal(globals.L, "another Leaflet");
    assert.equal((globals.LeafletObject as Leaflet).version, "1.9.4");
  });

  it("add the marker cluster group to the same Leaflet", () => {
    const { globals } = load();
    const leaflet = globals.LeafletObject as Leaflet;
    const plugin = (globals.Leaflet as { markercluster?: { MarkerClusterGroup?: unknown } } | undefined)?.markercluster;

    assert.equal(typeof leaflet.markerClusterGroup, "function");
    assert.equal(typeof leaflet.MarkerClusterGroup, "function");
    assert.equal(plugin?.MarkerClusterGroup, leaflet.MarkerClusterGroup);
  });

  it("keep the members of LeafletObject writable, for a plugin of a project", () => {
    const { window, globals } = load();

    window.eval('"use strict"; LeafletObject.Marker = "replaced";');

    assert.equal((globals.LeafletObject as Leaflet).Marker, "replaced");
  });

  it("hand the global back with noConflict", () => {
    const { globals } = load('window.LeafletObject = "before";');
    const leaflet = globals.LeafletObject as Leaflet;

    assert.equal(leaflet.noConflict(), leaflet);
    assert.equal(globals.LeafletObject, "before");
  });

  it("run in strict mode, as the builds they replace did", () => {
    for (const name of ["leaflet.js", "markerCluster.js"]) {
      const code = script(name);
      assert.ok(
        code.indexOf('"use strict";') !== -1 && code.indexOf('"use strict";') < code.indexOf("(()=>"),
        `${name} does not start in strict mode`,
      );
    }
  });

  it("write the line command of an SVG path as L", () => {
    const leaflet = load().globals.LeafletObject as Leaflet;

    assert.equal(
      leaflet.SVG.pointsToPath(
        [
          [
            { x: 1, y: 2 },
            { x: 3, y: 4 },
          ],
        ],
        false,
      ),
      "M1 2L3 4",
    );
  });
});
