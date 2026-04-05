const config = require('flarum-webpack-config');
const webpack = require('webpack');

// Polyfill for Flarum 1.x compatibility.
// flarum-webpack-config v3.x generates flarum.reg.get() calls (Flarum 2.x API).
// This shim creates flarum.reg on 1.x by mapping 2.x-style paths to flarum.core.compat entries.
const FLARUM_1X_POLYFILL = `
if (typeof flarum !== 'undefined' && (!flarum.reg || !flarum.reg.get) && flarum.core && flarum.core.compat) {
  (function() {
    var compat = flarum.core.compat;
    flarum.reg = {
      get: function(id) {
        if (compat[id]) return compat[id];
        var stripped = id.replace(/^flarum\\/(admin|forum|common)\\//, 'flarum/');
        if (compat[stripped]) return compat[stripped];
        var short = id.replace(/^flarum\\//, '').replace(/^(admin|forum|common)\\//, '');
        return compat[short] || undefined;
      }
    };
  })();
}`.trim();

const cfg = config();
cfg.plugins = cfg.plugins || [];
cfg.plugins.push(
  new webpack.BannerPlugin({ banner: FLARUM_1X_POLYFILL, raw: true, entryOnly: false })
);

module.exports = cfg;
