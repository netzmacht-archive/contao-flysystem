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
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\RootViolationException;

/**
 * The DbafsAdapter provides access to the database driven file system of Contao.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DbafsAdapter extends AbstractAdapter implements ProvidesCacheKey
{
    /**
     * The local file system adapter.
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * The base path.
     *
     * @var string
     */
    private $uploadPath;

    /**
     * DbafsAdapter constructor.
     *
     * @param AdapterInterface $adapter    The local file system adapter.
     * @param string           $uploadPath Base path is the uploadPath of Contao.
     */
    public function __construct(AdapterInterface $adapter, $uploadPath = 'files')
    {
        $this->adapter    = $adapter;
        $this->uploadPath = $uploadPath;
    }

    /**
     * {@inheritDoc}
     */
    public function write($path, $contents, Config $config)
    {
        $this->guardInUploadPath($path);

        if ($this->adapter->write($path, $contents, $config)) {
            $this->addResource($path);
            return true;
        }
        
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $this->guardInUploadPath($path);

        if ($this->adapter->writeStream($path, $resource, $config)) {
            $this->addResource($path);
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
        $this->guardInUploadPath($path);

        if ($this->adapter->update($path, $contents, $config)) {
            $this->addResource($path);
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
        $this->guardInUploadPath($path);

        if ($this->adapter->updateStream($path, $resource, $config)) {
            $this->addResource($path);
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
        $this->guardInUploadPath($path);
        $this->guardInUploadPath($newpath);

        if ($this->adapter->rename($path, $newpath)) {
            \Dbafs::moveResource($path, $newpath);

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
        $this->guardInUploadPath($path);
        $this->guardInUploadPath($newpath);

        if ($this->adapter->copy($path, $newpath)) {
            \Dbafs::copyResource($path, $newpath);

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
        $this->guardInUploadPath($path);
        
        if ($this->adapter->delete($path)) {
            \Dbafs::deleteResource($path);

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
        $this->guardInUploadPath($path);

        if ($this->adapter->deleteDir($path)) {
            \Dbafs::deleteResource($path);
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function createDir($dirname, Config $config)
    {
        $this->guardInUploadPath($dirname);

        if ($this->adapter->createDir($dirname, $config)) {
            $this->addResource($dirname);
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
        $this->guardInUploadPath($path);

        return $this->adapter->setVisibility($path, $visibility);
    }

    /**
     * {@inheritDoc}
     */
    public function has($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardInUploadPath($path);

        if ($path === false) {
            return false;
        }

        return $this->adapter->has($path);
    }

    /**
     * {@inheritDoc}
     */
    public function read($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        $this->guardInUploadPath($path);

        return $this->adapter->read($path);
    }

    /**
     * {@inheritDoc}
     */
    public function readStream($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        $this->guardInUploadPath($path);

        return $this->adapter->read($path);
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
        } else {
            $directory = $this->uploadPath;
        }

        $this->guardInUploadPath($directory);

        return $this->adapter->listContents($directory, $recursive);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        $this->guardInUploadPath($path);

        $metadata = $this->adapter->getMetadata($path);
        if ($metadata) {
            if (\Validator::isUuid($pathOrUuid)) {
                $model = \FilesModel::findByUuid($pathOrUuid);
            } else {
                $model = \FilesModel::findOneBy('path', $path);
            }

            if ($model) {
                $metadata['id']   = (int) $model->id;
                $metadata['uuid'] = \String::binToUuid($model->uuid);
                $metadata['hash'] = $model->hash;
                $metadata['meta'] = deserialize($model->meta, true);

                $metadata['importantPart'] = array(
                    'x'      => $model->importantPartX,
                    'y'      => $model->importantPartY,
                    'width'  => $model->importantPartWidth,
                    'height' => $model->importantPartHeight
                );
            }
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
        $this->guardInUploadPath($path);

        return $this->adapter->getSize($path);
    }

    /**
     * {@inheritDoc}
     */
    public function getMimetype($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        $this->guardInUploadPath($path);

        return $this->adapter->getMimetype($path);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimestamp($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        $this->guardInUploadPath($path);

        return $this->adapter->getTimestamp($path);
    }

    /**
     * {@inheritDoc}
     */
    public function getVisibility($pathOrUuid)
    {
        $path = $this->convertToPath($pathOrUuid);
        $this->guardNoInvalidUuid($pathOrUuid, $path);
        $this->guardInUploadPath($path);

        return $this->adapter->getVisibility($path);
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheKey($path)
    {
        $path = $this->convertToPath($path);
        if ($path === false) {
            return null;
        }

        return md5($path);
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
     * @return void
     * @throws FileNotFoundException When file is not found.
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

    /**
     * Guard that the path is in the upload path.
     *
     * @param string $path Given path.
     *
     * @return void
     * @throws RootViolationException When path is not in the upload path.
     */
    private function guardInUploadPath($path)
    {
        if (substr($path, 0, strlen($this->uploadPath)) !== $this->uploadPath) {
            throw new RootViolationException(sprintf('Path "%s" is not in upload path', $path));
        }
    }

    /**
     * Add resource to the database driven file system.
     *
     * @param string $path Given path.
     *
     * @return void
     *
     * @throws \Exception When adding resource failed.
     */
    private function addResource($path)
    {
        $fileModel = \FilesModel::findByPath($path);

        if ($fileModel === null) {
            \Dbafs::addResource($fileModel);
        }
    }
}
