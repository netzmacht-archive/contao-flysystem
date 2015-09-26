<?php

/**
 * @package    contao-flysystem
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Plugin;
use Netzmacht\Contao\Flysystem\Adapter\DbafsAdapter;
use Netzmacht\Contao\Flysystem\Adapter\DoctrineCacheAdapter;

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
 * File system plugins registry.
 */
$container['flysystem.local.plugins'] = $container->share(
    function () {
        $plugins   = new \ArrayObject();
        $plugins[] = new Plugin\GetWithMetadata();
        $plugins[] = new Plugin\ListFiles();
        $plugins[] = new Plugin\ListPaths();
        $plugins[] = new Plugin\ListWith();
        $plugins[] = new Plugin\EmptyDir();

        return $plugins;
    }
);

/*
 * Local file system adapter.
 */
$container['flysystem.local.adapter.default'] = $container->share(
    function ($container) {
        return new Local(TL_ROOT, $container['flysystem.local.lock'], $container['flysystem.local.permissions']);
    }
);

if (!isset($container['flysystem.local.adapter'])) {
    $container['flysystem.local.adapter'] = $container->share(
        function ($container) {
            return $container['flysystem.local.adapter.default'];
        }
    );
}

/*
 * Local file system.
 */
$container['flysystem.local.file-system'] = $container->share(
    function ($container) {
        $fileSystem = new Filesystem($container['flysystem.local.adapter']);

        foreach ($container['flysystem.local.plugins'] as $plugin) {
            $fileSystem->addPlugin($plugin);
        }

        return $fileSystem;
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
 * Dbafs default file system adapter.
 */
$container['flysystem.dbafs.adapter.default'] = $container->share(
    function ($container) {
        return new DbafsAdapter($container['flysystem.local.adapter'], $container['flysystem.dbafs.upload-path']);
    }
);

/*
 * Dbafs cached file system adapter.
 */
$container['flysystem.dbafs.cache.file'] = $container->share(
    function () {
        return new FilesystemCache(TL_ROOT . '/system/cache/flysystem/dbafs');
    }
);

$container['flysystem.dbafs.cache.array'] = $container->share(
    function () {
        return new ArrayCache();
    }
);

$container['flysystem.dbafs.cache.apc'] = $container->share(
    function () {
        return new ApcCache();
    }
);

$container['flysystem.dbafs.adapter.cached'] = $container->share(
    function ($container) {
        return new DoctrineCacheAdapter(
            $container['flysystem.dbafs.adapter.default'],
            $container['flysystem.dbafs.cache.' . $container['config']->get('flysystem_cache')],
            $container['config']->get('flysystem_lifetime') ?: 0
        );
    }
);

/*
 * The adapter entry point.
 */
if (!isset($container['flysystem.dbafs.adapter'])) {
    $container['flysystem.dbafs.adapter'] = $container->share(
        function ($container) {
            if ($container['config']->get('flysystem_cache')) {
                return $container['flysystem.dbafs.adapter.cached'];
            }

            return $container['flysystem.dbafs.adapter.default'];
        }
    );
}

/*
 * File system plugins registry.
 */
$container['flysystem.dbafs.plugins'] = $container->share(
    function () {
        $plugins   = new \ArrayObject();
        $plugins[] = new Plugin\GetWithMetadata();
        $plugins[] = new Plugin\ListFiles();
        $plugins[] = new Plugin\ListPaths();
        $plugins[] = new Plugin\ListWith();
        $plugins[] = new Plugin\EmptyDir();

        return $plugins;
    }
);

/*
 * Dbafs file system.
 */
$container['flysystem.dbafs.file-system'] = $container->share(
    function ($container) {
        $fileSystem = new Filesystem($container['flysystem.dbafs.adapter']);

        foreach ($container['flysystem.dbafs.plugins'] as $plugin) {
            $fileSystem->addPlugin($plugin);
        }

        return $fileSystem;
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
