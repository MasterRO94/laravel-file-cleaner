<?php

use Orchestra\Testbench\TestCase;
use Illuminate\Filesystem\Filesystem;
use MasterRO\LaravelFileCleaner\FileCleaner;

class FileCleanerTest extends TestCase
{
	private $tempDir;


	public function setUp()
	{
		parent::setUp();

		$this->app->singleton('Illuminate\Contracts\Console\Kernel', TestKernel::class);

		$this->tempDir = __DIR__ . '/tmp';
		mkdir($this->tempDir, 0777, true);
		$this->setTestConfig();

		$this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(app(FileCleaner::class));
	}


	public function tearDown()
	{
		parent::tearDown();

		$files = new Filesystem;
		$files->deleteDirectory($this->tempDir);
	}


	/**
	 * @test
	 */
	public function it_deletes_fresh_files_with_force()
	{
		$files = new Filesystem;

		config(['file-cleaner.time_before_remove' => 60]);

		$files->put("{$this->tempDir}/test.txt", 'test');

		$this->callCleaner();

		$this->assertFileExists("{$this->tempDir}/test.txt");

		$this->callCleaner(['-f' => true]);

		$this->assertFileNotExists("{$this->tempDir}/test.txt");
	}


	/**
	 * @test
	 */
	public function it_removes_directories_when_config_set()
	{
		config(['file-cleaner.remove_directories' => false]);

		$this->createTestFilesAndDirectories();

		$this->callCleaner(['-f' => true]);

		$this->assertFileNotExists("{$this->tempDir}/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/example1.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/dir2/example2.txt");

		$this->assertDirectoryExists("{$this->tempDir}/dir1/dir2");

		config(['file-cleaner.remove_directories' => true]);

		$this->callCleaner(['-f' => true]);

		$this->assertDirectoryNotExists("{$this->tempDir}/dir1");
	}


	/**
	 * @test
	 */
	public function it_allows_to_redefine_remove_directories_config_parameter()
	{
		config(['file-cleaner.remove_directories' => false]);
		$this->createTestFilesAndDirectories();

		$this->callCleaner(['-f' => true]);
		$this->assertDirectoryExists("{$this->tempDir}/dir1/dir2");

		$this->createTestFilesAndDirectories();

		$this->callCleaner(['-f' => true, '--remove-directories' => true]);
		$this->assertDirectoryNotExists("{$this->tempDir}/dir1");
	}


	/**
	 * @test
	 */
	public function it_can_delete_files_in_specified_directories()
	{
		$files = new Filesystem;

		config(['file-cleaner.paths' => [
			"{$this->tempDir}/dir1",
			"{$this->tempDir}/dir2",
		]]);

		$files->makeDirectory("{$this->tempDir}/dir1", 0777, true);
		$files->makeDirectory("{$this->tempDir}/dir2", 0777, true);
		$files->makeDirectory("{$this->tempDir}/dir3", 0777, true);

		$files->put("{$this->tempDir}/dir1/test.txt", 'test');
		$files->put("{$this->tempDir}/dir2/test.txt", 'test');
		$files->put("{$this->tempDir}/dir3/test.txt", 'test');

		$this->callCleaner(['-f' => true]);

		$this->assertFileNotExists("{$this->tempDir}/dir1/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir2/test.txt");
		$this->assertFileExists("{$this->tempDir}/dir3/test.txt");
	}


	protected function setTestConfig()
	{
		config(['file-cleaner' => [
			'paths'              => [
				$this->tempDir,
			],
			'excluded_paths'     => [],
			'excluded_files'     => [],
			'time_before_remove' => 0,
			'remove_directories' => true,
			'model'              => null,
			'file_field_name'    => null,
			'relation'           => null,

		]]);
	}


	/**
	 * @param array $params
	 */
	protected function callCleaner(array $params = [])
	{
		$this->artisan('file-cleaner:clean', $params);
	}


	protected function createTestFilesAndDirectories()
	{
		$files = new Filesystem;

		$path = "{$this->tempDir}/dir1/dir2";

		$files->deleteDirectory($path);
		$files->makeDirectory($path, 0777, true);

		$files->put("{$this->tempDir}/test.txt", 'test');
		$files->put("{$this->tempDir}/dir1/example1.txt", 'test');
		$files->put("{$this->tempDir}/dir1/dir2/example2.txt", 'test');
	}

}
