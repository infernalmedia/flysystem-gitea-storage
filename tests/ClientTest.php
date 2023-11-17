<?php

namespace InfernalMedia\FlysystemGitea\Tests;

use GuzzleHttp\Exception\ClientException;
use InfernalMedia\FlysystemGitea\Client;

class ClientTest extends TestCase
{
    /**
     * @var \InfernalMedia\FlysystemGitea\Client
     */
    protected $client;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->getClientInstance();
    }

    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Client::class, $this->getClientInstance());
    }

    /**
     * @test
     */
    public function it_can_read_a_file()
    {
        $meta = $this->client->read('README.md');

        $this->assertArrayHasKey('sha', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('last_commit_sha', $meta);
    }

    /**
     * @test
     */
    public function it_can_read_a_file_raw()
    {
        $content = $this->client->readRaw('README.md');

        $this->assertStringStartsWith('# Testing repo for `flysystem-gitea`', $content);
    }

    /**
     * @test
     */
    public function it_can_create_a_file()
    {
        $contents = $this->client->upload('testing.md', '# Testing create', 'Created file');

        $this->assertStringStartsWith('# Testing create', $this->client->readRaw('testing.md'));
        $this->assertEquals($contents["content"]["path"], 'testing.md');
        $this->assertTrue(str_ends_with($contents["content"]["url"], "?ref=" . $this->client->getBranch()));
    }

    /**
     * @test
     */
    public function it_can_update_a_file()
    {
        $contents = $this->client->upload('testing.md', '# Testing update', 'Updated file', true);

        $this->assertStringStartsWith('# Testing update', $this->client->readRaw('testing.md'));
        
        $this->assertEquals($contents["content"]["path"], 'testing.md');
        $this->assertTrue(str_ends_with($contents["content"]["url"], "?ref=" . $this->client->getBranch()));
    }

    /**
     * @test
     */
    public function it_can_delete_a_file()
    {
        $this->client->delete('testing.md', 'Deleted file');

        $this->expectException(ClientException::class);

        $this->client->read('testing.md');
    }

    /**
     * @test
     */
    public function it_can_create_a_file_from_stream()
    {
        $stream = fopen(__DIR__ . '/assets/testing.txt', 'r+');

        $contents = $this->client->uploadStream('testing.txt', $stream, 'Created file');

        fclose($stream);

        $this->assertStringStartsWith('File for testing file streams', $this->client->readRaw('testing.txt'));
        $this->assertEquals($contents["content"]["path"], 'testing.txt');
        $this->assertTrue(str_ends_with($contents["content"]["url"], "?ref=" . $this->client->getBranch()));

        // Clean up
        $this->client->delete('testing.txt', 'Deleted file');
    }

    /**
     * @test
     */
    public function it_can_not_a_create_file_from_stream_without_a_valid_stream()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->client->uploadStream('testing.txt', 'string of data', 'Created file');
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_file_tree()
    {
        $contents = $this->client->tree();

        $content = $contents->current();

        $this->assertTrue(is_array($content));
        $this->assertArrayHasKey('sha', $content[0]);
        $this->assertArrayHasKey('type', $content[0]);
        $this->assertArrayHasKey('path', $content[0]);
        $this->assertArrayHasKey('mode', $content[0]);
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_file_tree_recursive()
    {
        $contents = $this->client->tree('/', true);

        $content = $contents->current();

        $this->assertTrue(is_array($content));
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_file_tree_of_a_subdirectory()
    {
        $contents = $this->client->tree('recursive', true);
        $content = $contents->current();

        $this->assertTrue(is_array($content));
        $this->assertArrayHasKey('sha', $content[0]);
        $this->assertArrayHasKey('type', $content[0]);
        $this->assertArrayHasKey('path', $content[0]);
        $this->assertArrayHasKey('mode', $content[0]);
    }

    /**
     * @test
     */
    public function it_can_change_the_branch()
    {
        $this->client->setBranch('dev');

        $this->assertEquals($this->client->getBranch(), 'dev');
    }

    /**
     * @test
     */
    public function it_can_change_the_project_id()
    {
        $this->client->setRepository('12345678');

        $this->assertEquals($this->client->getRepository(), '12345678');
    }

    /**
     * @test
     */
    public function it_can_change_the_username()
    {
        $this->client->setUsername('org_name');

        $this->assertEquals($this->client->getUsername(), 'org_name');
    }

    /**
     * @test
     */
    public function it_can_change_the_personal_access_token()
    {
        $this->client->setPersonalAccessToken('12345678');

        $this->assertEquals($this->client->getPersonalAccessToken(), '12345678');
    }
}
