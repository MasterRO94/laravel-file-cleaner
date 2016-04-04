<?php

namespace MasterRO\LaravelFilesCleaner;

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
    protected $signature = 'file-cleaner:clean {--f|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all temp or other files from disk';


    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var string|null
     */
    protected $model = null;

    /**
     * @var string|null
     */
    protected $file_field = null;

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
        if($count = count($this->paths)){
            for ($i=0; $i<$count; $i++) {
                $this->paths[$i] = realpath(base_path($this->paths[$i]));
            }
        }

        if( ! is_null(config('file-cleaner.model'))){
            $model = config('file-cleaner.model');
            $this->model = new $model;
            $this->file_field = config('file-cleaner.file_field_name');
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
        
        if( ! count($this->paths)) $this->info('Nothing to delete.');

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
        $this->removeFiles(
            $this->filesystem->allFiles($path)
        );

        $this->removeDirectories(
            $this->filesystem->directories($path)
        );
    }


    /**
     * @param array $files
     */
    protected function removeFiles(array $files)
    {
        foreach ($files as $file) {
            if(Carbon::createFromTimestamp($file->getMTime())->diffInMinutes(Carbon::now()) > $this->timeBeforeRemove){
                $this->filesystem->delete($filename = $file->getRealPath());
                $this->deleteDocument($file->getBasename());
                $this->info('Deleted file: ' . $filename);
                $this->countRemovedFiles++;
            }
        }
    }


    /**
     * @param array $directories
     */
    protected function removeDirectories(array $directories)
    {
        foreach ($directories as $dir) {
            if( ! count($this->filesystem->allFiles($dir))){
                $this->filesystem->deleteDirectory($dir);
                $this->info('Deleted directory: ' . $dir);
                $this->countRemovedDirectories++;
            }
        }
    }


    /**
     * Display how much files and directories totally were removed
     */
    private function outputResultCounts()
    {
        if( ! $this->countRemovedFiles && ! $this->countRemovedDirectories) {
            $this->info('Nothing to delete. All files are fresh.');
        }else{
            if($this->countRemovedFiles) {
                $this->info('Deleted ' . $this->countRemovedFiles . ' file(s)');
            }
            if($this->countRemovedDirectories){
                $this->info('Deleted ' . $this->countRemovedDirectories . ' directory(ies).');
            }
            if($this->countRemovedInstances){
                $this->info('Deleted ' . $this->countRemovedInstances . ' instance(s).');
            }
        }
    }


    /**
     * @param $name
     * @return bool
     */
    private function deleteDocument($name)
    {
        if(is_null($this->model) || is_null($this->file_field)) return false;

        if($instances = $this->model->where($this->file_field, $name)->get()){
            foreach ($instances as $instance) {
                $instance->delete();
                $this->countRemovedInstances++;
            }
        }
    }
}