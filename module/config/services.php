<?php

/**
 * @package    contao-flysystem
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Netzmacht\Contao\Flysystem\Adapter\DbafsAdapter;

global $container;

/**
 * The filesystem local provides access to the TL_ROOT directory.
 *
 * Override the configuration below to customize the way it works.
 */

/*
 * Local file system link handling.
 *
 * @see http://flysystem.thephpleague.com/adapter/local/#links-added-in-108
 */
$container['flysystem.local.linkHandling'] = Local::DISALLOW_LINKS;

/*
 * Local file system lock option.
 *
 * @see http://flysystem.thephpleague.com/adapter/local/#locks
 */
$container['flysystem.local.lock'] = LOCK_EX;

/*
 * File and directory permission settings.
 *
 * @see http://flysystem.thephpleague.com/adapter/local/#file-and-directory-permission-settings-added-in-1014
 */
$container['flysystem.local.permissions'] = array();

/*
 * Local file system adapter.
 */
$container['flysystem.local.adapter'] = $container->share(
    function ($container) {
        return new Local(TL_ROOT, $container['flysystem.local.lock'], $container['flysystem.local.permissions']);
    }
);

/*
 * Local file system.
 */
$container['flysystem.local.file-system'] = $container->share(
    function ($container) {
        return new Filesystem($container['flysystem.local.adapter']);
    }
);


/**
 * The database driven filesystem.
 */

/*
 * Dbafs file system config.
 */
$container['flysystem.dbafs.upload-path'] = function ($container) {
    return $container['config']->get('uploadPath');
};

/*
 * Dbafs file system adapter.
 */
$container['flysystem.dbafs.adapter'] = $container->share(
    function ($container) {
        return new DbafsAdapter($container['flysystem.local.adapter'], $container['flysystem.dbafs.upload-path']);
    }
);

/*
 * Dbafs file system.
 */
$container['flysystem.dbafs.file-system'] = $container->share(
    function ($container) {
        return new Filesystem($container['flysystem.dbafs.adapter']);
    }
);

/**
 * Flysystem integration.
 */

/*
 * File systems registry. Add your filesystem to $container['flysystem.file-systems'].
 */
$container['flysystem.file-systems'] = $container->share(
    function ($container) {
        $fileSystems          = new \ArrayObject();
        $fileSystems['local'] = $container['flysystem.local.file-system'];
        $fileSystems['dbafs'] = $container['flysystem.dbafs.file-system'];

        return $fileSystems;
    }
);

/*
 * Main mount manager. You should only access the flysystem integration through the mount manager!
 */
$container['flysystem.mount-manager'] = $container->share(
    function ($container) {
        $fileSystems = $container['flysystem.file-systems']->getArrayCopy();

        return new MountManager($fileSystems);
    }
);
