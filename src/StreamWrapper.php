<?php

namespace Bolt\Filesystem;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use League\Flysystem;

/**
 * Wraps Filesystem into stream protocol.
 *
 * Based on AWS's S3 implementation.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class StreamWrapper
{
    /**
     * @internal
     * @var resource
     */
    public $context;

    /** @var File|Directory for current path */
    private $handler;
    /** @var string The opened protocol */
    private $protocol;
    /** @var string The opened path */
    private $path;

    /** @var \Iterator Iterator used with directory related calls */
    private $iterator;

    /** @var Cache */
    private $cache;

    /**
     * Registers a stream protocol. If the protocol is already registered, it is replaced.
     *
     * @param FilesystemInterface $filesystem
     * @param string              $protocol
     * @param Cache|null          $cache
     */
    public static function register(FilesystemInterface $filesystem, $protocol = 'flysystem', Cache $cache = null)
    {
        if (in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }

        stream_wrapper_register($protocol, get_called_class(), STREAM_IS_URL);

        $default = stream_context_get_options(stream_context_get_default());
        $default[$protocol]['filesystem'] = $filesystem;

        if ($cache) {
            $default[$protocol]['cache'] = $cache;
        } elseif (!isset($default[$protocol]['cache']) || $default[$protocol]['cache'] === null) {
            $default[$protocol]['cache'] = new ArrayCache();
        }

        stream_context_set_default($default);
    }

    /**
     * Removes the stream protocol, if it exists.
     *
     * @param string $protocol
     */
    public static function unregister($protocol)
    {
        if (!in_array($protocol, stream_get_wrappers())) {
            return;
        }
        stream_wrapper_unregister($protocol);

        $default = stream_context_get_options(stream_context_get_default());
        foreach ($default[$protocol] as $key => $value) {
            $default[$protocol][$key] = null;
        }
        stream_context_set_default($default);
    }

    /**
     * Gets a filesystem handler for the given path.
     *
     * If the handler is not given, the path needs to exist
     * to determine if it is a directory or file.
     *
     * @param string            $path    In the form of protocol://path
     * @param Flysystem\Handler $handler An optional handler to populate
     *
     * @return Directory|File
     */
    public static function getHandler($path, $handler = null)
    {
        if (strpos($path, '://') == 0) { // == is intentional to check for false
            throw new \InvalidArgumentException('Path needs to be in the form of protocol://path');
        }
        list($protocol, $path) = explode('://', $path, 2);

        $default = stream_context_get_options(stream_context_get_default());
        if (!isset($default[$protocol]['filesystem']) || $default[$protocol]['filesystem'] === null) {
            throw new \RuntimeException('Filesystem does not exist for that protocol');
        }
        $filesystem = $default[$protocol]['filesystem'];
        if (!$filesystem instanceof FilesystemInterface) {
            throw new \UnexpectedValueException(
                'Filesystem needs to be an instance of Bolt\Filesystem\FilesystemInterface'
            );
        }

        return $filesystem->get($path, $handler);
    }

    /**
     * @internal Use {@see StreamWrapper::register} instead.
     */
    public function __construct()
    {
    }


    /**
     * Support for {@see dir()} and {@see opendir()}
     *
     * @param string $path The path to the directory
     *
     * @return bool
     *
     * @see http://www.php.net/manual/en/function.opendir.php
     */
    public function dir_opendir($path)
    {
        $this->init($path);

        $handler = $this->getThisHandler();
        if ($handler === false) {
            return false;
        }

        $this->iterator = new \ArrayIterator($handler->getContents());

        return true;
    }

    /**
     * Used with {@see readdir()}
     *
     * @return bool|string The next filename or false if there is no next file.
     *
     * @link http://www.php.net/manual/en/function.readdir.php
     */
    public function dir_readdir()
    {
        if (!$this->iterator || !$this->iterator->valid()) {
            return false;
        }

        /** @var File|Directory $handler */
        $handler = $this->iterator->current();

        // Cache the object data for quick url_stat lookups used with RecursiveDirectoryIterator
        $key = $this->getFullPath($handler->getPath());
        $stat = $this->createStat($handler);
        $this->getCache()->save($key, $stat);

        $this->iterator->next();

        // To emulate other stream wrappers we need to strip $this->path
        // (current directory open) from $path (file in directory)
        $path = $handler->getPath();
        if ($this->path) {
            $path = substr($path, strlen($this->path) + 1);
        }

        return $path;
    }

    /**
     * Used with {@see rewinddir()}
     *
     * @return bool
     *
     * @link http://www.php.net/manual/en/function.rewinddir.php
     */
    public function dir_rewinddir()
    {
        $this->iterator->rewind();

        return true;
    }

    /**
     * Used with {@see closedir()}
     *
     * @return bool
     *
     * @link http://www.php.net/manual/en/function.closedir.php
     */
    public function dir_closedir()
    {
        $this->iterator = null;
        gc_collect_cycles();

        return true;
    }


    public function mkdir($path, $mode, $options)
    {
    }

    public function rename($path_from, $path_to)
    {
    }

    public function rmdir($path, $options)
    {
    }

    public function unlink($path)
    {
    }


    public function stream_open($path, $mode, $options, &$opened_path)
    {
    }

    public function stream_close()
    {
    }

    public function stream_flush()
    {
    }

    public function stream_read($count)
    {
    }

    public function stream_write($data)
    {
    }

    public function stream_tell()
    {
    }

    public function stream_eof()
    {
    }

    public function stream_seek($offset, $whence)
    {
    }

    public function stream_stat()
    {
    }

    /**
     * @internal
     *
     * Provides information for is_dir, is_file, filesize, etc.
     *
     * Note: class variables are not populated.
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array
     *
     * @link http://php.net/manual/en/streamwrapper.url-stat.php
     */
    public function url_stat($path, $flags)
    {
        $this->init($path);

        // Check if this path is in cache
        if ($value = $this->getCache()->fetch($path)) {
            return $value;
        }

        $handler = $this->getThisHandler(null, $flags);
        if (!$handler || is_array($handler)) {
            return $handler;
        }

        $stat = $this->createStat($handler, $flags);

        if (is_array($stat)) {
            $this->getCache()->save($path, $stat);
        }

        return $stat;
    }

    /**
     * Sets the protocol and path variables.
     *
     * @param string $path
     */
    private function init($path)
    {
        list($this->protocol, $this->path) = explode('://', $path, 2);
    }

    /**
     * Returns the path with the protocol.
     *
     * @param string $path Optional path to use instead of current.
     *
     * @return string
     */
    private function getFullPath($path = null)
    {
        return $this->protocol . '://' . ($path ?: $this->path);
    }

    /**
     * Creates a url_stat array with the given handler.
     *
     * @param File|Directory $handler
     * @param int            $flags
     *
     * @return array
     */
    private function createStat($handler, $flags = null)
    {
        return $this->boolCall(function () use ($handler) {
            $stat = $this->getStatTemplate();

            if ($handler->isDir()) {
                $stat['mode'] = $stat[2] = 0040777;
            } else {
                $stat['mode'] = $stat[2] = 0100666;
                $stat['size'] = $stat[7] = $handler->getSize();
            }
            $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10] = $handler->getTimestamp();

            return $stat;
        }, $flags);
    }

    /**
     * Gets a URL stat template with default values.
     *
     * @return array
     */
    private function getStatTemplate()
    {
        return [
            0  => 0,  'dev'     => 0,
            1  => 0,  'ino'     => 0,
            2  => 0,  'mode'    => 0,
            3  => 0,  'nlink'   => 0,
            4  => 0,  'uid'     => 0,
            5  => 0,  'gid'     => 0,
            6  => -1, 'rdev'    => -1,
            7  => 0,  'size'    => 0,
            8  => 0,  'atime'   => 0,
            9  => 0,  'mtime'   => 0,
            10 => 0,  'ctime'   => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks'  => -1,
        ];
    }

    /**
     * @param Flysystem\Handler $handler Optional handler, to skip file exists check
     * @param int  $flags
     *
     * @return array|Directory|File|false
     */
    private function getThisHandler($handler = null, $flags = null)
    {
        if (!$this->handler) {
            $this->handler = $this->boolCall(function () use ($handler) {
                return static::getHandler($this->getFullPath(), $handler);
            }, $flags);
        }

        return $this->handler;
    }

    private function getCache()
    {
        if (!$this->cache) {
            $this->cache = $this->getOption('cache') ?: new ArrayCache();
        }

        return $this->cache;
    }

    private function getOption($name)
    {
        $options = $this->getOptions();

        return isset($options[$name]) ? $options[$name] : null;
    }

    private function getOptions()
    {
        if ($this->context === null) {
            $options = [];
        } else {
            $options = stream_context_get_options($this->context);
            $options = isset($options[$this->protocol]) ? $options[$this->protocol] : [];
        }

        $default = stream_context_get_options(stream_context_get_default());
        $default = isset($default[$this->protocol]) ? $default[$this->protocol] : [];
        $result = $options + $default;

        return $result;
    }

    private function boolCall(callable $fn, $flags = null)
    {
        try {
            return $fn();
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage(), $flags);
        }
    }

    /**
     * Triggers one or more errors.
     *
     * @param string|array $errors Errors to trigger
     * @param int          $flags  If set to STREAM_URL_STAT_QUIET, then no error or exception occurs
     *
     * @return array|bool
     */
    private function triggerError($errors, $flags = null)
    {
        // This is triggered with things like file_exists()
        if ($flags & STREAM_URL_STAT_QUIET) {
            // This is triggered for things like is_link()
            if ($flags & STREAM_URL_STAT_LINK) {
                return $this->getStatTemplate();
            }

            return false;
        }

        // This is triggered when doing things like lstat() or stat()
        trigger_error(implode("\n", (array) $errors), E_USER_WARNING);

        return false;
    }
}
