<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Flysystem Adapter for Gitea configurations
    |--------------------------------------------------------------------------
    |
    | These configurations will be used in all the the tests to bootstrap
    | a Client object.
    |
    */
    
    /**
     * Personal access token
     *
     * @see https://docs.gitea.com/development/api-usage
     */
    'personal-access-token' => 'your-access-token',
    
    /**
     * Username or organization of your repo
     */
    'username'            => 'your-org-name',
    
    /**
     * Repository
     */
    'repository'            => 'your-test-repo',

    /**
     * Branch that should be used
     */
    'branch'                => 'main',
    
    /**
     * Base URL of Gitea server you want to use
     */
    'base-url'              => 'https://gitea.com',
];
