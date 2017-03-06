const gulp = require('gulp');
const gulpSequence = require('gulp-sequence')

gulp.task('bundle', gulpSequence('commit', 'version', 'build', 'publish'));
