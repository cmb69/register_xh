<?php

namespace Register;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class DbServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var DbService
     */
    private $subject;

    protected function setUp()
    {
        $this->root = vfsStream::setup('root');
        $this->subject = new DbService(vfsStream::url('root/'));
    }

    public function testWriteAndReadGroups()
    {
        $expected = array(
            (object) ['groupname' => 'admin', 'loginpage' => '']
        );
        $this->subject->writeGroups($expected);
        $actual = $this->subject->readGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAndReadUsers()
    {
        $expected = array(
            (object) array(
                'username' => 'cmb',
                'password' => 'test',
                'accessgroups' => ['admin', 'guest'],
                'name' => 'Christoph M. Becker',
                'email' => 'cmbecker69@gmx.de',
                'status' => 'activated'
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
        $this->assertFileEquals(vfsStream::url('root/groups.csv.bak'), vfsStream::url('root/groups.csv'));
    }

    public function testUsersBackup()
    {
        $this->subject->writeUsers([]);
        $this->subject->writeUsers([]);
        $this->assertFileEquals(vfsStream::url('root/users.csv.bak'), vfsStream::url('root/users.csv'));
    }
}
