<?php

namespace Bolt\Filesystem\Adapter;

use Bolt\Filesystem\Capability;
use League\Flysystem\Memory\MemoryAdapter;

class Memory extends MemoryAdapter implements AdapterInterface, Capability\Directories
{
    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return (new Capability\Profile($this))
            ->enableDirs()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDir($path)
    {
        return $this->hasDirectory($path);
    }
}
