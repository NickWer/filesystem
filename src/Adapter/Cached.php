<?php

namespace Bolt\Filesystem\Adapter;

use Bolt\Filesystem\Capability;
use Bolt\Filesystem\Capability\Profile;
use League\Flysystem\Cached\CachedAdapter;

class Cached extends CachedAdapter implements DefinesProfileInterface, Capability\Directories
{
    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        $innerAdapter = $this->getAdapter();
        if ($innerAdapter instanceof DefinesProfileInterface) {
            return $innerAdapter->getProfile()->applyTo($this);
        }

        return new Profile($this);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDir($path)
    {
        $this->getAdapter()->hasDir($path);
    }
}
