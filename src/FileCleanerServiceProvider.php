<?php

namespace MasterRO\LaravelFileCleaner;

use Illuminate\Support\ServiceProvider;

class FileCleanerServiceProvider extends ServiceProvider
{

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/file-cleaner.php' => config_path('file-cleaner.php'),
		]);

		if ($this->app->runningInConsole()) {
			$this->commands([
				FileCleaner::class,
			]);
		}
	}


	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}