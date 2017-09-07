<?php

declare(strict_types=1);

use Orchestra\Testbench\TestCase;
use Illuminate\Filesystem\Filesystem;
use MasterRO\LaravelFileCleaner\FileCleaner;
use Illuminate\Console\Application as ConsoleApplication;

class FileCleanerTest extends TestCase
{
	private $tempDir;


	public function setUp()
	{
		parent::setUp();

		$this->tempDir = __DIR__ . '/tmp';
		mkdir($this->tempDir, 0777, true);

		$config = require __DIR__ . './../src/file-cleaner.php';

		config(['file-cleaner' => $config]);

//		$this->app[Kernel::class]->add(FileCleaner::class);
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
	public function it_deletes_files_force()
	{
		$files = new Filesystem;

		$files->put("{$this->tempDir}/example.txt", 'test');

		$this->artisan('file-cleaner:clean', ['-f']);

		$this->assertFileNotExists("{$this->tempDir}/example.txt");
	}

}
