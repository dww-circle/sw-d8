// Include gulp.
var gulp = require('gulp');

// Include plugins.
var _ = require('lodash');
var browserSync = require('browser-sync').create();
var sass = require('gulp-sass');
var plumber = require('gulp-plumber');
var notify = require('gulp-notify');
var autoprefix = require('gulp-autoprefixer');
var glob = require('gulp-sass-glob');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');

// Get default config.
var config = require('./config.json');

// Merge with local config.
try {
  var local_config = require('./config.local.json');
  config = _.merge(config, local_config);
}
catch (e) {
  // Do nothing.
}

// CSS.
gulp.task('css', function() {
  return gulp.src(config.css.src)
    .pipe(glob())
    .pipe(plumber({
      errorHandler: function (error) {
        notify.onError({
          title:    "Gulp",
          subtitle: "Failure!",
          message:  "Error: <%= error.message %>",
          sound:    "Beep"
        }) (error);
        this.emit('end');
      }}))
    .pipe(sourcemaps.init())
    .pipe(sass({
      style: 'compressed',
      errLogToConsole: true,
      includePaths: config.css.includePaths
    }))
    .pipe(autoprefix(config.autoprefixerVersions))
    .pipe(sourcemaps.write(config.css.sourceMapDest))
    .pipe(gulp.dest(config.css.dest))
    .pipe(browserSync.stream());

});

gulp.task('js', function(){
  return gulp.src(config.js.src)
    .pipe(plumber({
      errorHandler: function (error) {
        console.log(error.message);
        this.emit('end');
      }}))
    .pipe(concat(config.js.finalfile))
    .pipe(gulp.dest(config.js.dest))
    .pipe(rename({suffix: '.min'}))
    .pipe(uglify())
    .pipe(gulp.dest(config.js.dest))
    .pipe(browserSync.reload({stream:true}))
});

// Static Server + Watch
gulp.task('serve', ['css', 'js'], function() {
  browserSync.init({
    proxy: config.bs.proxy,
  });

  gulp.watch(config.js.src, ['js']);
  gulp.watch(config.css.src, ['css']);
  gulp.watch(config.css.dest, ['css']).on('change', browserSync.reload);
  gulp.watch(config.js.watch, ['js']).on('change', browserSync.reload);
  
});

// Default Task
gulp.task('default', ['serve']);
