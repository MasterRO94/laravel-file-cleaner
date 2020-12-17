<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg">
</p>

<p align="center">
    <a href="https://packagist.org/packages/masterro/laravel-file-cleaner">
        <img src="https://img.shields.io/packagist/v/masterro/laravel-file-cleaner.svg?style=flat-rounded" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/masterro/laravel-file-cleaner">
        <img src="https://img.shields.io/packagist/dt/masterro/laravel-file-cleaner.svg?style=flat-rounded" alt="Total Downloads">
    </a>
    <a href="https://github.com/MasterRO94/laravel-file-cleaner/actions">
        <img src="https://github.com/MasterRO94/laravel-file-cleaner/workflows/Tests/badge.svg" alt="Build Status">
    </a>
    <a href="https://github.com/MasterRO94/laravel-file-cleaner/blob/master/LICENSE.txt">
        <img src="https://img.shields.io/github/license/MasterRO94/laravel-file-cleaner" alt="License">
    </a>
</p>

# Laravel File Cleaner

Laravel File Cleaner is a package for Laravel 5+ that provides deleting files and associated model instances.

## Installation

### Step 1: Composer

From the command line, run:

```
composer require masterro/laravel-file-cleaner
```

### Step 2: Service Provider (For Laravel < 5.5)

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
* `paths` - Paths where temp files are storing (or will be storing), relative to root directory
* `excluded_paths` -  Excluded directory paths where nothing would be deleted, relative to root directory
* `excluded_files` - Excluded files path that would not be deleted, relative to root directory
* `time_before_remove` - Time after which the files will be deleted | _default **60** minutes_
* `model` - Model which instances will be deleted with associated files | _optional_
* `file_field_name` - Field name that contains the name of the removing file | _optional, **only if model set**_
* `remove_directories` - Remove directories flag, if set to true all nested directories would be removed | _default **true**_
* `relation` - Relation, remove files and model instances only if model instance does not have a set relation

### Voter callback
Additionally, you can set a static voter callback or invokable object to have more power on controlling deletion logic.  
You can register it in one of yours Service providers. The callback will be called after `time_before_remove` and `excluded_*` checks.

```php
FileCleaner::voteDeleteUsing(function($path, $entity) {
    if (isset($entity) && !$entity->user->isActive()) {
        return true;
    }

    return false;
});
```

If callback return `true` file and optionally associated record in db will be removed.  
If callback return `false` file and record won't be removed.  
Otherwise `relation` check will be performed. 
   

## Usage

### Scheduling

Add new command call to schedule function:
> Have a look at [Laravel's task scheduling documentation](https://laravel.com/docs/scheduling), if you need any help.

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

#### _I will be grateful if you star this project :)_
