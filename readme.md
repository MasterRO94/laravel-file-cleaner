<p align="center">
    <img src="https://laravel.com/assets/img/components/logo-laravel.svg">
</p>

<p align="center">
    <a href="https://packagist.org/packages/masterro/laravel-file-cleaner">
        <img src="https://poser.pugx.org/masterro/laravel-file-cleaner/v/stable" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/masterro/laravel-file-cleaner">
        <img src="https://poser.pugx.org/masterro/laravel-file-cleaner/downloads" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/masterro/laravel-file-cleaner">
        <img src="https://poser.pugx.org/masterro/laravel-file-cleaner/v/unstable" alt="Latest Unstable Version">
    </a>
    <a href="https://github.com/MasterRO94/laravel-chronos/blob/master/LICENSE">
        <img src="https://poser.pugx.org/masterro/laravel-file-cleaner/license" alt="License">
    </a>
    <a href="https://github.com/MasterRO94/laravel-chronos/blob/master/LICENSE">
        <img src="https://poser.pugx.org/masterro/laravel-file-cleaner/composerlock" alt="composer.lock">
    </a>
</p>

# LaravelFileCleaner

LaravelFileCleaner is a package for Laravel 5 that provides deleting temp files and associated model instances(if needed).

## Installation

### Step 1: Composer

From the command line, run:

```
composer require masterro/laravel-file-cleaner
```

### Step 2: Service Provider

For your Laravel app, open `config/app.php` and, within the `providers` array, append:

```
MasterRO\LaravelFileCleaner\FileCleanerServiceProvider::class
```

This will bootstrap the package into Laravel.

### Step 3: Publish Configs

First from the command line, run:

```
php artisan vendor:publish --provider="MasterRO\LaravelFileCleaner\FileCleanerServiceProvider"
```

After that you will see `file-cleaner.php` file in config directory

For this package you may set such configurations:
* Paths where temp files are storing (or will be storing), relative to root directory
* Excluded directory paths where nothing would be deleted, relative to root directory
* Excluded files path that would not be deleted, relative to root directory
* Time after which the files will be deleted | _default **60** minutes_
* Model which instances will be deleted with associated files | _optional_
* Field name that contains the name of the removing file | _optional, **only if model set**_
* Remove directories flag, if set to true all nested directories would be removed | _default **true**_

## Usage

### Scheduling
In your Command kernel file add `FileCleaner::class`:

```php
protected $commands = [
    \MasterRO\LaravelFileCleaner\FileCleaner::class,
];
```

Then add new command call to schedule function:
> Have a look at [Laravel's task scheduling documentation](https://laravel.com/docs/5.2/scheduling), if you need any help.

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('file-cleaner:clean')->everyMinute();
}
```

And that's all. If your cron set up everything will work.


### Manual, using artisan console

You can run deleting manually, just run from the command line:
```
php artisan file-cleaner:clean
```
And see the output.


Or if you want to delete files without checking time (just delete all files from all set directories) use the --force flag (or -f shortcut):
```
php artisan file-cleaner:clean -f
```

You can even override config directories `paths`, `excluded_paths` and `excluded_files` values with `--directories`, `--excluded-paths` and `--excluded-files` options (separate by comma):
```
php artisan file-cleaner:clean -f --directories=storage/temp/images,public/uploads/test
```
```
php artisan file-cleaner:clean -f --excluded-paths=public/uploads/images/default,public/uploads/test
```
```
php artisan file-cleaner:clean -f --excluded-files=public/uploads/images/default.png,public/uploads/test/01.png
```


Also you can even override `remove_directories` config value with `--remove-directories` option:
```
php artisan file-cleaner:clean -f --directories=storage/temp/images,public/uploads/test --remove-directories=false
```








