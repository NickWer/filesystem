<?php

namespace Bolt\Filesystem\Exception;

class UnableToDetermineTypeException extends IOException
{
    /**
     * Constructor.
     *
     * @param string          $path
     * @param string          $adapter
     * @param \Exception|null $previous
     */
    public function __construct($path, $adapter, \Exception $previous = null)
    {
        $message = <<<EOT
The adapter "{$adapter}"does not support actual directories. In most cases
directories are emulated. For example, when calling listContents() we have 
a list of files so we can automatically structure them into directories like
you would expect. However, for hasDir(), we just assume the directory exists,
because a file could be created with that path prefix, or "directory", with
no problem.

This only bites us when we are trying to figure out types. If the path is not 
a file that exists, then we don't know if it is a directory or there is just 
nothing there.

For these filesystems, you can get a "directory" by calling getDir(), which
really just represents a path prefix.
EOT;
        parent::__construct($message, $path, 0, $previous);
    }
}
