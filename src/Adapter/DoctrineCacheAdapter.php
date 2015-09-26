<?php

/**
 * @package    contao-flysystem
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\Contao\Flysystem\Adapter;

use Doctrine\Common\Cache\Cache;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * Class DoctrineCacheAdapter.
 *
 * @package Netzmacht\Contao\Flysystem\Adapter
 */
class DoctrineCacheAdapter extends AbstractAdapter
{
    /**
     * File adapter being cached.
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * Doctrine cache.
     *
     * @var Cache
     */
    private $cache;

    /**
     * Cache life time.
     *
     * @var int
     */
    private $lifeTime = 0;

    /**
     * DoctrineCacheAdapter constructor.
     *
     * @param AdapterInterface $adapter  The adapter.
     * @param Cache            $cache    Cache.
     * @param int              $lifeTime Cache lifetime.
     */
    public function __construct(AdapterInterface $adapter, Cache $cache, $lifeTime=0)
    {
        $this->adapter  = $adapter;
        $this->cache    = $cache;
        $this->lifeTime = $lifeTime;
    }

    /**
     * @inheritDoc
     */
    public function write($path, $contents, Config $config)
    {
        $success = $this->adapter->write($path, $contents, $config);

        if ($success) {
            $this->cache->delete($this->getCacheKey($path));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        $success = $this->adapter->writeStream($path, $resource, $config);

        if ($success) {
            $this->cache->delete($this->getCacheKey($path));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $config)
    {
        $success = $this->adapter->update($path, $contents, $config);

        if ($success) {
            $this->cache->delete($this->getCacheKey($path));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        $success = $this->adapter->updateStream($path, $resource, $config);

        if ($success) {
            $this->cache->delete($this->getCacheKey($path));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function rename($path, $newpath)
    {
        $success = $this->adapter->rename($path, $newpath);

        if ($success) {
            $this->cache->delete($this->getCacheKey($path));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath)
    {
        return $this->adapter->copy($path, $newpath);
    }

    /**
     * @inheritDoc
     */
    public function delete($path)
    {
        $success = $this->adapter->delete($path);

        if ($success) {
            $this->cache->delete($this->getCacheKey($path));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname)
    {
        $success = $this->adapter->deleteDir($dirname);

        if ($success) {
            $this->cache->delete($this->getCacheKey($dirname));
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function createDir($dirname, Config $config)
    {
        return $this->adapter->createDir($dirname, $config);
    }

    /**
     * @inheritDoc
     */
    public function setVisibility($path, $visibility)
    {
        $success = $this->adapter->setVisibility($path, $visibility);

        if ($success) {
            $cached = $this->getCacheKey($path);

            $cached['metadata']['visibility'] = $visibility;
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function has($path)
    {
        $key = $this->getCacheKey($path);
        if ($this->cache->contains($key)) {
            return true;
        }

        return $this->adapter->has($path);
    }

    /**
     * @inheritDoc
     */
    public function read($path)
    {
        return $this->adapter->read($path);
    }

    /**
     * @inheritDoc
     */
    public function readStream($path)
    {
        return $this->adapter->readStream($path);
    }

    /**
     * @inheritDoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        $metadata = $this->getCached($directory);
        $contents = $metadata['contents'];

        if ($recursive) {
            foreach ($contents as $object) {
                if ($object['type'] === 'dir') {
                    $contents = array_merge($contents, $this->listContents($object['path'], true));
                }
            }
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($path)
    {
        $metadata = $this->getCached($path);

        return $metadata['metadata'];
    }

    /**
     * @inheritDoc
     */
    public function getSize($path)
    {
        $metadata = $this->getCached($path);

        return $metadata['metadata']['size'];
    }

    /**
     * @inheritDoc
     */
    public function getMimetype($path)
    {
        $metadata = $this->getCached($path);

        return $metadata['metadata']['visibility'];
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp($path)
    {
        $metadata = $this->getMetadata($path);

        return $metadata['metadata']['timestamp'];
    }

    /**
     * @inheritDoc
     */
    public function getVisibility($path)
    {
        $metadata = $this->getCached($path);

        return $metadata['metadata']['visibility'];
    }

    /**
     * Get the cached item.
     *
     * @param string $path The file path.
     *
     * @return array|false|mixed|void
     */
    private function getCached($path)
    {
        $key = $this->getCacheKey($path);
        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        $metadata['metadata'] = $this->adapter->getMetadata($path);
        if ($metadata['metadata']['type'] === 'dir') {
            $metadata['contents'] = $this->adapter->listContents($path);
        }

        $this->cache->save($key, $metadata, $this->lifeTime);

        return $metadata;
    }

    /**
     * Create the cache key.
     *
     * @param string $path
     *
     * @return string
     */
    private function getCacheKey($path)
    {
        if ($this->adapter instanceof ProvidesCacheKey) {
            return $this->adapter->getCacheKey($path);
        }

        return md5($path);
    }
}
