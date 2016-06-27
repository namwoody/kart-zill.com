// ## Globals
var gulp = require('gulp');
var autoprefixer = require('gulp-autoprefixer');

var minifyCss    = require('gulp-minify-css');
var watch   = require('gulp-watch');
var sass         = require('gulp-sass');




gulp.task('css',function(){
  gulp.src('assets/styles/main.scss')
     .pipe(sass())
     .pipe(autoprefixer('last 10 version'))
     //.pipe(minifyCss({compatibility: 'ie8'}))
     .pipe(gulp.dest('css'));

});


gulp.task('watch',function(){
 gulp.watch('assets/styles/**/*.scss',['css']);
});


gulp.task('jade', function() {
 
  return gulp.src('templates/**/*.jade')
      .pipe(jade())
    .pipe(gulp.dest('templates/'));
});


gulp.task('default',['watch']);
