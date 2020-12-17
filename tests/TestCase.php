<?php

namespace MasterRO\LaravelFileCleaner\Tests;

use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\DirectoryExists;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Asserts that a file does not exist.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public static function assertFileDoesNotExist(string $filename, string $message = ''): void
    {
        static::assertThat($filename, new LogicalNot(new FileExists), $message);
    }

    /**
     * Asserts that a directory does not exist.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public static function assertDirectoryDoesNotExist(string $directory, string $message = ''): void
    {
        static::assertThat($directory, new LogicalNot(new DirectoryExists), $message);
    }
}
