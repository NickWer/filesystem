<?php

namespace Bolt\Filesystem\Capability;

/**
 * An adapter should implement this if the filesystem supports
 * actually directories and is not just emulating them.
 *
 * By implementing this the adapter it is stating:
 * - has() works for both for files and directories
 * - getMetadata() works for both files and directories
 */
interface Directories
{
    /**
     * Check whether a directory exists.
     *
     * @param string $path
     *
     * @return array|bool
     */
    public function hasDir($path);
}
