# LaravelFileCleaner

LaravelFileCleaner - это пакет для Laravel 5.1-5.2, который позвлоляет удалять временные файлы и связанные с ними сущности модели (при необходимости).

## Установка

### ШАГ 1: Composer

В коммандной строке:

```
composer require masterro/laravel-file-cleaner
```

### ШАГ 2: Service Provider

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
* Время, после которого файлы будут удалены | _по умолчанию **60** минут_
* Модель, сущности которой будут удалены вместе с привязанными файлами | _не обязательно_
* Имя поля в таблице модели, которое хранит имя привязанного файла | _не обязательно, **работает только если указана модель**_

## Использование

В Command/Kernel.php добавьте `FileCleaner::class`:

```php
protected $commands = [
    \MasterRO\LaravelFileCleaner\FileCleaner::class,
];
```

Затем добавьте вызов команды в фукцию `schedule`:
> [Документация по Task Scheduling](https://laravel.com/docs/5.2/scheduling), если есть вопросы.

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('file-cleaner:clean')->everyMinute();
}
```

И это все что нужно для работы пакета. Если вы настроили крон правильновсе будет работать.



Вы можете запустить удаление вручную прописав в консоли:
```
php artisan file-cleaner:clean
```
Удаляться только те файлы, которые храняться больше указанного в настройках времени.


Или если нужно удалить все файлы без проверки на время (просто удалить все файлы из указанных директорий):
```
php artisan file-cleaner:clean -f
```








