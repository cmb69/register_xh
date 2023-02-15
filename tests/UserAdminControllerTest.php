<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use XH\CSRFProtection as CsrfProtector;

use Register\Value\User;
use Register\Value\UserGroup;
use Register\Infra\DbService;
use Register\Infra\Request;
use Register\Infra\Url;

class UserAdminControllerTest extends TestCase
{
    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "Foo"));
    }

    public function testEditUsersActionRendersUsers()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(true);
        $dbService->method('readUsers')->willReturn([$this->makeJohn(), $this->makeJane()]);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", ""), new UserGroup("guest", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->editUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditUsersActionIncludesScripts()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(true);
        $dbService->method('readUsers')->willReturn([$this->makeJohn(), $this->makeJane()]);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->editUsersAction($this->request);
        $expected = [
            "register_texts" => [
                "name" => "Full Name",
                "username" => "Username",
                "password" => "Password",
                "email" => "Email",
                "accessgroups" => "Access Groups",
                "status" => "Status",
                "prefsemailsubject" => "Account data changed for",
                "confirmLeave" => "Unsaved changes will be lost!",
                "newPassword" => "New Password (may contain only alphanumeric characters and underscores):",
                "tooManyUsers" => "There are too many users!",
            ],
            "register_max_number_of_users" => 142,
        ];
        $this->assertEquals($expected, $response->meta());
        $this->assertEquals("./admin.min.js", $response->script());
    }

    public function testEditUsersActionFailsIfNoUsersFile()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(false);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->editUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersCanAddRecord()
    {
        $_POST = [
            "add" => [""],
            "username" => ["cmb"],
            "password" => ["test"],
            "oldpassword" => ["test"],
            "name" => ["Christoph Becker"],
            "email" => ["chris@example.com"],
            "accessgroups" => ["users"],
            "status" => ["activated"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->saveUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersCanDeleteRecord()
    {
        $_POST = [
            "delete" => [0 => "1"],
            "username" => ["to_be_deleted", "cmb"],
            "password" => ["to_be_deleted", "test"],
            "oldpassword" => ["to_be_deleted", "test"],
            "name" => ["Toby Deleted", "Christoph Becker"],
            "email" => ["to_be_deleted@example.com", "chris@example.com"],
            "accessgroups" => ["", "users"],
            "status" => ["activated", "activated"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->saveUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersFailsOnInvalidUserName()
    {
        $_POST = [
            "username" => [""],
            "password" => ["test"],
            "oldpassword" => ["test"],
            "name" => ["Christoph Becker"],
            "email" => ["cmb@example.com"],
            "accessgroups" => ["users"],
            "status" => ["activated"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->saveUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersFailsOnInvalidUserNameWhenChangingPassword()
    {
        $_POST = [
            "username" => [""],
            "password" => ["new"],
            "oldpassword" => ["old"],
            "name" => ["Christoph Becker"],
            "email" => ["cmb@example.com"],
            "accessgroups" => ["users"],
            "status" => ["activated"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->saveUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersFailsOnDuplicateUserNameAndEmail()
    {
        $_POST = [
            "username" => ["cmb", "cmb"],
            "password" => ["pw1", "pw2"],
            "oldpassword" => ["pw1", "pw2"],
            "name" => ["Christoph Becker", "Christoph Becker"],
            "email" => ["cmb@example.com", "cmb@example.com"],
            "accessgroups" => ["users", "users"],
            "status" => ["activated", "activated"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->saveUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersSuccessfullySaves()
    {
        $_POST = [
            "username" => ["cmb"],
            "password" => ["pw"],
            "oldpassword" => ["pw"],
            "name" => ["Christoph Becker"],
            "email" => ["cmb@example.com"],
            "accessgroups" => ["users"],
            "status" => ["activated"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->once())->method('writeUsers')->willReturn(true);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $response = $sut->saveUsersAction($this->request);
        Approvals::verifyHtml($response->output());
    }
    private function makeUserAdminController(DbService $dbService): UserAdminController
    {
        return new UserAdminController(
            "./",
            $this->makeConf(),
            $this->makeLang(),
            $this->makeCsrfProtector(false),
            $dbService,
            "/"
        );
    }

    /** @return array<string,string> */
    private function makeConf(): array
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        return $plugin_cf['register'];
    }

    /** @return array<string,string> */
    private function makeLang(): array
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        return $plugin_tx['register'];
    }

    private function makeCsrfProtector(bool $used): CsrfProtector
    {
        $csrfProtector = $this->createStub(CsrfProtector::class);
        if ($used) {
            $csrfProtector->expects($this->once())->method('check');
        }
        return $csrfProtector;
    }

    private function makeJohn(): User
    {
        return new User(
            "john",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["guest"],
            "John Doe",
            "john@example.com",
            "activated"
        );
    }

    private function makeJane(): User
    {
        return new User(
            "jane",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated"
        );
    }
}
