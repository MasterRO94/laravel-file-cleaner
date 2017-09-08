<?php

namespace Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
	/**
	 * This adds support for php 5.5
	 *
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
	 * This adds support for php 5.5
	 *
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
