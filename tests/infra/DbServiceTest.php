<?php

namespace Register\Infra;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use Register\Value\User;
use Register\Value\UserGroup;

class DbServiceTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $root;

    /** @var DbService */
    private $subject;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup("root");
        $this->subject = new DbService(vfsStream::url("root/register/"), "guest");
    }

    public function testUsersAndGroupsFileAreCreatedAutomatically(): void
    {
        $this->assertTrue($this->subject->hasUsersFile());
        $this->assertTrue($this->subject->hasGroupsFile());
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
                'activated'
            )
        );
        $this->subject->writeUsers($expected);
        $actual = $this->subject->readUsers();
        $this->assertEquals($expected, $actual);
    }

    public function testGroupsBackup()
    {
        $this->subject->writeGroups([]);
        $this->subject->writeGroups([]);
        $this->assertFileEquals(
            vfsStream::url('root/register/groups.csv.bak'),
            vfsStream::url('root/register/groups.csv')
        );
    }

    public function testUsersBackup()
    {
        $this->subject->writeUsers([]);
        $this->subject->writeUsers([]);
        $this->assertFileEquals(
            vfsStream::url('root/register/users.csv.bak'),
            vfsStream::url('root/register/users.csv')
        );
    }
}
