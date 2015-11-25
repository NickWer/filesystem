<?php

namespace Bolt\Filesystem\Tests\Image;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Image\Exif;
use Bolt\Filesystem\Image\Info;
use Bolt\Filesystem\Image\Type;
use PHPExif;

/**
 * Tests for Bolt\Filesystem\Image\Info
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class InfoTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filesystem = new Filesystem(new Local(__DIR__ . '/../'));
    }

    public function testConstruct()
    {
        $exif = new Exif([]);
        $type = new Type(IMAGETYPE_JPEG);
        $info = new Info(1024, 768, $type, 2, 7, 'Marcel Marceau', $exif);
        $this->assertInstanceOf('Bolt\Filesystem\Image\Info', $info);
    }

    public function testCreateFromFile()
    {
        $file = dirname(__DIR__) . '/fixtures/images/1-top-left.jpg';
        $info = Info::createFromFile($file);

        $this->assertInstanceOf('Bolt\Filesystem\Image\Info', $info);
        $this->assertInstanceOf('Bolt\Filesystem\Image\Type', $info->getType());
        $this->assertInstanceOf('Bolt\Filesystem\Image\Exif', $info->getExif());

        $this->assertSame(400, $info->getWidth());
        $this->assertSame(200, $info->getHeight());
        $this->assertSame(8, $info->getBits());
        $this->assertSame(3, $info->getChannels());
        $this->assertSame('image/jpeg', $info->getMime());
        $this->assertSame(2, $info->getAspectRatio());

        $this->assertTrue($info->isLandscape());
        $this->assertFalse($info->isPortrait());
        $this->assertFalse($info->isSquare());
    }

    public function testCreateFromFileFail()
    {
        $this->setExpectedException('Bolt\Filesystem\Exception\IOException', 'Failed to get image data from file');
        Info::createFromFile('drop-bear.jpg');
    }

    public function testCreateFromString()
    {
        $file = $this->filesystem->get('fixtures/images/1-top-left.jpg')->read();
        $info = Info::createFromString($file);

        $this->assertInstanceOf('Bolt\Filesystem\Image\Info', $info);
        $this->assertInstanceOf('Bolt\Filesystem\Image\Type', $info->getType());
        $this->assertInstanceOf('Bolt\Filesystem\Image\Exif', $info->getExif());

        $this->assertSame(400, $info->getWidth());
        $this->assertSame(200, $info->getHeight());
        $this->assertSame(8, $info->getBits());
        $this->assertSame(3, $info->getChannels());
        $this->assertSame('image/jpeg', $info->getMime());
        $this->assertSame(2, $info->getAspectRatio());

        $this->assertTrue($info->isLandscape());
        $this->assertFalse($info->isPortrait());
        $this->assertFalse($info->isSquare());
    }

    public function testCreateFromStringFail()
    {
        $this->setExpectedException('Bolt\Filesystem\Exception\IOException', 'Failed to get image data from string');
        Info::createFromString('drop-bear.jpg');
    }

    public function testClone()
    {
        $file = $this->filesystem->get('fixtures/images/1-top-left.jpg')->read();
        $info = Info::createFromString($file);
        $clone = clone $info;

        $this->assertNotSame($clone->getExif(), $info->getExif());
    }
}
