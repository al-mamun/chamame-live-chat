var gulp = require('gulp');
var merge = require('event-stream').merge;
var sass = require('gulp-ruby-sass');
var autoprefixer = require('gulp-autoprefixer');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var footer = require('gulp-footer');
var stripDebug = require('gulp-strip-debug');
var jshint = require('gulp-jshint');
var csslint = require('gulp-csslint');
var scsslint = require('gulp-scss-lint');
var saveLicense = require('uglify-save-license');
var runSequence = require('run-sequence');

gulp.task('css', function() {
  return merge(
    gulp.src('css/sass/client/client.scss')
    .pipe(sass({
      sourcemap: true,
      style: 'compressed'
    }))
    .pipe(autoprefixer('last 2 versions', 'ie 9', 'ios 6', 'android 4'))
    .pipe(gulp.dest( 'css/' ))
    .on('end', function() {
      gulp.src('css/client.css')
      .pipe(footer('/*@ sourceMappingURL=client.css.map */'))
      .pipe(gulp.dest( 'css/' ))
    }),

    gulp.src('css/sass/admin/admin.scss')
    .pipe(sass({
      sourcemap: true,
      style: 'compressed'
    }))
    .pipe(autoprefixer('last 2 versions', 'ie 9', 'ios 6', 'android 4'))
    .pipe(gulp.dest('css/'))
    .on('end', function() {
      gulp.src('css/admin.css')
      .pipe(footer('/*@ sourceMappingURL=admin.css.map */'))
      .pipe(gulp.dest( 'css/' ))
    })
  );
});

gulp.task('csslint', function() {
  return gulp.src(['css/admin.css', 'css/client.css'])
    .pipe(csslint())
    .pipe(csslint.reporter());
});

gulp.task('scsslint', function() {
  return gulp.src('css/sass/**/*.scss')
  .pipe(scsslint());
});

gulp.task('js', function() {
  return merge(
    gulp.src([
      'js/src/intro.js',
      'js/src/admin.js',
      'js/src/outro.js'
    ])
    .pipe(concat('admin.js'))
    .pipe(gulp.dest('js'))
    .pipe(rename({
      extname: '.min.js'
    }))
    .pipe(stripDebug())
    .pipe(uglify({
      preserveComments: saveLicense
    }))
    .pipe(gulp.dest('js')),

    gulp.src([
      'js/src/intro.js',
      'js/src/client.js',
      'js/src/outro.js'
    ])
    .pipe(concat('client.js'))
    .pipe(gulp.dest('js'))
    .pipe(rename({
      extname: '.min.js'
    }))
    .pipe(stripDebug())
    .pipe(uglify({
      preserveComments: saveLicense
    }))
    .pipe(gulp.dest('js'))
  );
});

gulp.task('jshint', function() {
  return gulp.src(['js/admin.js', 'js/client.js'])
    .pipe(jshint())
    .pipe(jshint.reporter('default'));
});

gulp.task('watch', function() {
  gulp.watch('js/src/*.js', ['js', 'jshint']);
  gulp.watch('css/sass/**/*.scss', ['scsslint', 'css', 'csslint']);
});

gulp.task('default', function() {
  runSequence(
    ['js', 'css'],
    ['jshint', 'scsslint', 'csslint'],
    'watch'
  );
});
