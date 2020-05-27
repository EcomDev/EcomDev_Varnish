

const path = require('path');

module.exports = {
    entry: './src/js/ecomdev/varnish/index.js',
    mode: 'production',
    output: {
        filename: 'varnish.bundle.js',
        path: path.resolve(__dirname, 'src/js/ecomdev'),
        library: 'VarnishBundle',
        libraryTarget: 'window',
        libraryExport: 'default'
    },
};