<?php

namespace Bolt\Filesystem\Capability;

use Bolt\Filesystem\Exception\LogicException;
use League\Flysystem\AdapterInterface;

class Profile
{
    /** @var AdapterInterface */
    protected $adapter;

    /** @var bool */
    protected $dirs;
    /** @var bool */
    protected $psrStreams;
    /** @var bool */
    protected $includeFile;

    /**
     * Constructor.
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function applyTo(AdapterInterface $adapter)
    {
        $profile = clone $this;
        $profile->adapter = $adapter;
        $profile->verify();

        return $profile;
    }

    protected function verify()
    {
        if ($this->dirs && !$this->adapter instanceof Directories) {
            throw new LogicException('To enable directories the adapter needs to implement ' . Directories::class);
        }

        if ($this->includeFile && !$this->adapter instanceof IncludeFile) {
            throw new LogicException('To enable including files the adapter needs to implement ' . IncludeFile::class);
        }
    }

    /**
     * @return bool
     */
    public function supportsDirs()
    {
        return $this->dirs;
    }

    /**
     * @param bool $enable
     *
     * @return Profile
     */
    public function enableDirs($enable = true)
    {
        $this->dirs = $enable;
        $this->verify();

        return $this;
    }

    /**
     * @return bool
     */
    public function supportsIncludeFile()
    {
        return $this->includeFile;
    }

    /**
     * @param bool $enable
     *
     * @return Profile
     */
    public function enableIncludeFile($enable = true)
    {
        $this->includeFile = $enable;
        $this->verify();

        return $this;
    }

    /**
     * @return bool
     */
    public function supportsPsrStreams()
    {
        return $this->psrStreams;
    }

    /**
     * @param bool $enable
     *
     * @return Profile
     */
    public function enablePsrStreams($enable = true)
    {
        $this->psrStreams = $enable;
        $this->verify();

        return $this;
    }
}
