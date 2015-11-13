Contao Flysystem integration
============================

[![Build Status](http://img.shields.io/travis/netzmacht/contao-flysystem/master.svg?style=flat-square)](https://travis-ci.org/netzmacht/contao-flysystem)
[![Version](http://img.shields.io/packagist/v/netzmacht/contao-flysystem.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-flysystem)
[![License](http://img.shields.io/packagist/l/netzmacht/contao-flysystem.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-flysystem)
[![Downloads](http://img.shields.io/packagist/dt/netzmacht/contao-flysystem.svg?style=flat-square)](http://packagist.com/packages/netzmacht/contao-flysystem)
[![Contao Community Alliance coding standard](http://img.shields.io/badge/cca-coding_standard-red.svg?style=flat-square)](https://github.com/contao-community-alliance/coding-standard)

This library integrates the [flysystem filesystem abstraction](http://flysystem.thephpleague.com) into Contao.

It provides a fully configured filesystem service for `TL_ROOT` and an adapter for the Dbafs of Contao!

Install
-------

You can install this library using Composer. It requires at least PHP 5.4 and Contao 3.2.

```
$ php composer.phar require netzmacht/contao-flysystem:~1.0
```

Features
--------

 * Filesystem abstraction for whole Contao installation
 * Dbafs adapter
 * Metadata caching
  

Usage
-----

### Access to the mount manager and the default file system

```
<?php

// First get the mount manager to get the file system.
$manager    = $GLOBALS['container']['flysystem.mount-manager'];
$fileSystem = $manager->getFilesystem('local');

// Alternatively use the FlysystemTrait
class MyContentElement
{
   use Netzmacht\Contao\Flysystem\Integration\FlysystemTrait;
   
   protected function compile()
   {
      $fileSystem = $this->getMountManager()->getFilesystem('local');
   }
}
```

### Dbafs file system

For the dbafs filesystem exists an separate filesystem adapter. It allows to fetch files by path or uuid.
The meta data is enriched with model details `id, uuid, hash, meta, importantPart`.

It keeps the dbafs in sync when performing file system actions.

```
<?php
 
$manager    = $GLOBALS['container']['flysystem.mount-manager'];
$fileSystem = $manager->getFilesystem('dbafs');

// Get file path.
$metadata = $fileSystem->getMetadata('files/path/to/file');

// Get file by binary uuid.
$metadata = $fileSystem->getMetadata($this->singleSRC);

// Get file by binary uuid.
$metadata = $fileSystem->getMetadata(\String::binToUuid($this->singleSRC));

```

Note: You have to prepend the file path with the upload path as this is the Contao standard. If you try to access a 
file out of the upload path scope you'll get an `RootViolationException`.
