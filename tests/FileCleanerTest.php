<?php

namespace MasterRO\LaravelFileCleaner;

class FileCleanerTest extends TestCase
{
    
    /** @st */
    public function it_gets_all_files()
    {
        $files = $this->filesystem->allFiles($this->filesDir);
        
        $this->assertCount(3, $files);
    }


    /** @test */
    public function it_deletes_needed_files()
    {
        $fileCleaner = new FileCleaner($this->filesystem);
        
        $fileCleaner->handle();
    }
    
}
