<?php

namespace Tests;

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

		$this->callCleaner(false);

		$this->assertFileExists("{$this->tempDir}/test.txt");

		$this->callCleaner();

		$this->assertFileNotExists("{$this->tempDir}/test.txt");
	}


	/**
	 * @test
	 */
	public function it_removes_directories_when_config_set()
	{
		config(['file-cleaner.remove_directories' => false]);

		$this->createNestedTestFilesAndDirectories();

		$this->callCleaner();

		$this->assertFileNotExists("{$this->tempDir}/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/example1.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/dir2/example2.txt");

		$this->assertDirectoryExists("{$this->tempDir}/dir1/dir2");

		config(['file-cleaner.remove_directories' => true]);

		$this->callCleaner();

		$this->assertDirectoryNotExists("{$this->tempDir}/dir1");
	}


	/**
	 * @test
	 */
	public function it_can_override_remove_directories_config_parameter()
	{
		config(['file-cleaner.remove_directories' => false]);
		$this->createNestedTestFilesAndDirectories();

		$this->callCleaner();
		$this->assertDirectoryExists("{$this->tempDir}/dir1/dir2");

		$this->createNestedTestFilesAndDirectories();

		$this->callCleaner(true, ['--remove-directories' => true]);
		$this->assertDirectoryNotExists("{$this->tempDir}/dir1");
	}


	/**
	 * @test
	 */
	public function it_can_delete_files_in_specified_directories()
	{
		config(['file-cleaner.paths' => [
			"{$this->tempDir}/dir1",
			"{$this->tempDir}/dir2",
		]]);

		$this->createTestDirectoriesAndFiles(3);

		$this->callCleaner();

		$this->assertFileNotExists("{$this->tempDir}/dir1/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir2/test.txt");
		$this->assertFileExists("{$this->tempDir}/dir3/test.txt");
	}


	/**
	 * @test
	 */
	public function it_can_override_paths_config_parameter_with_directories_parameter()
	{
		config(['file-cleaner.paths' => [
			"{$this->tempDir}/dir1",
			"{$this->tempDir}/dir2",
		]]);

		$this->createTestDirectoriesAndFiles(4);

		$this->callCleaner(true, ['--directories' => "{$this->tempDir}/dir3,{$this->tempDir}/dir4"]);

		$this->assertFileExists("{$this->tempDir}/dir1/test.txt");
		$this->assertFileExists("{$this->tempDir}/dir2/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir3/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir4/test.txt");
	}


	/**
	 * @test
	 */
	public function it_can_exclude_directories_from_cleaning()
	{
		config(['file-cleaner.excluded_paths' => [
			"{$this->tempDir}/dir1",
		]]);

		$this->createTestDirectoriesAndFiles();

		$this->callCleaner();

		$this->assertFileExists("{$this->tempDir}/dir1/test.txt");
		$this->assertDirectoryNotExists("{$this->tempDir}/dir2");
	}


	/**
	 * @test
	 */
	public function it_can_override_excluded_directories_from_cleaning()
	{
		config(['file-cleaner.excluded_paths' => [
			"{$this->tempDir}/dir1",
			"{$this->tempDir}/dir2",
		]]);

		$this->createTestDirectoriesAndFiles(4);

		$this->callCleaner(true, ['--excluded-paths' => "{$this->tempDir}/dir3,{$this->tempDir}/dir4"]);

		$this->assertFileExists("{$this->tempDir}/dir3/test.txt");
		$this->assertFileExists("{$this->tempDir}/dir4/test.txt");
		$this->assertDirectoryNotExists("{$this->tempDir}/dir1");
		$this->assertDirectoryNotExists("{$this->tempDir}/dir2");
	}


	/**
	 * @test
	 */
	public function it_can_exclude_files_from_cleaning()
	{
		$files = new Filesystem;

		config(['file-cleaner.excluded_files' => [
			"{$this->tempDir}/dir1/test.txt",
		]]);

		$this->createTestDirectoriesAndFiles();

		$files->put("{$this->tempDir}/dir1/test2.txt", 'test');

		$this->callCleaner();

		$this->assertFileExists("{$this->tempDir}/dir1/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/test2.txt");
		$this->assertDirectoryNotExists("{$this->tempDir}/dir2");
	}


	/**
	 * @test
	 */
	public function it_can_override_excluded_files_from_cleaning()
	{
		$files = new Filesystem;

		config(['file-cleaner.excluded_files' => [
			"{$this->tempDir}/dir1/test.txt",
			"{$this->tempDir}/dir2/test.txt",
		]]);

		$this->createTestDirectoriesAndFiles(4);

		$files->put("{$this->tempDir}/dir1/test2.txt", 'test');
		$files->put("{$this->tempDir}/dir2/test2.txt", 'test');

		$this->callCleaner(true, ['--excluded-files' => "{$this->tempDir}/dir1/test2.txt,{$this->tempDir}/dir2/test2.txt"]);

		$this->assertFileExists("{$this->tempDir}/dir1/test2.txt");
		$this->assertFileExists("{$this->tempDir}/dir2/test2.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/test.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir2/test.txt");
		$this->assertDirectoryNotExists("{$this->tempDir}/dir3");
		$this->assertDirectoryNotExists("{$this->tempDir}/dir4");
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
	 * @param bool $force
	 * @param array $params
	 */
	protected function callCleaner($force = true, array $params = [])
	{
		$params = $force ? array_merge($params, ['-f' => true]) : $params;

		$this->artisan('file-cleaner:clean', $params);
	}


	protected function createNestedTestFilesAndDirectories($depth = 3)
	{
		$files = new Filesystem;

		$path = $this->tempDir;

		for ($i = 1; $i <= $depth; ++$i) {
			$path .= "/dir{$i}";
		}

		$files->deleteDirectory($path);
		$files->makeDirectory($path, 0777, true);

		$path = $this->tempDir;

		$files->put("{$this->tempDir}/test.txt", 'test');
		for ($i = 1; $i <= $depth; ++$i) {
			$path .= "/dir{$i}";
			$files->put("{$path}/test.txt", 'test');
		}
	}


	/**
	 * @param int $count
	 */
	protected function createTestDirectoriesAndFiles($count = 2)
	{
		$files = new Filesystem;

		for ($i = 1; $i <= $count; ++$i) {
			$files->makeDirectory("{$this->tempDir}/dir{$i}", 0777, true);
			$files->put("{$this->tempDir}/dir{$i}/test.txt", 'test');
		}
	}

}
