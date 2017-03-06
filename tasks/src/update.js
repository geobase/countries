const gulp = require('gulp');
const gulpSequence = require('gulp-sequence')
const jeditor = require("gulp-json-editor");
const prompt = require("gulp-prompt");
const packageInfo = require('../../package.json');

const nextVersion = (currentVersion) => {
    const version = currentVersion.match(/(\d*)\.(\d*).(\d*)$/);
    return version[1] + '.' + version[2] + '.' + (parseInt(version[3]) + 1);
}

let version;

gulp.task('update:get-version', () =>
    gulp.src('../package.json')
        .pipe(prompt.prompt({
            type: 'input',
            name: 'task',
            message: 'What is the new version (Current verions is ' + packageInfo.version + ')?',
            default: nextVersion(packageInfo.version)
        }, (res) => version = res.task)));

gulp.task('update:package.json', () =>
    gulp.src('../package.json')
        .pipe(jeditor(function(json) {
            json.version = version;
            return json;
        }))
        .pipe(gulp.dest('../')));

gulp.task('update:composer.json', () =>
    gulp.src('../composer.json')
        .pipe(jeditor(function(json) {
            json.version = version;
            return json;
        }))
        .pipe(gulp.dest('../')));

gulp.task('update', gulpSequence('update:get-version', 'update:package.json', 'update:composer.json'));
