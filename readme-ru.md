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

LaravelFileCleaner - это пакет для Laravel 5, который позвлоляет удалять временные файлы и связанные с ними сущности модели (при необходимости).

## Установка

### ШАГ 1: Composer

В коммандной строке:

```
composer require masterro/laravel-file-cleaner
```

### ШАГ 2: Service Provider (Для версии Laravel < 5.5)

Откройте файл `config/app.php` и добавьте в массив `providers` :

```
MasterRO\LaravelFileCleaner\FileCleanerServiceProvider::class
```

Таким образом мы подключим пакет в автозагрузку Laravel.

### ШАГ 3: Конфигурация

Для начала в коммандной строке пишем:

```
php artisan vendor:publish --provider="MasterRO\LaravelFileCleaner\FileCleanerServiceProvider"
```

После чего в директории `config` появится файл `file-cleaner.php`

Для текущей версии пакета доступны слледующие настройки:
* Массив путей к папкам, где храняться (или будут хранится) файлы для удаления | пути относительно корневого каталога.
* Массив путей к папкам, файлы и одпапки которых не будут удалены | пути относительно корневого каталога.
* Массив путей к файлам, которые не будут удалены | пути относительно корневого каталога.
* Время, после которого файлы будут удалены | _по умолчанию **60** минут_
* Модель, сущности которой будут удалены вместе с привязанными файлами | _не обязательно_
* Имя поля в таблице модели, которое хранит имя привязанного файла | _не обязательно, **работает только если указана модель**_
* Флаг указывающий на то удалять или не удалять пустые папки после удаления файлов | _по умолчанию **true**_
* Релейшн, если указан то файлы и сущности будут удалены только в случае  если связанной сущности нет

## Использование

### Scheduling

Добавьте вызов команды в фукцию `schedule`:
> [Документация по Task Scheduling](https://laravel.com/docs/5.2/scheduling), если есть вопросы.

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('file-cleaner:clean')->everyMinute();
}
```

И это все что нужно для работы пакета. Если вы настроили крон правильновсе будет работать.


### Вручную, используя Artisan Console

Вы можете запустить удаление вручную прописав в консоли:
```
php artisan file-cleaner:clean
```
Удаляться только те файлы, которые храняться больше указанного в настройках времени.


Или если нужно удалить все файлы без проверки на время (просто удалить все файлы из указанных директорий):
```
php artisan file-cleaner:clean -f
```

Вы даже можете переопределить значения конфига `paths`, `excluded_paths` и `excluded_files` используя `--directories`, `--excluded-paths` and `--excluded-files` options (разделяя запятой):
```
php artisan file-cleaner:clean -f --directories=storage/temp/images,public/uploads/test
```
```
php artisan file-cleaner:clean -f --excluded-paths=public/uploads/images/default,public/uploads/test
```
```
php artisan file-cleaner:clean -f --excluded-files=public/uploads/images/default.png,public/uploads/test/01.png
```

Также можно переопределить значени конфига `remove_directories` используя `--remove-directories` option:
```
php artisan file-cleaner:clean -f --directories=storage/temp/images,public/uploads/test --remove-directories=false
```

#### _Буду признателен за звезды :)_