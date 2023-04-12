<?php

namespace Register\Infra;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Value\User;
use Register\Value\UserGroup;

class DbServiceTest extends TestCase
{
    /** @var DbService */
    private $subject;

    protected function setUp(): void
    {
        vfsStream::setup("root");
        $this->subject = new DbService("vfs://root/register/", "guest", $this->createStub(Random::class));
    }

    public function testCanAquireLock(): void
    {
        $lock = $this->subject->lock(false);
        $this->assertIsResource($lock);
        $this->subject->unlock($lock);
    }

    public function testReturnsNullIfLockCannotBeAquired(): void
    {
        $lockFilename = $this->subject->dataFolder() . ".lock";
        touch($lockFilename);
        chmod($lockFilename, 0000);
        $lock = $this->subject->lock(true);
        $this->assertNull($lock);
    }

    public function testWriteAndReadGroups()
    {
        $expected = array(
            new UserGroup('admin', '')
        );
        $this->subject->writeGroups($expected);
        $actual = $this->subject->readGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAndReadUsers()
    {
        $expected = array(
            new User(
                'cmb',
                'test',
                ['admin', 'guest'],
                'Christoph M. Becker',
                'cmbecker69@gmx.de',
                'activated',
                "secret"
            )
        );
        $this->subject->writeUsers($expected);
        $actual = $this->subject->readUsers();
        $this->assertEquals($expected, $actual);
    }
}
