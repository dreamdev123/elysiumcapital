<?php
// -vvv
// php dep deploy production -vvv
// php dep rollback production -vvv
// php dep deploy:unlock production

// php vendor/bin/dep deploy production
// php vendor/bin/dep rollback production
// php vendor/bin/dep deploy:unlock production

namespace Deployer;
use Maknz\Slack\Client;

require_once __DIR__ . '/vendor/deployer/deployer/recipe/common.php';
require 'recipe/laravel.php';
require 'recipe/npm.php';

set('bin/php', static function () {
    return '/usr/bin/php';
});

set('ssh_type', 'native');
set('ssh_multiplexing', false);
set('allow_anonymous_stats', false);
set('deployerUser', !empty(getenv('user')) ? getenv('user') : trim(shell_exec('whoami')));
set('slackWebHookUrl', 'https://hooks.slack.com/services/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');
set('slackDeploymentChannel', '#deployment');
set('projectName', 'elysiumcapital.io-www');
set('repository', 'git@bitbucket.org:elysiumcapital/' . get('projectName') . '.git');
set('stage_domain', null);
set('keep_releases', 3);

########################################################################################################################
task('slack:deploy', static function () {

    $stageDomain = null !== get('stage_domain') ? ' domain: ' . get('stage_domain') : null;

    $message = '*' . get('deployerUser') . '* pulled project ' . get('projectName') . ' from branch `' . get('branch') . '` on stage `' . get('stage') . '`->`' . get('hostname') . ':' . get('deploy_path') . '`' . $stageDomain;

    $client = new Client(get('slackWebHookUrl'));

    $client
        ->to(get('slackDeploymentChannel'))
        ->send($message);
});

task('slack:rollback', static function () {

    $stageDomain = null !== get('stage_domain') ? ' domain: ' . get('stage_domain') : null;

    $message = '*' . get('deployerUser') . '* rollback project ' . get('projectName') . ' from branch `' . get('branch') . '` on stage `' . get('stage') . '`->`' . get('hostname') . ':' . get('deploy_path') . '`' . $stageDomain;

    $client = new Client(get('slackWebHookUrl'));

    $client
        ->to(get('slackDeploymentChannel'))
        ->send($message);
});

task('server:cache-reset', static function () {
    $rand = random_int(1, 6);
    run("sleep $rand && sudo /sbin/service php-fpm reload");
});
########################################################################################################################

host('3.64.57.255')
    ->user('deploy')
    ->set('deploy_path', '/data/www/elysiumcapital.io')
    ->set('branch', 'master')
    ->stage('production')
//    ->identityFile('frontend-asg.pem')
    ->set('stage_domain', 'https://www.elysiumcapital.io/')
    ->forwardAgent()
    ->addSshOption('UserKnownHostsFile', '/dev/null')
    ->addSshOption('StrictHostKeyChecking', 'no');
########################################################################################################################

set('shared_files', [
    '.env'
]);
add('shared_dirs', [
    'storage',
]);
set('copy_dirs', [
    'vendor'
]);
add('writable_dirs', [
    'storage/api-docs',
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'storage/test',
]);
set('writable_dirs', []);

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:copy_dirs',
    'deploy:shared',
    'deploy:vendors',
    'npm:install',
    'npm:production',
    'deploy:writable',
    'artisan:view:cache',
    'artisan:cache:clear',
    'deploy:symlink',
    'deploy:unlock',
    'server:cache-reset',
    'cleanup',
    'success',
]);

task('npm:production', '
    npm run prod
');
########################################################################################################################

after('deploy:failed', 'deploy:unlock');
after('deploy', 'slack:deploy');
after('rollback', 'slack:rollback');
after('rollback', 'server:cache-reset');
