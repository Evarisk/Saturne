'use strict';

/**
 * Build the compiled assets of a Saturne module: css/<module>.min.css and js/<module>.min.js.
 *
 * Replaces the gulp 4 chain, unmaintained since 2019, by the two tools that actually do the
 * work: sass compiles the stylesheet, esbuild minifies both outputs. See Saturne#1613.
 *
 * Usage:
 *   node build-assets.js --module=saturne
 *   node build-assets.js --module=saturne --watch
 *   MODULE_NAME=saturne node build-assets.js        (form kept for the CI and for habit)
 */

const fs      = require('fs');
const path    = require('path');
const sass    = require('sass');
const esbuild = require('esbuild');

const args       = process.argv.slice(2);
const watchMode  = args.includes('--watch');
const moduleArg  = args.find(arg => arg.startsWith('--module='));
const moduleName = moduleArg ? moduleArg.slice('--module='.length) : process.env.MODULE_NAME;

if (!moduleName) {
    console.error('Module name is required.\nUsage: node build-assets.js --module=mymodule [--watch]');
    process.exit(1);
}

// Resolve the module root as an absolute path so the same script works in both layouts
// without depending on cwd:
//
//   Sibling layout (local dev):
//     __dirname = .../custom/saturne
//     module    = .../custom/{moduleName}   <- exists one level up
//
//   Subfolder layout (CI - saturne checked out as .saturne inside the module):
//     __dirname = .../custom/{moduleName}/.saturne
//     module    = .../custom/{moduleName}   <- parent of __dirname
//
let moduleRoot;
if (moduleName === 'saturne') {
    moduleRoot = __dirname;
} else {
    const siblingPath = path.join(__dirname, '..', moduleName);
    moduleRoot = fs.existsSync(siblingPath) ? siblingPath : path.join(__dirname, '..');
}

const scssEntry = path.join(moduleRoot, 'css/scss/style.scss');
const scssDir   = path.join(moduleRoot, 'css/scss');
const jsEntry   = path.join(moduleRoot, 'js/' + moduleName + '.js');
const jsDir     = path.join(moduleRoot, 'js/modules');

/**
 * The JS files to concatenate, in an order that does not depend on the machine.
 *
 * The order of concatenation decides the bytes of the bundle. Sorting it with a locale aware
 * comparator, as the glob of gulp used to, made the same sources compile to two different
 * files depending on the ICU version of the machine (Saturne#1607). Array.prototype.sort()
 * without a comparator sorts by UTF-16 code point: same result everywhere.
 *
 * @return {string[]} Absolute paths, the module entry point first, empty when there is no JS
 */
function jsSources() {
    const sources = fs.existsSync(jsEntry) ? [jsEntry] : [];
    if (fs.existsSync(jsDir)) {
        fs.readdirSync(jsDir)
            .filter(file => file.endsWith('.js'))
            .sort()
            .forEach(file => sources.push(path.join(jsDir, file)));
    }

    return sources;
}

/**
 * Compile css/scss/style.scss into css/<module>.min.css.
 *
 * Only style.scss is compiled, the other files of css/scss/ being partials it imports. The
 * gulp chain used to hand every non partial file to sass and write them all to the same
 * destination, where the last one won - harmless as long as there is exactly one entry point,
 * which is the case in every module, but not something to carry over.
 *
 * @return {Promise<string>} What was done, for the console
 */
async function buildCss() {
    if (!fs.existsSync(scssEntry)) {
        return 'css: skipped, no css/scss/style.scss';
    }

    const compiled  = sass.compile(scssEntry, { loadPaths: [scssDir], style: 'expanded' });
    const minified  = await esbuild.transform(compiled.css, { loader: 'css', minify: true, legalComments: 'none' });
    const target    = path.join(moduleRoot, 'css/' + moduleName + '.min.css');

    fs.writeFileSync(target, minified.code);

    return 'css: ' + path.basename(target) + ' (' + minified.code.length + ' bytes)';
}

/**
 * Concatenate and minify the JS into js/<module>.min.js.
 *
 * The files are plain scripts assigning to globals, not modules: they are concatenated as text
 * and handed to esbuild as a single file. Bundling them would be wrong, there is nothing to
 * resolve, and it would rename the globals the PHP side relies on.
 *
 * @return {Promise<string>} What was done, for the console
 */
async function buildJs() {
    const sources = jsSources();
    if (sources.length === 0) {
        return 'js: skipped, no js/' + moduleName + '.js nor js/modules/';
    }

    const concatenated = sources.map(source => fs.readFileSync(source, 'utf8')).join('\n');
    const minified     = await esbuild.transform(concatenated, { loader: 'js', minify: true, legalComments: 'none' });
    const target       = path.join(moduleRoot, 'js/' + moduleName + '.min.js');

    fs.writeFileSync(target, minified.code);

    return 'js: ' + path.basename(target) + ' (' + sources.length + ' files, ' + minified.code.length + ' bytes)';
}

/**
 * Compile both assets and report, never leaving a half written pair behind
 *
 * @return {Promise<void>}
 */
async function build() {
    const started = Date.now();

    const results = await Promise.all([buildCss(), buildJs()]);
    results.forEach(result => console.log('  ' + result));

    console.log('  done in ' + (Date.now() - started) + ' ms');
}

build().then(() => {
    if (!watchMode) {
        return;
    }

    console.log('watching ' + moduleName + '...');

    // Debounced: an editor saving a file often fires several events, and a rebuild triggered
    // while sass is still reading the previous one would compile a half written stylesheet
    let pending = null;
    const rebuild = () => {
        clearTimeout(pending);
        pending = setTimeout(() => {
            build().catch(error => console.error(error.message));
        }, 100);
    };

    [scssDir, path.join(moduleRoot, 'js')].filter(fs.existsSync).forEach(directory => {
        fs.watch(directory, { recursive: true }, (eventType, filename) => {
            // The compiled files live inside the watched directories: rebuilding on their own
            // write would loop forever
            if (filename && !filename.includes('.min.')) {
                rebuild();
            }
        });
    });
}).catch(error => {
    console.error(error.message);
    process.exit(1);
});
