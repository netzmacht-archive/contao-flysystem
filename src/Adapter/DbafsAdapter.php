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

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

/**
 * The DbafsAdapter provides access to the database driven file system of Contao.
 * 
 * @package Netzmacht\Contao\Flysystem\Adapter
 */
class DbafsAdapter extends AbstractAdapter
{
    /**
     * @var FileSystemInterface
     */
    private $fileSystem;

    /**
     * The base path.
     *
     * @var string
     */
    private $basePath;

    /**
     * DbafsAdapter constructor.
     *
     * @param FilesystemInterface $fileSystem The local file system.
     * @param string              $basePath   The base path is usually the contao upload path.
     */
    public function __construct(FilesystemInterface $fileSystem, $basePath = 'files')
    {
        $this->fileSystem = $fileSystem;
        $this->basePath   = $basePath . '/';
    }

    /**
     * {@inheritDoc}
     */
    public function write($path, $contents, Config $config)
    {
        if ($this->fileSystem->write($this->basePath. $path, $contents, $config)) {
            \Dbafs::addResource($path);
            
            return true;
        }
        
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        if ($this->fileSystem->writeStream($this->basePath. $path, $resource, $config)) {
            \Dbafs::addResource($path);

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function update($pathOrUuid, $contents, Config $config)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        if ($this->fileSystem->update($this->basePath. $path, $contents, $config)) {
            \Dbafs::addResource($this->basePath. $path);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStream($pathOrUuid, $resource, Config $config)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        if ($this->fileSystem->updateStream($this->basePath. $path, $resource, $config)) {
            \Dbafs::addResource($this->basePath. $path);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($pathOrUuid, $newpath)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        
        if ($this->fileSystem->rename($this->basePath. $path, $this->basePath. $newpath)) {
            \Dbafs::moveResource($this->basePath. $path, $this->basePath. $newpath);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function copy($pathOrUuid, $newpath)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        if ($this->fileSystem->copy($this->basePath. $path, $this->basePath. $newpath)) {
            \Dbafs::copyResource($this->basePath . $path, $this->basePath . $newpath);

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        
        if ($this->fileSystem->delete($this->basePath . $path)) {
            \Dbafs::deleteResource($this->basePath . $path);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDir($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        if ($this->fileSystem->deleteDir($this->basePath . $path)) {
            \Dbafs::deleteResource($this->basePath . $path);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function createDir($dirname, Config $config)
    {
        if ($this->fileSystem->createDir($this->basePath. $dirname, $config)) {
            \Dbafs::addResource($this->basePath. $dirname);

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setVisibility($pathOrUuid, $visibility)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->setVisibility($this->basePath . $path, $visibility);
    }

    /**
     * {@inheritDoc}
     */
    public function has($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        if ($path === false) {
            return false;
        }

        return $this->fileSystem->has($this->basePath . $path);
    }

    /**
     * {@inheritDoc}
     */
    public function read($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->read($this->basePath . $path);
    }

    /**
     * {@inheritDoc}
     */
    public function readStream($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->read($path);
    }

    /**
     * {@inheritDoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        if ($directory) {
            $raw       = $directory;
            $directory = $this->convertToPath($raw);
            $this->guardNoInvalidUuid($raw, $directory);
        }

        return $this->fileSystem->listContents($this->basePath . $directory, $recursive);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        $metadata = $this->fileSystem->getMetadata($this->basePath . $path);
        if ($metadata && \Validator::isUuid($pathOrUuid)) {
            $model            = \FilesModel::findByUuid($metadata);
            $metadata['meta'] = deserialize($model->meta, true);
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->getSize($this->basePath . $path);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimetype($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->getMimetype($this->basePath . $path);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimestamp($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->getTimestamp($this->basePath . $path);
    }

    /**
     * {@inheritDoc}
     */
    public function getVisibility($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);

        return $this->fileSystem->getVisibility($this->basePath . $path);
    }

    /**
     * Convert to a path.
     * 
     * @param mixed $path Path or uuid.
     *
     * @return string|false
     */
    private function convertToPath($path)
    {
        if (\Validator::isUuid($path)) {
            $model = \FilesModel::findByUuid($path);
            
            if ($model) {
                return $model->path;
            }
            
            return false;
        }
        
        return $path;
    }

    /**
     * Guard that the given uuid is valid.
     *
     * @param mixed  $pathOrUuid Given path or uuid.
     * @param string $path       The converted path.
     *
     * @throws FileNotFoundException
     */
    private function guardNoInvalidUuid($pathOrUuid, $path)
    {
        if ($path === false) {
            throw new FileNotFoundException($this->stringifyUuid($pathOrUuid));
        }
    }

    /**
     * Stringify an uuid.
     *
     * @param mixed $uuid Given uuid.
     *
     * @return string
     */
    private function stringifyUuid($uuid)
    {
        if (\Validator::isBinaryUuid($uuid)) {
            return \String::binToUuid($uuid);
        }

        return $uuid;
    }
}
