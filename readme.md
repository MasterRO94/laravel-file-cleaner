# LaravelFileCleaner

LaravelFileCleaner is a package for Laravel that provides deleting temp files and associated model instances(if needed).

## Installation

### Step 1: Composer

From the command line, run:

```
masterro/laravel-file-cleaner
```

### Step 2: Service Provider

For your Laravel app, open `config/app.php` and, within the `providers` array, append:

```
MasterRO\LaravelFileCleaner\FileCleanerServiceProvider::class
```

This will bootstrap the package into Laravel.

### Step 3: Publish Configs

First type

```
php artisan vendor:publish --provider="MasterRO\LaravelFileCleaner\FileCleanerServiceProvider"
```

After that you will see _**file-cleaner.php**_ file in config directory

For this package you may set such configurations:
* Paths where temp files are storing (or will be storing), relative to root directory
* Time after which the files will be deleted | _default 60 minutes_
* Model which instances will be deleted with associated files | _optional_
* Field name that contains the name of the removing file | _optional, **only if model set**_

## Usage

In your Command kernel file add _**FileCleaner::class**_

```php
protected $commands = [
    \MasterRO\LaravelFileCleaner\FileCleaner::class,
];
```

Then add new command call to schedule function

```php
protected $commands = [
    $schedule->command('file-cleaner:clean')->everyMinute();
];
```

And that's all. If your cron set up everything will work.



You can run command manually by command
```
php artisan file-cleaner:clean
```
And see the output.


Or if you want to delete files without checking time

```
php artisan file-cleaner:clean -f
```








