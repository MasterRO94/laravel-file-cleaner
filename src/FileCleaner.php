<?php

namespace MasterRO\LaravelFileCleaner;

use Closure;
use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class FileCleaner
 *
 * @package MasterRO\LaravelFileCleaner
 */
class FileCleaner extends Command
{
    const DEFAULT_TIME_BEFORE_REMOVE = 60;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file-cleaner:clean 
                                                {--f|force} 
                                                {--directories=}
                                                {--excluded-paths=}
                                                {--excluded-files=}
                                                {--remove-directories=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all temp or other files from disk and associated models';

    /**
     * @var Closure|null
     */
    protected static $voter = null;

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var array
     */
    protected $excludedPaths = [];

    /**
     * @var array
     */
    protected $excludedFiles = [];

    /**
     * @var string|null
     */
    protected $model = null;

    /**
     * @var string|null
     */
    protected $fileField = null;

    /**
     * @var string|null
     */
    protected $relation = null;

    /**
     * @var bool
     */
    protected $removeDirectories = true;

    /**
     * Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @var int $timeAfterRemove
     */
    protected $timeBeforeRemove;

    /**
     * @var int $countRemovedFiles
     */
    protected $countRemovedFiles = 0;

    /**
     * @var int $countRemovedDirectories
     */
    protected $countRemovedDirectories = 0;

    /**
     * @var int $countRemovedInstances
     */
    protected $countRemovedInstances = 0;

    /**
     * Create a new command instance.
     *
     * @param Filesystem|\Illuminate\Contracts\Filesystem\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Vote Delete Using
     *
     * @param Closure $voter
     */
    public static function voteDeleteUsing(Closure $voter = null)
    {
        static::$voter = $voter;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->readConfigs()->setUp();

        if (!count($this->paths)) {
            $this->info('Nothing to delete.');

            return;
        }

        foreach ($this->paths as $path) {
            $this->clear($path);
        }

        $this->outputResultCounts();
    }

    /**
     * Read Configs
     *
     * @return FileCleaner
     */
    protected function readConfigs()
    {
        $this->paths = config('file-cleaner.paths', []);
        $this->excludedPaths = config('file-cleaner.excluded_paths', []);
        $this->excludedFiles = config('file-cleaner.excluded_files', []);
        $this->setRealPaths();

        if (!is_null(config('file-cleaner.model'))) {
            $model = config('file-cleaner.model');
            $this->model = app($model);

            if (!is_a($this->model, Model::class)) {
                throw new InvalidArgumentException("Model [{$model}] should be an instance of " . Model::class);
            }

            $this->fileField = config('file-cleaner.file_field_name');
            $this->relation = config('file-cleaner.relation');
        }

        return $this;
    }

    /**
     * Set Up
     *
     * @return FileCleaner
     */
    protected function setUp()
    {
        $this->timeBeforeRemove = $this->option('force') ? -1
            : config('file-cleaner.time_before_remove', self::DEFAULT_TIME_BEFORE_REMOVE);

        if ($directories = $this->option('directories')) {
            $this->readPathsFromConsole($directories);
        }

        if ($excludedDirectory = $this->option('excluded-paths')) {
            $this->readExcludedPathsFromConsole($excludedDirectory);
        }

        if ($excludedFiles = $this->option('excluded-files')) {
            $this->readExcludedFilesFromConsole($excludedFiles);
        }

        $this->removeDirectories = ($removeDirectories = $this->option('remove-directories'))
            ? ($removeDirectories == "false" && $removeDirectories !== true ? false : true)
            : config('file-cleaner.remove_directories', true);

        return $this;
    }

    /**
     * Remove files and directories from specified path
     *
     * @param string $path
     *
     * @return FileCleaner
     */
    protected function clear($path)
    {
        if ($this->filesystem->exists($path)) {
            $this->removeFiles(
                $this->filesystem->allFiles($path)
            );

            if ($this->removeDirectories === true) {
                $this->removeDirectories(
                    $this->filesystem->directories($path)
                );
            }
        } else {
            $this->warn('Directory ' . $path . ' does not exists');
        }

        return $this;
    }

    /**
     * Remove Files
     *
     * @param array $files
     *
     * @return FileCleaner
     */
    protected function removeFiles(array $files)
    {
        foreach ($files as $file) {
            // File fresh.
            if (Carbon::createFromTimestamp($file->getMTime())
                    ->diffInMinutes(Carbon::now()) <= $this->timeBeforeRemove
            ) {
                continue;
            }

            // File should be excluded.
            if (in_array($file->getPath(), $this->excludedPaths)
                || in_array($filename = $file->getRealPath(), $this->excludedFiles)
            ) {
                continue;
            }

            if (!is_null($this->model) && !is_null($this->fileField)) {
                $model = $this->model->where($this->fileField, $file->getBasename())->first();
            }

            if (static::$voter) {
                $decision = call_user_func_array(static::$voter, [$file, isset($model) ? $model : null]);

                if (false === $decision) {
                    continue;
                }

                if (true === $decision) {
                    $this->info("Voter decision: 'Delete file: {$file->getRealPath()}'");
                    $this->deleteFile($filename, $file->getBasename());
                    continue;
                }
            }

            // If relation option set, then we remove files only if there is no related instance(s).
            if (!is_null($this->relation)) {
                if (!isset($model)) {
                    throw new ModelNotFoundException(
                        sprintf(
                            "'Instance of [%s] not found with '%s' by '%s' field.'",
                            is_object($this->model) ? get_class($this->model) : $this->model,
                            $file->getBasename(),
                            $this->fileField
                        )
                    );
                }

                $related = $model->{$this->relation};

                if (is_null($related) || ($related instanceof Collection && $related->isEmpty())) {
                    $this->info("File instance without relation: {$file->getRealPath()}");
                    $this->deleteFile($filename, $file->getBasename());
                }
            } else {
                $this->deleteFile($filename, $file->getBasename());
            }
        }

        return $this;
    }

    /**
     * Remove Directories
     *
     * @param array $directories
     *
     * @return FileCleaner
     */
    protected function removeDirectories(array $directories)
    {
        foreach ($directories as $dir) {
            if (!count($this->filesystem->allFiles($dir))) {
                $this->filesystem->deleteDirectory($dir);
                $this->info('Deleted directory: ' . $dir);
                $this->countRemovedDirectories++;
            }
        }

        return $this;
    }

    /**
     * Display how much files and directories totally were removed
     *
     * @return FileCleaner
     */
    protected function outputResultCounts()
    {
        if (!$this->countRemovedFiles && !$this->countRemovedDirectories) {
            $this->info('Nothing to delete. All files are fresh.');
        } else {
            if ($this->countRemovedFiles) {
                $this->info('Deleted ' . $this->countRemovedFiles . ' file(s)');
            }
            if ($this->countRemovedDirectories) {
                $this->info('Deleted ' . $this->countRemovedDirectories . ' directory(ies).');
            }
            if ($this->countRemovedInstances) {
                $this->info('Deleted ' . $this->countRemovedInstances . ' instance(s).');
            }
        }

        return $this;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    protected function deleteModelInstances($filename)
    {
        if (is_null($this->model) || is_null($this->fileField)) {
            return false;
        }

        if ($instances = $this->model->where($this->fileField, $filename)->get()) {
            foreach ($instances as $instance) {
                $instance->delete();
                $this->countRemovedInstances++;
            }
        }

        return true;
    }

    /**
     * Delete File
     *
     * @param string $filename
     * @param string $fileBaseName
     *
     * @return FileCleaner
     */
    protected function deleteFile($filename, $fileBaseName)
    {
        $this->filesystem->delete($filename);
        $this->deleteModelInstances($fileBaseName);
        $this->info('Deleted file: ' . $filename);
        $this->countRemovedFiles++;

        return $this;
    }

    /**
     * Read Paths From Console
     *
     * @param string $directories
     *
     * @return FileCleaner
     */
    protected function readPathsFromConsole($directories)
    {
        $this->paths = explode(',', $directories);

        return $this->setRealDirectoryPaths();
    }

    /**
     * Read Excluded Paths From Console
     *
     * @param string $paths
     *
     * @return FileCleaner
     */
    protected function readExcludedPathsFromConsole($paths)
    {
        $this->excludedPaths = explode(',', $paths);

        return $this->setRealExcludedDirectoryPaths();
    }

    /**
     * Read Excluded Files From Console
     *
     * @param string $paths
     *
     * @return FileCleaner
     */
    protected function readExcludedFilesFromConsole($paths)
    {
        $this->excludedFiles = explode(',', $paths);

        return $this->setRealExcludedFilesPaths();
    }

    /**
     * Set Real Paths
     *
     * @return FileCleaner
     */
    protected function setRealPaths()
    {
        return $this->setRealDirectoryPaths()
            ->setRealExcludedDirectoryPaths()
            ->setRealExcludedFilesPaths();
    }

    /**
     * Set Real Directory Paths
     *
     * @return FileCleaner
     */
    private function setRealDirectoryPaths()
    {
        if ($count = count($this->paths)) {
            for ($i = 0; $i < $count; $i++) {
                $this->paths[$i] = realpath(base_path($this->paths[$i])) ?: $this->paths[$i];
            }
        }

        return $this;
    }

    /**
     * Set Real Excluded Directory Paths
     *
     * @return FileCleaner
     */
    private function setRealExcludedDirectoryPaths()
    {
        if ($count = count($this->excludedPaths)) {
            for ($i = 0; $i < $count; $i++) {
                $this->excludedPaths[$i] = realpath(base_path($this->excludedPaths[$i])) ?: $this->excludedPaths[$i];
            }
        }

        return $this;
    }

    /**
     * Set Real Excluded Files Paths
     *
     * @return FileCleaner
     */
    private function setRealExcludedFilesPaths()
    {
        if ($count = count($this->excludedFiles)) {
            for ($i = 0; $i < $count; $i++) {
                $this->excludedFiles[$i] = realpath(base_path($this->excludedFiles[$i])) ?: $this->excludedFiles[$i];
            }
        }

        return $this;
    }
}
