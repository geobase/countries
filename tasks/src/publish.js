const gulp = require('gulp');
const gulpSequence = require('gulp-sequence')
const git = require('gulp-git');
var spawn = require('child_process').spawn;

gulp.task('git:push', () =>
    git.push('origin', 'master', function (err) {
        if (err) throw err;
    }));

gulp.task('npm:publish', function (done) {
    spawn('npm', ['publish'], {
        stdio: 'inherit',
        cwd: __dirname + '/../..'
    }).on('close', done);
});

gulp.task('publish', gulpSequence('git:push', 'npm:publish'));
