<?php

namespace Bolt\Filesystem\Adapter;

use Bolt\Filesystem\Capability\Profile;

interface DefinesProfileInterface
{
    /**
     * @return Profile
     */
    public function getProfile();
}
