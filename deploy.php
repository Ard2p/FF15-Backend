<?php

namespace Deployer;

require 'recipe/laravel.php';
require 'recipe/cachetool.php';

set('application', 'test-bah-1x.ff15.ru');
set('repository', 'git@bitbucket.org:ff15/ff15-backend.git');
set('keep_releases', 5);

add('shared_files', []);
add('shared_dirs', []);

add('writable_dirs', []);
set('writable_mode', 'chmod');

set('allow_anonymous_stats', false);

localhost()
    ->set('deploy_path', '~/www/{{application}}/backend');

after('deploy:failed', 'deploy:unlock');
before('deploy:symlink', 'artisan:migrate');