var argv         = require('minimist')(process.argv.slice(2));
var autoprefixer = require('gulp-autoprefixer');
var changed      = require('gulp-changed');
var concat       = require('gulp-concat');
var del          = require('del');
var download     = require('gulp-download-stream');
var flatten      = require('gulp-flatten');
var gulp         = require('gulp');
var gulpif       = require('gulp-if');
var imagemin     = require('gulp-imagemin');
var jshint       = require('gulp-jshint');
var lazypipe     = require('lazypipe');
var merge        = require('merge-stream');
var cssNano      = require('gulp-cssnano');
var plumber      = require('gulp-plumber');
var rev          = require('gulp-rev');
var runSequence  = require('run-sequence');
var sass         = require('gulp-sass');
var sourcemaps   = require('gulp-sourcemaps');
var uglify       = require('gulp-uglify');

var manifest = require('asset-builder')('./assets/src/manifest.json');
var path = manifest.paths;
var globs = manifest.globs;
var project = manifest.getProjectGlobs();

var enabled = {
  // Enable static asset revisioning when `--production`
  rev: argv.production,
  // Disable source maps when `--production`
  maps: !argv.production,
  // Fail styles task on error when `--production`
  failStyleTask: argv.production,
  // Fail due to JSHint warnings only when `--production`
  failJSHint: argv.production,
  // Strip debug statments from javascript when `--production`
  stripJSDebug: argv.production
};

var revManifest = path.dist + 'assets.json';

var cssTasks = function(filename) {
  return lazypipe()
    .pipe(function() {
      return gulpif(!enabled.failStyleTask, plumber());
    })
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.init());
    })
    .pipe(function() {
      return gulpif('*.scss', sass({
        outputStyle: 'nested', // libsass doesn't support expanded yet
        precision: 10,
        includePaths: ['.'],
        errLogToConsole: !enabled.failStyleTask
      }));
    })
    .pipe(concat, filename)
    .pipe(autoprefixer, {
      browsers: [
        'last 2 versions',
        'android 4',
        'opera 12'
      ]
    })
    .pipe(cssNano, {
      safe: true
    })
    .pipe(function() {
      return gulpif(enabled.rev, rev());
    })
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.write('.', {
        sourceRoot: 'assets/src/styles/'
      }));
    })();
};

var jsTasks = function(filename) {
  return lazypipe()
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.init());
    })
    .pipe(concat, filename)
    .pipe(uglify, {
      compress: {
        'drop_debugger': enabled.stripJSDebug
      }
    })
    .pipe(function() {
      return gulpif(enabled.rev, rev());
    })
    .pipe(function() {
      return gulpif(enabled.maps, sourcemaps.write('.', {
        sourceRoot: 'assets/src/scripts/'
      }));
    })();
};

var writeToManifest = function(directory) {
  return lazypipe()
    .pipe(gulp.dest, path.dist + directory)
    .pipe(rev.manifest, revManifest, {
      base: path.dist,
      merge: true
    })
    .pipe(gulp.dest, path.dist)();
};

gulp.task('download-tmp', function () {
  return download([
      'https://raw.githubusercontent.com/WordPress/WordPress/master/wp-admin/css/colors/_admin.scss',
      'https://raw.githubusercontent.com/WordPress/WordPress/master/wp-admin/css/colors/_variables.scss',
      'https://raw.githubusercontent.com/WordPress/WordPress/master/wp-admin/css/colors/_mixins.scss',
      ])
    .pipe(gulp.dest('assets/src/styles/partials/tmp/'));
});

gulp.task('styles', ['wiredep'], function() {
  var merged = merge();
  manifest.forEachDependency('css', function(dep) {
    var cssTasksInstance = cssTasks(dep.name);
    if (!enabled.failStyleTask) {
      cssTasksInstance.on('error', function(err) {
        console.error(err.message);
        this.emit('end');
      });
    }
    merged.add(gulp.src(dep.globs, {base: 'styles'})
      .pipe(cssTasksInstance));
  });
  return merged
    .pipe(writeToManifest('styles'));
});

gulp.task('scripts', ['jshint'], function() {
  var merged = merge();
  manifest.forEachDependency('js', function(dep) {
    merged.add(
      gulp.src(dep.globs, {base: 'scripts'})
        .pipe(jsTasks(dep.name))
    );
  });
  return merged
    .pipe(writeToManifest('scripts'));
});

gulp.task('fonts', function() {
  return gulp.src(globs.fonts)
    .pipe(flatten())
    .pipe(gulp.dest(path.dist + 'fonts'));
});

gulp.task('images', function() {
  return gulp.src(globs.images)
    .pipe(imagemin({
      progressive: true,
      interlaced: true,
      svgoPlugins: [{removeUnknownsAndDefaults: false}, {cleanupIDs: false}]
    }))
    .pipe(gulp.dest(path.dist + 'images'));
});

gulp.task('wiredep', function() {
  var wiredep = require('wiredep').stream;
  return gulp.src(project.css)
    .pipe(wiredep())
    .pipe(changed(path.source + 'styles', {
      hasChanged: changed.compareSha1Digest
    }))
    .pipe(gulp.dest(path.source + 'styles'));
});

gulp.task('jshint', function() {
  return gulp.src([
    'bower.json', 'gulpfile.js'
  ].concat(project.js))
    .pipe(jshint())
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(gulpif(enabled.failJSHint, jshint.reporter('fail')));
});

gulp.task('delete-tmp', function () {
  return del('./assets/src/styles/partials/tmp');
});

gulp.task('clean', require('del').bind(null, [path.dist]));

gulp.task('build', function() {
  runSequence(
    'download-tmp',
    'styles',
    'scripts',
    'fonts',
    'images',
    'delete-tmp'
  );
});

gulp.task('default', ['clean'], function() {
  gulp.start('build');
});
