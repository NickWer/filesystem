<?php

namespace Bolt\Filesystem\Iterator;

use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\FilesystemInterface;
use RecursiveIteratorIterator;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobFilterIterator;

/**
 * Returns filesystem handlers matching a glob.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class GlobIterator extends \IteratorIterator
{
    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem The filesystem to search
     * @param string              $glob       The glob pattern
     * @param int                 $flags      A bitwise combination of the flag constants in {@see Glob}
     */
    public function __construct(FilesystemInterface $filesystem, $glob, $flags = 0)
    {
        // Glob code requires absolute paths, so prefix path
        // with leading slash, but not before mount point
        if (strpos($glob, '://') > 0) {
            $glob = str_replace('://', ':///', $glob);
        } else {
            $glob = '/' . ltrim($glob, '/');
        }

        $basePath = Glob::getBasePath($glob, $flags);

        if (!Glob::isDynamic($glob)) {
            // If the glob is a file path, return that path if it exists.
            try {
                $file = $filesystem->get($glob);
                $innerIterator = new \ArrayIterator([$glob => $file]);
            } catch (FileNotFoundException $e) {
                $innerIterator = new \EmptyIterator(); 
            }
        } else {
            $basePath = Glob::getBasePath($glob);

            $innerIterator = new GlobFilterIterator(
                $glob,
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $filesystem,
                        $basePath,
                        RecursiveDirectoryIterator::KEY_FOR_GLOB
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                ),
                GlobFilterIterator::FILTER_KEY,
                $flags
            );
        }

        parent::__construct($innerIterator);
    }
}
