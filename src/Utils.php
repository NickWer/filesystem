<?php

namespace Bolt\Filesystem;

use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Filesystem\Iterator\RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Utils
{
    public static function copy(FileInterface $origin, FileInterface $target, $override = null)
    {
        if ($target->exists() &&
            (
                $override === false ||
                (
                    $override === null &&
                    $origin->getTimestamp() <= $target->getTimestamp()
                )
            )
        ) {
            return;
        }

        $buffer = $origin->readStream();
        $target->putStream($buffer);

        $buffer->close();
    }

    public static function mirror(DirectoryInterface $origin, DirectoryInterface $target, $config = [])
    {
        if ($origin->getFilesystem() === $target->getFilesystem()) {
            $origin->mirror($target->getPath(), $config);
            return;
        }

        $config += [
            'delete'   => false,
            'override' => null,
        ];

        if ($config['delete']) {
            $it = new RecursiveDirectoryIterator($origin->getFilesystem(), $origin->getPath());
            $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $handler) {
                $originPath = str_replace($target->getPath(), $origin->getPath(), $handler->getPath());
                if ($handler instanceof DirectoryInterface && !$origin->getFilesystem()->getProfile()->supportsDirs()) {
                    continue;
                }
                if ($origin->getFilesystem()->has($originPath)) {
                    continue;
                }
                if ($handler instanceof DirectoryInterface) {
                    $target->getFilesystem()->deleteDir($originPath);
                } else {
                    $target->getFilesystem()->delete($originPath);
                }
            }
        }

        if ($origin->exists()) {
            $target->create();
        }

        $it = new RecursiveDirectoryIterator($origin->getFilesystem(), $origin->getPath());
        $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $handler) {
            $targetPath = str_replace($origin->getPath(), $target->getPath(), $handler->getPath());
            if ($handler instanceof DirectoryInterface) {
                $target->getFilesystem()->createDir($targetPath, $config);
            } else {
                static::copy($handler, $target->getFilesystem()->getFile($targetPath), $config['override']);
            }
        }
    }
}
