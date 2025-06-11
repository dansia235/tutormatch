const Encore = require('@symfony/webpack-encore');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/tutoring/public/build')
    // only needed for CDN's or subdirectory deploy
    .setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/js/app.js')
    .addEntry('admin', './assets/js/admin.js')
    .addEntry('student', './assets/js/student.js')
    .addEntry('tutor', './assets/js/tutor.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://github.com/symfony/webpack-encore/blob/master/doc/features.md
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // Configure performance optimizations
    .configurePerformance((config) => {
        if (Encore.isProduction()) {
            // Set optimization level
            config.hints = 'warning'; // 'warning', 'error' or false
            config.maxAssetSize = 250000; // in bytes
            config.maxEntrypointSize = 400000; // in bytes
        } else {
            // Disable performance hints in development
            config.hints = false;
        }
    })

    // Configure Babel
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // Enable code splitting for dynamic imports
    .configureSplitChunks(splitChunks => {
        splitChunks.cacheGroups = {
            vendors: {
                test: /[\\/]node_modules[\\/]/,
                name: 'vendors',
                chunks: 'all',
                priority: 1
            },
            controllers: {
                test: /[\\/]controllers[\\/]/,
                name: 'controllers',
                chunks: 'all',
                priority: 0
            }
        };
    })

    // Enable PostCSS processing for Tailwind CSS
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, 'postcss.config.js')
        };
    })
    
    // Add image optimization in production
    .copyFiles({
        from: './assets/img',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
    })
    
    // Enable terser for better minification
    .configureTerserPlugin((options) => {
        options.terserOptions = {
            compress: {
                drop_console: Encore.isProduction(),
                drop_debugger: Encore.isProduction()
            },
            output: {
                comments: false
            }
        };
    })
;

module.exports = Encore.getWebpackConfig();