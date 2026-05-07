'use strict';

const path       = require('path');
const fs         = require('fs');
const gulp       = require('gulp');
const sass       = require('gulp-sass')(require('sass'));
const rename     = require('gulp-rename');
const uglify     = require('gulp-uglify');
const concat     = require('gulp-concat');
const cleanCSS   = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');

const moduleName = process.env.MODULE_NAME;

if (!moduleName) {
    throw new Error(
        'MODULE_NAME environment variable is required.\n' +
        'Usage: MODULE_NAME=mymodule node node_modules/gulp/bin/gulp.js --gulpfile gulpfile-shared.js build'
    );
}

// Resolve the module root as an absolute path so the same gulpfile works in
// both layouts without depending on cwd:
//
//   Sibling layout (local dev):
//     __dirname = .../custom/saturne
//     module    = .../custom/{moduleName}   ← exists one level up
//
//   Subfolder layout (CI — saturne checked out as .saturne inside the module):
//     __dirname = .../custom/{moduleName}/.saturne
//     module    = .../custom/{moduleName}   ← parent of __dirname
//
let moduleRoot;
if (moduleName === 'saturne') {
    moduleRoot = __dirname;
} else {
    const siblingPath = path.join(__dirname, '..', moduleName);
    moduleRoot = fs.existsSync(siblingPath) ? siblingPath : path.join(__dirname, '..');
}

const paths = {
    scss_core:  [path.join(moduleRoot, 'css/scss/**/*.scss'), path.join(moduleRoot, 'css/')],
    js_backend: [path.join(moduleRoot, 'js/' + moduleName + '.js'), path.join(moduleRoot, 'js/modules/*.js')]
};

/** SCSS — dev : sourcemaps inline, minifié */
gulp.task('scss_core', function() {
    return gulp.src(paths.scss_core[0])
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(rename('./' + moduleName + '.min.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.scss_core[1]));
});

/** SCSS — prod : clean-css, pas de sourcemaps */
gulp.task('scss_core:prod', function() {
    return gulp.src(paths.scss_core[0])
        .pipe(sass().on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(rename('./' + moduleName + '.min.css'))
        .pipe(gulp.dest(paths.scss_core[1]));
});

/** JS — concat + uglify (dev et prod) */
gulp.task('js_backend', function() {
    return gulp.src(paths.js_backend)
        .pipe(concat(moduleName + '.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(path.join(moduleRoot, 'js/')));
});

/** Build prod — one-shot, minifié, sans sourcemaps */
gulp.task('build', gulp.parallel('scss_core:prod', 'js_backend'));

/** Watch dev — compile initial puis surveille */
gulp.task('watch', function() {
    gulp.watch(paths.scss_core[0], gulp.series('scss_core'));
    gulp.watch(paths.js_backend,   gulp.series('js_backend'));
});

/** Default = build dev initial + watch */
gulp.task('default', gulp.series(
    gulp.parallel('scss_core', 'js_backend'),
    'watch'
));