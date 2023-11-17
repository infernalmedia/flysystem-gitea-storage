<?php

namespace InfernalMedia\FlysystemGitea\Tests;

use InfernalMedia\FlysystemGitea\Client;
use InfernalMedia\FlysystemGitea\GiteaAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;

class GiteaAdapterTest extends TestCase
{
    /**
     * @var \InfernalMedia\FlysystemGitea\GiteaAdapter
     */
    protected GiteaAdapter $GiteaAdapter;
    
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        
        $this->GiteaAdapter = $this->getAdapterInstance();
    }
    
    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(GiteaAdapter::class, $this->getAdapterInstance());
    }

    /**
     * @test
     */
    public function it_can_retrieve_client_instance()
    {
        $this->assertInstanceOf(Client::class, $this->GiteaAdapter->getClient());
    }

    /**
     * @test
     */
    public function it_can_set_client_instance()
    {
        $this->setInvalidProjectId();

        $this->assertEquals($this->GiteaAdapter->getClient()
            ->getRepository(), '123');
    }

    /**
     * @test
     */
    public function it_can_read_a_file()
    {
        $response = $this->GiteaAdapter->read('README.md');

        $this->assertStringStartsWith('# Testing repo for `flysystem-gitea`', $response);
    }

    /**
     * @test
     */
    public function it_can_read_a_file_into_a_stream()
    {
        $stream = $this->GiteaAdapter->readStream('README.md');

        $this->assertIsResource($stream);
        $this->assertEquals(stream_get_contents($stream, -1, 0), $this->GiteaAdapter->read('README.md'));
    }

    /**
     * @test
     */
    public function it_throws_when_read_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToReadFile::class);

        $this->GiteaAdapter->read('README.md');
    }

    /**
     * @test
     */
    public function it_can_determine_if_a_project_has_a_file()
    {
        $this->assertTrue($this->GiteaAdapter->fileExists('/README.md'));

        $this->assertFalse($this->GiteaAdapter->fileExists('/I_DONT_EXIST.md'));
    }

    /**
     * @test
     */
    public function it_throws_when_file_existence_failed()
    {
        $this->setInvalidToken();

        $this->expectException(UnableToCheckFileExistence::class);

        $this->GiteaAdapter->fileExists('/README.md');
    }

    /**
     * @test
     */
    public function it_can_delete_a_file()
    {
        $this->GiteaAdapter->write('testing.md', '# Testing create', new Config());

        $this->assertTrue($this->GiteaAdapter->fileExists('/testing.md'));

        $this->GiteaAdapter->delete('/testing.md');

        $this->assertFalse($this->GiteaAdapter->fileExists('/testing.md'));
    }

    /**
     * @test
     */
    public function it_returns_false_when_delete_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToDeleteFile::class);

        $this->GiteaAdapter->delete('testing_renamed.md');
    }

    /**
     * @test
     */
    public function it_can_write_a_new_file()
    {
        $this->GiteaAdapter->write('testing.md', '# Testing create', new Config());

        $this->assertTrue($this->GiteaAdapter->fileExists('testing.md'));
        $this->assertEquals($this->GiteaAdapter->read('testing.md'), '# Testing create');

        $this->GiteaAdapter->delete('testing.md');
    }

    /**
     * @test
     */
    public function it_automatically_creates_missing_directories()
    {
        $this->GiteaAdapter->write('/folder/missing/testing.md', '# Testing create folders', new Config());

        $this->assertTrue($this->GiteaAdapter->fileExists('/folder/missing/testing.md'));
        $this->assertEquals($this->GiteaAdapter->read('/folder/missing/testing.md'), '# Testing create folders');

        $this->GiteaAdapter->delete('/folder/missing/testing.md');
    }

    /**
     * @test
     */
    public function it_throws_when_write_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToWriteFile::class);

        $this->GiteaAdapter->write('testing.md', '# Testing create', new Config());
    }

    /**
     * @test
     */
    public function it_can_write_a_file_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        $this->GiteaAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);

        $this->assertTrue($this->GiteaAdapter->fileExists('testing.txt'));
        $this->assertEquals($this->GiteaAdapter->read('testing.txt'), 'File for testing file streams');

        $this->GiteaAdapter->delete('testing.txt');
    }

    /**
     * @test
     */
    public function it_throws_when_writing_file_stream_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToWriteFile::class);

        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        $this->GiteaAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);
    }

    /**
     * @test
     */
    public function it_can_override_a_file()
    {
        $this->GiteaAdapter->write('testing.md', '# Testing create', new Config());
        $this->GiteaAdapter->write('testing.md', '# Testing update', new Config());

        $this->assertStringStartsWith($this->GiteaAdapter->read('testing.md'), '# Testing update');

        $this->GiteaAdapter->delete('testing.md');
    }


    /**
     * @test
     */
    public function it_can_override_with_a_file_stream()
    {
        $stream = fopen(__DIR__.'/assets/testing.txt', 'r+');
        $this->GiteaAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);

        $stream = fopen(__DIR__.'/assets/testing-update.txt', 'r+');
        $this->GiteaAdapter->writeStream('testing.txt', $stream, new Config());
        fclose($stream);

        $this->assertTrue($this->GiteaAdapter->fileExists('testing.txt'));
        $this->assertEquals($this->GiteaAdapter->read('testing.txt'), 'File for testing file streams!');

        $this->GiteaAdapter->delete('testing.txt');
    }

    /**
     * @test
     */
    public function it_can_move_a_file()
    {
        $this->GiteaAdapter->write('testing.md', '# Testing move', new Config());

        $this->GiteaAdapter->move('testing.md', 'testing_move.md', new Config());

        $this->assertFalse($this->GiteaAdapter->fileExists('testing.md'));
        $this->assertTrue($this->GiteaAdapter->fileExists('testing_move.md'));

        $this->assertEquals($this->GiteaAdapter->read('testing_move.md'), '# Testing move');

        $this->GiteaAdapter->delete('testing_move.md');
    }

    /**
     * @test
     */
    public function it_throws_when_move_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToMoveFile::class);

        $this->GiteaAdapter->move('testing_move.md', 'testing.md', new Config());
    }

    /**
     * @test
     */
    public function it_can_copy_a_file()
    {
        $this->GiteaAdapter->write('testing.md', '# Testing copy', new Config());

        $this->GiteaAdapter->copy('testing.md', 'testing_copy.md', new Config());

        $this->assertTrue($this->GiteaAdapter->fileExists('testing.md'));
        $this->assertTrue($this->GiteaAdapter->fileExists('testing_copy.md'));

        $this->assertEquals($this->GiteaAdapter->read('testing.md'), '# Testing copy');
        $this->assertEquals($this->GiteaAdapter->read('testing_copy.md'), '# Testing copy');

        $this->GiteaAdapter->delete('testing.md');
        $this->GiteaAdapter->delete('testing_copy.md');
    }

    /**
     * @test
     */
    public function it_throws_when_copy_failed()
    {
        $this->setInvalidProjectId();

        $this->expectException(UnableToCopyFile::class);

        $this->GiteaAdapter->copy('testing_copy.md', 'testing.md', new Config());
    }

    /**
     * @test
     */
    public function it_can_create_a_directory()
    {
        $this->GiteaAdapter->createDirectory('/testing', new Config());

        $this->assertTrue($this->GiteaAdapter->fileExists('/testing/.gitkeep'));

        $this->GiteaAdapter->delete('/testing/.gitkeep');
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_list_of_contents_of_root()
    {
        $list = $this->GiteaAdapter->listContents('/', false);
        $expectedPaths = [
            ['type' => 'dir', 'path' => 'recursive'],
            ['type' => 'file', 'path' => 'LICENSE'],
            ['type' => 'file', 'path' => 'README.md'],
            ['type' => 'file', 'path' => 'test'],
            ['type' => 'file', 'path' => 'test2'],
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertTrue(
                in_array(['type' => $item['type'], 'path' => $item['path']], $expectedPaths)
            );
        }
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_list_of_contents_of_root_recursive()
    {
        $list = $this->GiteaAdapter->listContents('/', true);
        $expectedPaths = [
            ['type' => 'dir', 'path' => 'recursive'],
            ['type' => 'file', 'path' => 'LICENSE'],
            ['type' => 'file', 'path' => 'README.md'],
            ['type' => 'file', 'path' => 'recursive/recursive.testing.md'],
            ['type' => 'file', 'path' => 'test'],
            ['type' => 'file', 'path' => 'test2'],
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertTrue(
                in_array(['type' => $item['type'], 'path' => $item['path']], $expectedPaths)
            );
        }
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_list_of_contents_of_sub_folder()
    {
        $list = $this->GiteaAdapter->listContents('/recursive', false);
        $expectedPaths = [
            ['type' => 'file', 'path' => 'recursive/recursive.testing.md']
        ];

        foreach ($list as $item) {
            $this->assertInstanceOf(StorageAttributes::class, $item);
            $this->assertTrue(
                in_array(['type' => $item['type'], 'path' => $item['path']], $expectedPaths)
            );
        }
    }

    /**
     * @test
     */
    public function it_can_delete_a_directory()
    {
        $this->GiteaAdapter->createDirectory('/testing', new Config());
        $this->GiteaAdapter->write('/testing/testing.md', 'Testing delete directory', new Config());

        $this->GiteaAdapter->deleteDirectory('/testing');

        $this->assertFalse($this->GiteaAdapter->fileExists('/testing/.gitkeep'));
        $this->assertFalse($this->GiteaAdapter->fileExists('/testing/testing.md'));
    }
    
    /**
     * @test
     */
    public function it_throws_when_delete_directory_failed()
    {
        $this->setInvalidProjectId();
        
        $this->expectException(FilesystemException::class);
        
        $this->GiteaAdapter->deleteDirectory('/testing');
    }
    
    /**
     * @test
     */
    public function it_can_retrieve_size()
    {
        $size = $this->GiteaAdapter->fileSize('README.md');

        $this->assertInstanceOf(FileAttributes::class, $size);
        $this->assertEquals($size->fileSize(), 38);
    }

    /**
     * @test
     */
    public function it_can_retrieve_mimetype()
    {
        $metadata = $this->GiteaAdapter->mimeType('README.md');

        $this->assertInstanceOf(FileAttributes::class, $metadata);
        $this->assertEquals($metadata->mimeType(), 'text/markdown');
    }

    /**
     * @test
     */
    public function it_can_not_retrieve_lastModified()
    {
        $lastModified = $this->GiteaAdapter->lastModified('README.md');

        $this->assertInstanceOf(FileAttributes::class, $lastModified);
        $this->assertEquals($lastModified->lastModified(), 1700236473);
    }

    /**
     * @test
     */
    public function it_throws_when_getting_visibility()
    {
        $this->expectException(UnableToSetVisibility::class);

        $this->GiteaAdapter->visibility('README.md');
    }

    /**
     * @test
     */
    public function it_throws_when_setting_visibility()
    {
        $this->expectException(UnableToSetVisibility::class);

        $this->GiteaAdapter->setVisibility('README.md', 0777);
    }

    /**
     * @test
     */
    public function it_can_check_directory_if_exists()
    {
        $dir = 'test-dir/test-dir2/test-dir3';
        $this->GiteaAdapter->createDirectory($dir, new Config());
        $this->assertTrue($this->GiteaAdapter->directoryExists($dir));
        $this->GiteaAdapter->deleteDirectory($dir);
    }

    /**
     * @test
     */
    public function it_cannot_check_if_directory_exists()
    {
        $this->assertFalse($this->GiteaAdapter->directoryExists('test_non_existent_dir'));
    }
    
    private function setInvalidToken()
    {
        $client = $this->GiteaAdapter->getClient();
        $client->setPersonalAccessToken('123');
        $this->GiteaAdapter->setClient($client);
    }
    
    private function setInvalidProjectId()
    {
        $client = $this->GiteaAdapter->getClient();
        $client->setRepository('123');
        $this->GiteaAdapter->setClient($client);
    }
}
