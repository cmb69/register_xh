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
use XH\Pages;

class MainAdminControllerTest extends TestCase
{
    public function testEditUsersActionRendersUsers()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(true);
        $dbService->method('readUsers')->willReturn([$this->makeJohn(), $this->makeJane()]);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", ""), new UserGroup("guest", "")]);
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->editUsersAction();
        Approvals::verifyHtml($response);
    }

    public function testEditUsersActionIncludesScripts()
    {
        global $hjs;

        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(true);
        $dbService->method('readUsers')->willReturn([$this->makeJohn(), $this->makeJane()]);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeMainAdminController($dbService);
        $hjs = "";
        $sut->editUsersAction();
        Approvals::verifyHtml($hjs);
    }

    public function testEditUsersActionFailsIfNoUsersFile()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(false);
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->editUsersAction();
        Approvals::verifyHtml($response);
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
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveUsersAction();
        Approvals::verifyHtml($response);
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
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveUsersAction();
        Approvals::verifyHtml($response);
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
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveUsersAction();
        Approvals::verifyHtml($response);
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
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveUsersAction();
        Approvals::verifyHtml($response);
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
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveUsersAction();
        Approvals::verifyHtml($response);
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
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveUsersAction();
        Approvals::verifyHtml($response);
    }

    public function testEditGroupActionRendersGroups()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->editGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testEditGroupActionFailsIfNoGroupsFile()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(false);
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->editGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testSaveGroupsCanAddRecord()
    {
        $_POST = [
            "add" => [""],
            "groupname" => ["guest"],
            "grouploginpage" => [""],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeGroups');
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testSaveGroupsCanDeleteRecord()
    {
        $_POST = [
            "delete" => [0 => "1"],
            "groupname" => ["to_be_deleted", "guest"],
            "grouploginpage" => ["", "Start"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeGroups');
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testSaveGroupsFailsOnInvalidGroupName()
    {
        $_POST = [
            "groupname" => ["illegal name", "guest"],
            "grouploginpage" => ["", "Start"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeGroups');
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testSaveGroupsSuccessfullySaves()
    {
        $_POST = [
            "groupname" => ["admin", "guest"],
            "grouploginpage" => ["", "Start"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->once())->method('writeGroups')->willReturn(true);
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testSaveGroupsFailsToSaves()
    {
        $_POST = [
            "groupname" => ["admin", "guest"],
            "grouploginpage" => ["", "Start"],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->once())->method('writeGroups')->willReturn(false);
        $sut = $this->makeMainAdminController($dbService);
        $response = $sut->saveGroupsAction();
        Approvals::verifyHtml($response);
    }

    private function makeMainAdminController(DbService $dbService): MainAdminController
    {
        return new MainAdminController(
            "./",
            $this->makeConf(),
            $this->makeLang(),
            $this->makeCsrfProtector(false),
            $dbService,
            "/",
            $this->makePages()
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

    private function makePages(): Pages
    {
        $pages = $this->createStub(Pages::class);
        $pages->method('getCount')->willReturn(2);
        $pages->method('url')->willReturnMap([[0, "foo"], [1, "bar"]]);
        $pages->method('level')->willReturnMap([[0, 1], [1, 2]]);
        $pages->method('heading')->willReturnMap([[0, "Foo"], [1, "Bar"]]);
        return $pages;
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
