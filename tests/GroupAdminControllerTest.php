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

use Register\Value\UserGroup;
use Register\Infra\DbService;

class GroupAdminControllerTest extends TestCase
{
    public function testEditGroupActionRendersGroups()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeGroupAdminController($dbService);
        $response = $sut->editGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testEditGroupActionFailsIfNoGroupsFile()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(false);
        $sut = $this->makeGroupAdminController($dbService);
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
        $sut = $this->makeGroupAdminController($dbService);
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
        $sut = $this->makeGroupAdminController($dbService);
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
        $sut = $this->makeGroupAdminController($dbService);
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
        $sut = $this->makeGroupAdminController($dbService);
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
        $sut = $this->makeGroupAdminController($dbService);
        $response = $sut->saveGroupsAction();
        Approvals::verifyHtml($response);
    }

    private function makeGroupAdminController(DbService $dbService): GroupAdminController
    {
        return new GroupAdminController(
            "./",
            $this->makeLang(),
            $this->makeCsrfProtector(false),
            $dbService,
            "/",
            $this->makePages()
        );
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
}
