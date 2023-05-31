<?php

return [
    'token' => env('DEPLOY_TOKEN', null),  
    'branch' => env('DEPLOY_BRANCH', 'master'),
    'author' => env('DEPLOY_AUTHOR', null),   

    'backend_repo' => env('DEPLOY_BACK_REPO', null),
    'frontend_repo' => env('DEPLOY_FRONT_REPO', null),    
    
    'backend_path' => env('DEPLOY_BACK_PATH', null),
    'frontend_path' => env('DEPLOY_FRONT_PATH', null),  

    'path' => env('DEPLOY_PATH', '~/' . env('APP_NAME')),
];