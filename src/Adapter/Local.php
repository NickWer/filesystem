<?php

namespace Bolt\Filesystem\Adapter;

use Bolt\Filesystem\Exception\DirectoryCreationException;
use Bolt\Filesystem\Exception\IncludeFileException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\SupportsIncludeFileInterface;
use League\Flysystem\Adapter\Local as LocalBase;
use League\Flysystem\Config;

class Local extends LocalBase implements SupportsIncludeFileInterface
{
    /**
     * {@inheritdoc}
     */
    protected function ensureDirectory($root)
    {
        if (!is_dir($root)) {
            $umask = umask(0);
            $result = @mkdir($root, $this->permissionMap['dir']['public'], true);
            umask($umask);

            if (!$result) {
                throw new DirectoryCreationException($root);
            }
        }

        return realpath($root);
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, Config $config)
    {
        return $this->dumpContents('write', $path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->dumpContents('writeStream', $path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->dumpContents('update', $path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * Persist data to a file.
     *
     * @param string          $output
     * @param string          $path
     * @param string|resource $contents
     * @param Config          $config
     */
    private function dumpContents($output, $path, $contents, Config $config)
    {
        set_error_handler(
            function ($errno, $errstr) use ($path) {
                throw new IOException($errstr, $path, $errno);
            }
        );

        if ($output === 'write') {
            $result = parent::write($path, $contents, $config);
        } elseif ($output === 'writeStream') {
            $result = parent::writeStream($path, $contents, $config);
        } elseif ($output === 'update') {
            $result = parent::update($path, $contents, $config);
        }

        restore_error_handler();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        if (!is_writable($location)) {
            return false;
        }

        return unlink($location);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $location = $this->applyPathPrefix($dirname);
        $umask = umask(0);
        $visibility = $config->get('visibility', 'public');

        if (!is_dir($location) && !@mkdir($location, $this->permissionMap['dir'][$visibility], true)) {
            $return = false;
        } else {
            $return = ['path' => $dirname, 'type' => 'dir'];
        }

        umask($umask);

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $location = $this->applyPathPrefix($dirname);
        if (!is_dir($location) || !is_writable($location)) {
            return false;
        }

        return parent::deleteDir($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function includeFile($path, $once = true)
    {
        $location = $this->applyPathPrefix($path);

        set_error_handler(
            function ($num, $message) use ($path) {
                throw new IncludeFileException($message, $path);
            }
        );

        if ($once) {
            $result = includeFileOnce($location);
        } else {
            $result = includeFile($location);
        }

        restore_error_handler();

        return $result;
    }
}

/**
 * Scope isolated include.
 *
 * Prevents access to $this/self from included files.
 *
 * @param string $file
 *
 * @return mixed
 */
function includeFile($file)
{
    /** @noinspection PhpIncludeInspection */
    return include $file;
}

/**
 * Scope isolated include_once.
 *
 * Prevents access to $this/self from included files.
 *
 * @param string $file
 *
 * @return mixed
 */
function includeFileOnce($file)
{
    /** @noinspection PhpIncludeInspection */
    return include_once $file;
}
