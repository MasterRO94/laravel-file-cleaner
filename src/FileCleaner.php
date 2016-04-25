<?php

namespace MasterRO\LaravelFileCleaner;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class FileCleaner extends Command
{
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
     * @var bool
     */
    protected $removeDirectories = true;

    /**
     * Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @int $timeAfterRemove
     */
    protected $timeBeforeRemove;


    /**
     * @int $countRemovedFiles
     */
    protected $countRemovedFiles = 0;

    /**
     * @int $countRemovedDirectories
     */
    protected $countRemovedDirectories = 0;

    /**
     * @int $countRemovedInstances
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

        $this->paths = config('file-cleaner.paths', []);
        $this->excludedPaths = config('file-cleaner.excluded_paths', []);
        $this->excludedFiles = config('file-cleaner.excluded_files', []);
        $this->setRealPaths();

        if (!is_null(config('file-cleaner.model'))) {
            $model = config('file-cleaner.model');
            $this->model = new $model;
            $this->fileField = config('file-cleaner.file_field_name');
        }

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->timeBeforeRemove = $this->option('force') ? -1 : config('file-cleaner.time_before_remove', 60);

        if ($directories = $this->option('directories')) {
            $this->getPathsFromConsole($directories);
        }

        if ($excludedDirectory = $this->option('excluded-paths')) {
            $this->getExcludedPathsFromConsole($excludedDirectory);
        }

        if ($excludedFiles = $this->option('excluded-files')) {
            $this->getExcludedFilesFromConsole($excludedFiles);
        }

        $this->removeDirectories = ($removeDirectories = $this->option('remove-directories'))
            ? ($removeDirectories == "false" ? false : true)
            : config('file-cleaner.remove_directories', true);

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
     * @param $path
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
    }


    /**
     * @param array $files
     */
    protected function removeFiles(array $files)
    {
        foreach ($files as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->diffInMinutes(Carbon::now()) > $this->timeBeforeRemove) {

                if (! in_array($file->getPath(), $this->excludedPaths)
                    && ! in_array($filename = $file->getRealPath(), $this->excludedFiles)) {

                    $this->filesystem->delete($filename);
                    $this->deleteDocument($file->getBasename());
                    $this->info('Deleted file: ' . $filename);
                    $this->countRemovedFiles++;
                }
            }
        }
    }


    /**
     * @param array $directories
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
    }


    /**
     * Display how much files and directories totally were removed
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
    }


    /**
     * @param $name
     * @return bool
     */
    protected function deleteDocument($name)
    {
        if (is_null($this->model) || is_null($this->fileField)) return false;

        if ($instances = $this->model->where($this->fileField, $name)->get()) {
            foreach ($instances as $instance) {
                $instance->delete();
                $this->countRemovedInstances++;
            }
        }

        return true;
    }


    /**
     * @param $directories
     */
    protected function getPathsFromConsole($directories)
    {
        $directories = explode(',', $directories);

        $this->paths = $directories;

        $this->setRealDirectoryPaths();
    }


    /**
     * @param $paths
     */
    protected function getExcludedPathsFromConsole($paths)
    {
        $paths = explode(',', $paths);

        $this->excludedPaths = $paths;

        $this->setRealExcludedDirectoryPaths();
    }


    /**
     * @param $paths
     */
    protected function getExcludedFilesFromConsole($paths)
    {
        $paths = explode(',', $paths);

        $this->excludedFiles = $paths;

        $this->setRealExcludedFilesPaths();
    }


    /**
     * Set real directories paths
     */
    protected function setRealPaths()
    {
        $this->setRealDirectoryPaths();
        $this->setRealExcludedDirectoryPaths();
        $this->setRealExcludedFilesPaths();
    }


    private function setRealDirectoryPaths()
    {
        if ($count = count($this->paths)) {
            for ($i = 0; $i < $count; $i++) {
                $this->paths[$i] = realpath(base_path($this->paths[$i])) ?: $this->paths[$i];
            }
        }
    }


    private function setRealExcludedDirectoryPaths()
    {
        if ($count = count($this->excludedPaths)) {
            for ($i = 0; $i < $count; $i++) {
                $this->excludedPaths[$i] = realpath(base_path($this->excludedPaths[$i])) ?: $this->excludedPaths[$i];
            }
        }
    }


    private function setRealExcludedFilesPaths()
    {
        if ($count = count($this->excludedFiles)) {
            for ($i = 0; $i < $count; $i++) {
                $this->excludedFiles[$i] = realpath(base_path($this->excludedFiles[$i])) ?: $this->excludedFiles[$i];
            }
        }
    }
}
