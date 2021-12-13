const Encore = require('@symfony/webpack-encore');
const path = require('path');
let childName = 'app-bundle';

Encore.reset();

Encore.configureDefinePlugin((options) => {
    options.__DEV__ = JSON.stringify(Encore.isDev());
});

Encore.setOutputPath('web/assets/build')
    .setPublicPath('/assets/build')
    .disableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .splitEntryChunks()
    .enableSassLoader((config) => {
        config.data =
            '$imgs-path: "/assets/build/images";' +
            '$fonts-path: "/assets/build/fonts";';
    })
    .autoProvidejQuery()
    .addAliases({
        __JS_ROOT__: path.resolve(__dirname, '../public/js'),
    })
    .addStyleEntry(childName + '-css-common', [
        path.resolve(__dirname, '../public/sass/layouts/common.scss'),
    ])
    .addStyleEntry(childName + '-css-homepage', [
        path.resolve(__dirname, '../public/sass/layouts/homepage.scss'),
    ])
    .addEntry(childName + '-js-common', [
        path.resolve(__dirname, '../public/js/layouts/common.js'),
    ])
    .cleanupOutputBeforeBuild()

const appBundleConfig = Encore.getWebpackConfig();

appBundleConfig.name = childName;

// Config or array of configs: [customConfig1, customConfig2];
module.exports = appBundleConfig;
