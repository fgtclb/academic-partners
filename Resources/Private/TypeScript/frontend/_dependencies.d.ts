// Ambient declarations for the libraries this extension does not own.
//
// "leaflet" and "leaflet.markercluster" are the ES modules of Leaflet and of
// its marker cluster plugin, built from their npm packages into
// "Resources/Public/JavaScript/vendor/" and published by the extension's
// Configuration/JavaScriptModules.php (see Build/vendor.mjs).
//
// Declared with exactly the surface the partner map calls, and no more: a
// declaration is the contract a library update is checked against, and a member
// nothing imports is a claim nobody verifies.

declare module "leaflet" {
  export interface LatLngBounds {
    readonly _southWest?: unknown;
  }

  export interface Marker {
    bindPopup(content: HTMLElement | string): Marker;
  }

  export interface Map {
    addLayer(layer: unknown): void;
    fitBounds(bounds: LatLngBounds, options: { padding: [number, number] }): void;
    setView(center: [number, number], zoom: number): void;
  }

  export function tileLayer(urlTemplate: string, options: { maxZoom: number; attribution: string }): unknown;
  export function map(elementId: string, options: { zoom: number; maxZoom: number; layers: unknown[] }): Map;
  export class Icon {
    /** Leaflet's default marker icon, which takes the directory of its images. */
    static Default: new (options?: { imagePath?: string }) => Icon;
  }

  export function marker(position: [number, number], options?: { icon?: Icon }): Marker;
}

declare module "leaflet.markercluster" {
  import type { LatLngBounds, Marker } from "leaflet";

  export class MarkerClusterGroup {
    constructor(options: { chunkedLoading: boolean });
    addLayer(marker: Marker): void;
    getLayers(): unknown[];
    getBounds(): LatLngBounds;
  }
}
