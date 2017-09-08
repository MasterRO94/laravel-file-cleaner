<?php

class TestCase extends \Orchestra\Testbench\TestCase
{
	/**
	 * @param string $directory
	 * @param string $message
	 */
	public static function assertDirectoryExists($directory, $message = '')
	{
		if (method_exists(parent::class, 'assertDirectoryExists')) {
			parent::assertDirectoryExists($directory, $message);
		} else {
			static::assertFileExists($directory, $message);
		}
	}


	/**
	 * @param string $directory
	 * @param string $message
	 */
	public static function assertDirectoryNotExists($directory, $message = '')
	{
		if (method_exists(parent::class, 'assertDirectoryNotExists')) {
			parent::assertDirectoryNotExists($directory, $message);
		} else {
			static::assertFileNotExists($directory, $message);
		}
	}
}
