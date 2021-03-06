<?php

namespace MasterRO\LaravelFileCleaner\Tests;

use stdClass;
use CreateFilesTable;
use CreateTestOneTable;
use InvalidArgumentException;
use CreateTestCollectionTable;
use Illuminate\Filesystem\Filesystem;
use MasterRO\LaravelFileCleaner\FileCleaner;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MasterRO\LaravelFileCleaner\Tests\Database\Models\File;
use MasterRO\LaravelFileCleaner\Tests\Database\Models\TestOne;

class FileCleanerTest extends TestCase
{
    private $tempDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->singleton('Illuminate\Contracts\Console\Kernel', TestKernel::class);

        $this->tempDir = __DIR__ . '/tmp';
        mkdir($this->tempDir, 0777, true);
        $this->setTestConfig();

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(app(FileCleaner::class));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        FileCleaner::voteDeleteUsing(null);

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

        $this->assertFileDoesNotExist("{$this->tempDir}/test.txt");
    }

    /**
     * @test
     */
    public function it_removes_directories_when_config_set()
    {
        config(['file-cleaner.remove_directories' => false]);

        $this->createNestedTestFilesAndDirectories();

        $this->callCleaner();

        $this->assertFileDoesNotExist("{$this->tempDir}/test.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/example1.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/dir2/example2.txt");

        $this->assertDirectoryExists("{$this->tempDir}/dir1/dir2");

        config(['file-cleaner.remove_directories' => true]);

        $this->callCleaner();

        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir1");
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
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir1");
    }

    /**
     * @test
     */
    public function it_can_delete_files_in_specified_directories()
    {
        config([
            'file-cleaner.paths' => [
                "{$this->tempDir}/dir1",
                "{$this->tempDir}/dir2",
            ],
        ]);

        $this->createTestDirectoriesAndFiles(3);

        $this->callCleaner();

        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir2/test.txt");
        $this->assertFileExists("{$this->tempDir}/dir3/test.txt");
    }

    /**
     * @test
     */
    public function it_can_override_paths_config_parameter_with_directories_parameter()
    {
        config([
            'file-cleaner.paths' => [
                "{$this->tempDir}/dir1",
                "{$this->tempDir}/dir2",
            ],
        ]);

        $this->createTestDirectoriesAndFiles(4);

        $this->callCleaner(true, ['--directories' => "{$this->tempDir}/dir3,{$this->tempDir}/dir4"]);

        $this->assertFileExists("{$this->tempDir}/dir1/test.txt");
        $this->assertFileExists("{$this->tempDir}/dir2/test.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir3/test.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir4/test.txt");
    }

    /**
     * @test
     */
    public function it_can_exclude_directories_from_cleaning()
    {
        config([
            'file-cleaner.excluded_paths' => [
                "{$this->tempDir}/dir1",
            ],
        ]);

        $this->createTestDirectoriesAndFiles();

        $this->callCleaner();

        $this->assertFileExists("{$this->tempDir}/dir1/test.txt");
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir2");
    }

    /**
     * @test
     */
    public function it_can_override_excluded_directories_from_cleaning()
    {
        config([
            'file-cleaner.excluded_paths' => [
                "{$this->tempDir}/dir1",
                "{$this->tempDir}/dir2",
            ],
        ]);

        $this->createTestDirectoriesAndFiles(4);

        $this->callCleaner(true, ['--excluded-paths' => "{$this->tempDir}/dir3,{$this->tempDir}/dir4"]);

        $this->assertFileExists("{$this->tempDir}/dir3/test.txt");
        $this->assertFileExists("{$this->tempDir}/dir4/test.txt");
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir1");
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir2");
    }

    /**
     * @test
     */
    public function it_can_exclude_files_from_cleaning()
    {
        $files = new Filesystem;

        config([
            'file-cleaner.excluded_files' => [
                "{$this->tempDir}/dir1/test.txt",
            ],
        ]);

        $this->createTestDirectoriesAndFiles();

        $files->put("{$this->tempDir}/dir1/test2.txt", 'test');

        $this->callCleaner();

        $this->assertFileExists("{$this->tempDir}/dir1/test.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test2.txt");
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir2");
    }

    /**
     * @test
     */
    public function it_can_override_excluded_files_from_cleaning()
    {
        $files = new Filesystem;

        config([
            'file-cleaner.excluded_files' => [
                "{$this->tempDir}/dir1/test.txt",
                "{$this->tempDir}/dir2/test.txt",
            ],
        ]);

        $this->createTestDirectoriesAndFiles(4);

        $files->put("{$this->tempDir}/dir1/test2.txt", 'test');
        $files->put("{$this->tempDir}/dir2/test2.txt", 'test');

        $this->callCleaner(
            true,
            ['--excluded-files' => "{$this->tempDir}/dir1/test2.txt,{$this->tempDir}/dir2/test2.txt"]
        );

        $this->assertFileExists("{$this->tempDir}/dir1/test2.txt");
        $this->assertFileExists("{$this->tempDir}/dir2/test2.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test.txt");
        $this->assertFileDoesNotExist("{$this->tempDir}/dir2/test.txt");
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir3");
        $this->assertDirectoryDoesNotExist("{$this->tempDir}/dir4");
    }

    /**
     * @test
     */
    public function it_deletes_associated_model_instance()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
        ]);

        File::create(['name' => 'test.txt']);

        $this->callCleaner();

        $this->assertCount(0, File::where(['name' => 'test.txt'])->get());
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test1.txt");
    }

    /**
     * @test
     */
    public function it_does_not_delete_associated_model_instance_if_field_name_wrong()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'wrong_name',
        ]);

        File::create(['name' => 'test.txt']);

        $this->callCleaner();

        $this->assertCount(1, File::where(['name' => 'test.txt'])->get());
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test1.txt");
    }

    /**
     * @test
     */
    public function model_should_be_an_instance_of_eloquent()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => stdClass::class,
            'file-cleaner.file_field_name' => 'name',
        ]);

        $this->callCleaner();
    }

    /**
     * @test
     */
    public function it_can_delete_model_instance_if_it_does_not_have_related_instance()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
            'file-cleaner.relation'        => 'testOne',
        ]);

        File::create(['name' => 'test.txt']);

        $this->callCleaner();

        $this->assertCount(0, File::where(['name' => 'test.txt'])->get());
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test.txt");
    }

    /**
     * @test
     */
    public function it_should_not_delete_model_instance_if_it_has_related_instance()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
            'file-cleaner.relation'        => 'testOne',
        ]);

        $oneRelated = TestOne::create(['name' => 'test']);
        $oneRelated->files()->create(['name' => 'test.txt']);

        $this->callCleaner();

        $this->assertCount(1, File::where(['name' => 'test.txt'])->get());
        $this->assertFileExists("{$this->tempDir}/dir1/test.txt");
    }

    /**
     * @test
     */
    public function it_can_delete_model_instance_if_it_does_not_have_related_instances()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
            'file-cleaner.relation'        => 'testCollection',
        ]);

        File::create(['name' => 'test.txt']);

        $this->callCleaner();

        $this->assertCount(0, File::where(['name' => 'test.txt'])->get());
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test.txt");
    }

    /**
     * @test
     */
    public function it_should_not_delete_file_and_model_instance_if_it_has_related_instances()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
            'file-cleaner.relation'        => 'testCollection',
        ]);

        $file = File::create(['name' => 'test.txt']);
        $file->testCollection()->createMany([
            ['name' => 'test'],
            ['name' => 'test2'],
        ]);

        $this->callCleaner();

        $this->assertCount(1, File::where(['name' => 'test.txt'])->get());
        $this->assertFileExists("{$this->tempDir}/dir1/test.txt");
    }

    /**
     * @test
     */
    public function it_throws_exception_if_model_instance_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
            'file-cleaner.relation'        => 'testOne',
        ]);

        $oneRelated = TestOne::create(['name' => 'test']);
        $oneRelated->files()->create(['name' => 'wrong_name.txt']);

        $this->callCleaner();
    }

    /**
     * @test
     */
    public function it_removes_file_if_voter_decision_is_true()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
        ]);

        File::create(['name' => 'test.txt']);

        FileCleaner::voteDeleteUsing(function ($path, $entity) {
            if (isset($entity) && false !== strpos($path, 'test.txt')) {
                return true;
            }

            return false;
        });

        $this->callCleaner();

        $this->assertCount(0, File::where(['name' => 'test.txt'])->get());
        $this->assertFileDoesNotExist("{$this->tempDir}/dir1/test.txt");
    }

    /**
     * @test
     */
    public function it_doesnt_removes_file_if_voter_decision_is_false()
    {
        $this->setUpDatabase($this->app);

        $this->createTestDirectoriesAndFiles(1);

        config([
            'file-cleaner.model'           => File::class,
            'file-cleaner.file_field_name' => 'name',
        ]);

        File::create(['name' => 'test.txt']);

        FileCleaner::voteDeleteUsing(function ($path, $entity) {
            if (isset($entity) && false !== strpos($path, 'test.txt')) {
                return false;
            }

            return true;
        });

        $this->callCleaner();

        $this->assertCount(1, File::where(['name' => 'test.txt'])->get());
        $this->assertFileExists("{$this->tempDir}/dir1/test.txt");
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Set up the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        (new CreateFilesTable)->up();
        (new CreateTestOneTable)->up();
        (new CreateTestCollectionTable)->up();
    }

    protected function setTestConfig()
    {
        config([
            'file-cleaner' => [
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

            ],
        ]);
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

    /**
     * @param int $depth
     */
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
