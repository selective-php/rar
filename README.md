# selective/rar

RAR file reader for PHP.

[![Latest Version on Packagist](https://img.shields.io/github/release/selective-php/rar.svg?style=flat-square)](https://packagist.org/packages/selective/rar)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/selective-php/rar/master.svg?style=flat-square)](https://travis-ci.org/selective-php/rar)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/selective-php/rar.svg?style=flat-square)](https://scrutinizer-ci.com/g/selective-php/rar/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/quality/g/selective-php/rar.svg?style=flat-square)](https://scrutinizer-ci.com/g/selective-php/rar/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/selective/rar.svg?style=flat-square)](https://packagist.org/packages/selective/rar/stats)

## Features

* Read RAR file information
* No dependencies
* No installed RAR package required
* Very fast

## Requirements

* PHP 7.2+

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

* MIT
