const config = require('flarum-webpack-config');
const webpack = require('webpack');

// Polyfill for Flarum 1.x compatibility.
// flarum-webpack-config v3.x generates flarum.reg.get(namespace, path) calls (Flarum 2.x API).
// This shim creates flarum.reg on 1.x by mapping paths to flarum.core.compat entries.
// Note: kept as a named IIFE to avoid Terser variable-name collisions with the webpack bundle.
const FLARUM_1X_POLYFILL = `;(function flarumRegPolyfill() {
  if (typeof flarum === 'undefined') return;
  if (flarum.reg && flarum.reg.get) return;
  if (!flarum.core || !flarum.core.compat) return;
  var compat = flarum.core.compat;
  function resolve(ns, id) {
    // v3.x calls get("core","admin/app") - combine to try as "core/admin/app" or just "id"
    var full = ns && id ? ns + '/' + id : (id || ns);
    if (compat[full]) return compat[full];
    // Try "flarum/id" (e.g. "flarum/admin/app")
    var withFlarum = 'flarum/' + (id || ns);
    if (compat[withFlarum]) return compat[withFlarum];
    // Strip namespace prefix (admin|forum|common) from path
    var short = (id || ns).replace(/^(admin|forum|common)\\//, '');
    if (compat[short]) return compat[short];
    // Try "flarum/short"
    if (compat['flarum/' + short]) return compat['flarum/' + short];
    return undefined;
  }
  flarum.reg = { get: resolve };
})();`;

const cfg = config();
cfg.plugins = cfg.plugins || [];
cfg.plugins.push(
  new webpack.BannerPlugin({ banner: FLARUM_1X_POLYFILL, raw: true, entryOnly: false })
);

module.exports = cfg;
