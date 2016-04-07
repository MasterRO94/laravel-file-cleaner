<?php

namespace MasterRO\LaravelFileCleaner;

use PHPUnit_Framework_TestCase;
use Illuminate\Filesystem\Filesystem;

abstract class TestCase extends PHPUnit_Framework_TestCase{

    protected $configs;
    protected $filesystem;
    protected $filesDir;

    /**
     * TestCase constructor.
     */
    public function setUp()
    {
        $this->filesystem = new Filesystem;
        $this->configs = $this->getConfigs();
        $this->filesDir = __DIR__ . '/' . $this->configs['paths'][0];
        $this->setUpLaravelHelperFunctions();
        $this->createFiles();
    }


    public function tearDown()
    {
//        $this->removeFiles();
    }

    /**
     * @return array
     */
    protected function getConfigs(){
        return [
            'paths' => [
                'temp\files',
            ],

            'time_before_remove' => 0,

            'model' => null,

            'file_field_name' => null,
        ];
    }


    protected function createFiles()
    {
        $this->filesystem->makeDirectory($this->filesDir, 0777, true);
        
        $this->filesystem->put($this->filesDir . '/file1.txt', 'file1');
        $this->filesystem->put($this->filesDir . '/file2.txt', 'file2');
        $this->filesystem->put($this->filesDir . '/file3.txt', 'file3');
        
        
    }

    
    protected function removeFiles()
    {
        $this->filesystem->deleteDirectory($this->filesDir);
        $this->filesystem->deleteDirectory(__DIR__ . '/temp');
    }



    private function setUpLaravelHelperFunctions()
    {
        if( ! function_exists('config')) {
            function config($key, $default = null, $configs = [
                'paths' => [
                    'temp\files',
                ],

                'time_before_remove' => 0,

                'model' => null,

                'file_field_name' => null,
            ])
            {
                switch ($key) {
                    case 'file-cleaner.paths' :
                        return $configs['paths'];
                        break;
                    case 'file-cleaner.time_before_remove' :
                        return $configs['time_before_remove'];
                        break;
                    case 'file-cleaner.model' :
                        return $configs['model'];
                        break;
                    case 'file-cleaner.file_field_name' :
                        return $configs['file_field_name'];
                        break;
                }

                return $default;
            }
        }

        if( ! function_exists('base_path')) {
            function base_path($path = '')
            {
                return __DIR__ . '/' . $path;
            }
        }
    }


}