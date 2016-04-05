<?php

namespace MasterRO\LaravelFileCleaner;

use PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase{

    protected $configs;

    /**
     * TestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->configs = $this->getConfigs();
    }

    /**
     * @return array
     */
    protected function getConfigs(){
        return [
            'paths' => [
                'temp\images',
            ],

            'time_before_remove' => 0,

            'model' => null,

            'file_field_name' => null,
        ];
    }

    
    
    
}