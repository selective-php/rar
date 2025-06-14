# selective/rar

RAR file reader for PHP.

[![Latest Version on Packagist](https://img.shields.io/github/release/selective-php/rar.svg)](https://packagist.org/packages/selective/rar)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://github.com/selective-php/rar/workflows/build/badge.svg)](https://github.com/selective-php/rar/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/selective/rar.svg)](https://packagist.org/packages/selective/rar/stats)

## Features

* Read RAR file information
* RAR 5 archive format
* RAR 4 archive format
* No dependencies
* Very fast

Note: This package does not support extracting / unpacking rar archives.

## Requirements

* PHP 8.1 - 8.4

> The [PECL RAR package](https://www.php.net/manual/en/book.rar.php) is **NOT** required

## Installation

```
composer require selective/rar
```

## Usage

### Open RAR file

```php
use Selective\Rar\RarFileReader;
use SplFileObject;

$rarFileReader = new RarFileReader();
$rarArchive = $rarFileReader->openFile(new SplFileObject('test.rar'));

foreach ($rarArchive->getEntries() as $entry) {
    echo $entry->getName() . "\n";
}
```

### Open in-memory RAR file

```php
use Selective\Rar\RarFileReader;
use SplTempFileObject;

$file = new SplTempFileObject();
$file->fwrite('my binary rar content');

$rarFileReader = new RarFileReader();
$rarArchive = $rarFileReader->openFile($file);

foreach ($rarArchive->getEntries() as $entry) {
    echo $entry->getName() . "\n";
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
