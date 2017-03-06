const gulp = require('gulp');
const zip = require('gulp-zip');
const gulpSequence = require('gulp-sequence')
const del = require('del')

gulp.task('build:clean', () =>
    del('../build/**/*', {force:true}));

gulp.task('build:copy', () =>
    gulp.src(['../countries/**/*', '../regions/**/*'])
      .pipe(gulp.dest('../build/countries')));

gulp.task('build:zip', () =>
    gulp.src('../build/countries/**/*')
        .pipe(zip('countries.zip'))
        .pipe(gulp.dest('../build')));

gulp.task('build', gulpSequence('build:clean', 'build:copy', 'build:zip'));
