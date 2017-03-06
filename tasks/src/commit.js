const gulp = require('gulp');
const gulpSequence = require('gulp-sequence')
const git = require('gulp-git');
const prompt = require("gulp-prompt");

let message;

gulp.task('git:message', () =>
    gulp.src('../package.json')
        .pipe(prompt.prompt({
            type: 'input',
            name: 'task',
            message: 'What is the commit message?'
        }, (res) => message = res.task)));

gulp.task('git:add', function(){
    return gulp.src('..')
        .pipe(git.add({ args: '--all' }));
});

gulp.task('git:commit', function(){
    return gulp.src('..')
        .pipe(git.commit(message));
});

gulp.task('commit', gulpSequence('git:message', 'git:add', 'git:commit'));
