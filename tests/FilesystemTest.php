<?php

namespace Bolt\Filesystem\Tests;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Filesystem;
use League\Flysystem;
use League\Flysystem\FilesystemInterface;

/**
 * Tests for Bolt\Filesystem\FilesystemTest
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FilesystemTest extends FilesystemTestCase
{
    /** @var FilesystemInterface */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->filesystem = new Filesystem(new Local(__DIR__));
    }

    public function testCast()
    {
        $filesystem = Filesystem::cast(new \League\Flysystem\Filesystem(new Local(__DIR__)));
        $this->assertInstanceOf('Bolt\Filesystem\Filesystem', $filesystem);

        $this->setExpectedException('LogicException', 'Cannot cast Flysystem\FilesystemInterface, only Flysystem\Filesystem');
        $filesystem = Filesystem::cast(new Mock\Fakesystem());
    }

    public function testHas()
    {
        $has = $this->filesystem->has('fixtures/base.css');
        $this->assertTrue($has);

        $has = $this->filesystem->has('fixtures/base.css.typo');
        $this->assertFalse($has);

        $this->setExpectedExceptionRegExp('LogicException', '/^Path is outside of the defined root/');
        $has = $this->filesystem->has('../no-you-do-not');
    }

    public function testWrite()
    {
        $path = 'temp/kwik-e-mart';
        $expected = 'Thank you, come again';

        $this->filesystem->write($path, $expected);
        $this->assertSame($this->filesystem->read($path), $expected);

        try {
            $filesystem = new Filesystem(new Local('/'));
            $filesystem->write('nope', $expected);
            $this->fail('IOException not thrown!');
        } catch (IOException $e) {
//             $result = $filesystem->getAdapter()->getLastError();
            $this->assertRegExp('/Failed to write to file/', $e->getMessage());
//             $this->assertRegExp('/failed to open stream: Permission denied/', $result['message']);
//             $this->assertSame(E_WARNING, $result['type']);
        }
    }

    public function testWriteStream()
    {
        $path = 'temp/kwik-e-mart';
        $expected = $this->filesystem->readStream('fixtures/base.css');

        $this->filesystem->writeStream($path, $expected);
        $this->assertSame((string) $this->filesystem->readStream($path), (string) $expected);

        try {
            $filesystem = new Filesystem(new Local('/'));
            $filesystem->writeStream('nope', $expected);
            $this->fail('IOException not thrown!');
        } catch (IOException $e) {
//             $result = $filesystem->getAdapter()->getLastError();
            $this->assertRegExp('/Failed to write to file/', $e->getMessage());
//             $this->assertRegExp('/failed to open stream: Permission denied/', $result['message']);
//             $this->assertSame(E_WARNING, $result['type']);
        }
    }

    public function testPut()
    {
        $path = 'temp/kwik-e-mart';
        $expected = 'Thank you, come again';

        $this->filesystem->put($path, $expected);
        $this->assertSame($this->filesystem->read($path), $expected);

        try {
            $filesystem = new Filesystem(new Local('/'));
            $filesystem->put('nope', $expected);
            $this->fail('IOException not thrown!');
        } catch (IOException $e) {
//             $result = $filesystem->getAdapter()->getLastError();
            $this->assertRegExp('/Failed to write to file/', $e->getMessage());
//             $this->assertRegExp('/failed to open stream: Permission denied/', $result['message']);
//             $this->assertSame(E_WARNING, $result['type']);
        }
    }

    public function testPutStream()
    {
        $path = 'temp/kwik-e-mart';
        $expected = $this->filesystem->readStream('fixtures/base.css');

        $this->filesystem->putStream($path, $expected);
        $this->assertSame((string) $this->filesystem->readStream($path), (string) $expected);

        try {
            $filesystem = new Filesystem(new Local('/'));
            $filesystem->putStream('nope', $expected);
            $this->fail('IOException not thrown!');
        } catch (IOException $e) {
//             $result = $filesystem->getAdapter()->getLastError();
            $this->assertRegExp('/Failed to write to file/', $e->getMessage());
//             $this->assertRegExp('/failed to open stream: Permission denied/', $result['message']);
//             $this->assertSame(E_WARNING, $result['type']);
        }
    }

    public function testReadAndDelete()
    {
        $this->filesystem->copy('fixtures/base.css', 'temp/koala.css');
        $expected = $this->filesystem->readAndDelete('temp/koala.css');
        $this->assertSame($this->filesystem->read('fixtures/base.css'), $expected);

        $this->setExpectedExceptionRegExp('Bolt\Filesystem\Exception\FileNotFoundException');
        (new Filesystem(new Local('/')))->readAndDelete('nope', $expected);
    }

    public function testUpdate()
    {
        $this->filesystem->copy('fixtures/base.css', 'temp/koala.css');
        $this->filesystem->update('temp/koala.css', 'drop bear sighting');
        $this->assertSame($this->filesystem->read('temp/koala.css'), 'drop bear sighting');

//         $this->setExpectedExceptionRegExp('Bolt\Filesystem\Exception\FileNotFoundException');
//         (new Filesystem(new Local('/')))->update('nope', 'drop bear sighting');
        try {
            $filesystem = new Filesystem(new Local('/'));
            $filesystem->update('nope', 'drop bear sighting');
            $this->fail('FileNotFoundException not thrown!');
        } catch (FileNotFoundException $e) {
//             $result = $filesystem->getAdapter()->getLastError();
//             $this->assertRegExp('/Failed to write to file/', $e->getMessage());
//             $this->assertRegExp('/failed to open stream: Permission denied/', $result['message']);
//             $this->assertSame(E_WARNING, $result['type']);
        }
    }

    public function testUpdateStream()
    {
        $this->filesystem->copy('fixtures/base.css', 'temp/koala.css');
        $expected = $this->filesystem->readStream('fixtures/base.css');
        $this->filesystem->updateStream('temp/koala.css', $expected);
        $this->assertSame((string) $this->filesystem->readStream('fixtures/base.css'), (string) $expected);

//         $this->setExpectedExceptionRegExp('Bolt\Filesystem\Exception\FileNotFoundException');
//         (new Filesystem(new Local('/')))->updateStream('nope', $expected);
        try {
            $filesystem = new Filesystem(new Local('/'));
            $filesystem->updateStream('nope', $expected);
            $this->fail('FileNotFoundException not thrown!');
        } catch (FileNotFoundException $e) {
//             $result = $filesystem->getAdapter()->getLastError();
//             $this->assertRegExp('/Failed to write to file/', $e->getMessage());
//             $this->assertRegExp('/failed to open stream: Permission denied/', $result['message']);
//             $this->assertSame(E_WARNING, $result['type']);
        }
    }
}
