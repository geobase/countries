const gulp = require('gulp');
const gulpSequence = require('gulp-sequence')

gulp.task('bundle', gulpSequence('update', 'build', 'commit'));
