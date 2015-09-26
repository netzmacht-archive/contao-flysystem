<?php

/**
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\Flysystem\Adapter;

/**
 * ProvidesCacheKey indicates adapters which take care of the cache key creation.
 *
 * @package Netzmacht\Contao\Flysystem\Adapter
 */
interface ProvidesCacheKey
{
    /**
     * Get the cache key.
     *
     * @param string $path The path.
     *
     * @return string
     */
    public function getCacheKey($path);
}
