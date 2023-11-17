<?php

namespace InfernalMedia\FlysystemGitea\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use InfernalMedia\FlysystemGitea\Client;
use InfernalMedia\FlysystemGitea\GiteaAdapter;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var array
     */
    protected $config;
    
    /**
     *
     */
    public function setUp(): void
    {
        $this->config = require(__DIR__.'/config/config.testing.php');
    }
    
    /**
     * @return \InfernalMedia\FlysystemGitea\Client
     */
    protected function getClientInstance(): Client
    {
        return new Client($this->config[ 'username' ], $this->config[ 'repository' ], $this->config[ 'branch' ], $this->config[ 'base-url' ],
            $this->config[ 'personal-access-token' ]);
    }
    
    /**
     * @return \InfernalMedia\FlysystemGitea\GiteaAdapter
     */
    protected function getAdapterInstance(): GiteaAdapter
    {
        return new GiteaAdapter($this->getClientInstance());
    }
}
