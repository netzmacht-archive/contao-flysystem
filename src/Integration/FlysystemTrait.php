<?php

/**
 * @package    contao-flysystem
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\Flysystem\Integration;

use League\Flysystem\MountManager;

/**
 * This trait provides easy access to the fly system integration.
 *
 * It's recommend to use dependency injection where possible! This is just a helper for classes which can't get loaded
 * from the DI.
 *
 * @package Netzmacht\Contao\Flysystem\Integration
 */
trait FlysystemTrait
{
    /**
     * Get the mount manager.
     *
     * @return MountManager
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getMountManager()
    {
        return $GLOBALS['container']['flysystem.mount-manager'];
    }
}
