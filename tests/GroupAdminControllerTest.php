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
use Register\Value\UserGroup;
use Register\Infra\DbService;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class GroupAdminControllerTest extends TestCase
{
    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "Page"));
    }
    public function testEditGroupActionRendersGroups()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeGroupAdminController($dbService);
        $this->request->expects($this->any())->method("method")->willReturn("get");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditGroupActionFailsIfNoGroupsFile()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(false);
        $sut = $this->makeGroupAdminController($dbService);
        $this->request->expects($this->any())->method("method")->willReturn("get");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveGroupsCanAddRecord()
    {
        $_POST = [
            "add" => "on",
            "groupname" => ["guest"],
            "grouploginpage" => [""],
        ];
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeGroups');
        $sut = $this->makeGroupAdminController($dbService);
        $this->request->expects($this->any())->method("method")->willReturn("post");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
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
        $this->request->expects($this->any())->method("method")->willReturn("post");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
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
        $this->request->expects($this->any())->method("method")->willReturn("post");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
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
        $this->request->expects($this->any())->method("method")->willReturn("post");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
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
        $this->request->expects($this->any())->method("method")->willReturn("post");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    private function makeGroupAdminController(DbService $dbService): GroupAdminController
    {
        return new GroupAdminController(
            $this->makeCsrfProtector(false),
            new View("./", $this->makeLang()),
            $dbService,
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
        $pages->method('count')->willReturn(2);
        $pages->method('url')->willReturnMap([[0, "foo"], [1, "bar"]]);
        $pages->method('level')->willReturnMap([[0, 1], [1, 2]]);
        $pages->method('heading')->willReturnMap([[0, "Foo"], [1, "Bar"]]);
        return $pages;
    }
}
