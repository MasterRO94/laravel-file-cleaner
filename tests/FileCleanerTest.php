<?php

declare(strict_types=1);

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


	/**
	 * @test
	 */
	public function it_deletes_fresh_files_with_force()
	{
		$files = new Filesystem;

		config(['file-cleaner.time_before_remove' => 60]);

		$files->put("{$this->tempDir}/example.txt", 'test');

		$this->callCleaner();

		$this->assertFileExists("{$this->tempDir}/example.txt");

		$this->callCleaner(['-f' => true]);

		$this->assertFileNotExists("{$this->tempDir}/example.txt");
	}


	/**
	 * @test
	 */
	public function it_removes_directories_when_config_set()
	{
		$files = new Filesystem;

		config(['file-cleaner.remove_directories' => false]);

		mkdir("{$this->tempDir}/dir1/dir2", 0777, true);

		$files->put("{$this->tempDir}/example.txt", 'test');
		$files->put("{$this->tempDir}/dir1/example1.txt", 'test');
		$files->put("{$this->tempDir}/dir1/dir2/example2.txt", 'test');


		$this->callCleaner(['-f' => true]);

		$this->assertFileNotExists("{$this->tempDir}/example.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/example1.txt");
		$this->assertFileNotExists("{$this->tempDir}/dir1/dir2/example2.txt");

		$this->assertDirectoryExists("{$this->tempDir}/dir1/dir2");
	}

}
