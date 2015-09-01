<?php
namespace Bolt\Filesystem;

use InvalidArgumentException;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Handler;
use League\Flysystem\Plugin\PluggableTrait;
use LogicException;

class Manager implements AggregateFilesystemInterface, FilesystemInterface
{
    use PluggableTrait;

    /** @var FilesystemInterface[] */
    protected $filesystems = [];

    /**
     * Constructor.
     *
     * @param array $filesystems
     */
    public function __construct(array $filesystems = [])
    {
        $this->mountFilesystems($filesystems);
    }

    /**
     * {@inheritdoc}
     */
    public function mountFilesystems(array $filesystems)
    {
        foreach ($filesystems as $prefix => $filesystem) {
            if (!$filesystem instanceof FilesystemInterface) {
                $filesystem = $this->createFilesystem($filesystem);
            }
            $this->mountFilesystem($prefix, $filesystem);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        if (!is_string($prefix)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects $prefix argument to be a string.');
        }

        $this->filesystems[$prefix] = $filesystem;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesystem($prefix)
    {
        if (!isset($this->filesystems[$prefix])) {
            throw new LogicException('No filesystem mounted with prefix ' . $prefix);
        }

        return $this->filesystems[$prefix];
    }

    /**
     * @inheritDoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        list($prefix, $directory) = $this->filterPrefix($directory);
        $filesystem = $this->getFilesystem($prefix);
        $result = $filesystem->listContents($directory, $recursive);

        foreach ($result as &$file) {
            $file['filesystem'] = $prefix;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        list($prefixFrom, $pathFrom) = $this->filterPrefix($path);

        $fsFrom = $this->getFilesystem($prefixFrom);
        $buffer = $fsFrom->readStream($pathFrom);

        if ($buffer === false) {
            return false;
        }

        list($prefixTo, $pathTo) = $this->filterPrefix($newpath);

        $fsTo = $this->getFilesystem($prefixTo);
        $result = $fsTo->writeStream($pathTo, $buffer);

        if (is_resource($buffer)) {
            fclose($buffer);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->has($path);
    }

    /**
     * @inheritDoc
     */
    public function read($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->read($path);
    }

    /**
     * @inheritDoc
     */
    public function readStream($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->readStream($path);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getMetadata($path);
    }

    /**
     * @inheritDoc
     */
    public function getSize($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getSize($path);
    }

    /**
     * @inheritDoc
     */
    public function getMimetype($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getMimetype($path);
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getTimestamp($path);
    }

    /**
     * @inheritDoc
     */
    public function getVisibility($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getVisibility($path);
    }

    /**
     * @inheritDoc
     */
    public function write($path, $contents, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->write($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->writeStream($path, $resource, $config);
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->update($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->updateStream($path, $resource, $config);
    }

    /**
     * @inheritDoc
     */
    public function rename($path, $newpath)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->rename($path, $newpath);
    }

    /**
     * @inheritDoc
     */
    public function delete($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->delete($path);
    }

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname)
    {
        list($prefix, $path) = $this->filterPrefix($dirname);
        return $this->getFilesystem($prefix)->deleteDir($path);
    }

    /**
     * @inheritDoc
     */
    public function createDir($dirname, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($dirname);
        return $this->getFilesystem($prefix)->createDir($path, $config);
    }

    /**
     * @inheritDoc
     */
    public function setVisibility($path, $visibility)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->setVisibility($path, $visibility);
    }

    /**
     * @inheritDoc
     */
    public function put($path, $contents, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->put($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function putStream($path, $resource, array $config = [])
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->putStream($path, $resource, $config);
    }

    /**
     * @inheritDoc
     */
    public function readAndDelete($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->readAndDelete($path);
    }

    /**
     * @inheritDoc
     */
    public function get($path, Handler $handler = null)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->get($path, $handler);
    }

    /**
     * @inheritDoc
     */
    public function getImage($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getImage($path);
    }

    /**
     * @inheritDoc
     */
    public function getImageInfo($path)
    {
        list($prefix, $path) = $this->filterPrefix($path);
        return $this->getFilesystem($prefix)->getImageInfo($path);
    }

    /**
     * Creates a local filesystem if path exists, else a null filesystem.
     *
     * @param string $path
     *
     * @return Filesystem
     */
    protected function createFilesystem($path)
    {
        return new Filesystem(is_dir($path) ? new Local($path) : new NullAdapter());
    }

    /**
     * Separates the filesystem prefix from the path.
     *
     * @param string $path
     *
     * @return array [prefix, path]
     */
    protected function filterPrefix($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('First argument should be a string');
        }

        if (!preg_match('#^.+\:\/\/.*#', $path)) {
            throw new InvalidArgumentException('No prefix detected in path: ' . $path);
        }

        return explode('://', $path, 2);
    }
}
