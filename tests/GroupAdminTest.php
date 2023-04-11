<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeCsrfProtector;
use Register\Infra\FakeDbService;
use Register\Infra\Pages;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;
use Register\Value\UserGroup;

class GroupAdminTest extends TestCase
{
    private $csrfProtector;
    private $dbService;
    private $pages;
    private $view;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->csrfProtector = new FakeCsrfProtector;
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->dataFolder();
        $this->pages = $this->createStub(Pages::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
        $this->request = $this->getMockBuilder(Request::class)
            ->onlyMethods(["action", "postedGroup", "selectedGroup", "url"])
            ->getMock();
    }

    private function sut(): GroupAdmin
    {
        return new GroupAdmin(
            $this->csrfProtector,
            $this->dbService,
            $this->pages,
            $this->view
        );
    }

    public function testRendersOverview(): void
    {
        $this->dbService->writeGroups([new UserGroup("guest", ""), new UserGroup("new", "Start")]);
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $this->pages->method("count")->willReturn(1);
        $this->pages->method("url")->willReturn("Start");
        $this->pages->method("heading")->willReturn("Start");
        $this->pages->method("level")->willReturn(1);
        $this->request->method("action")->willReturn("create");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_create");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoCreateReportsInvalidGroup(): void
    {
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedGroup")->willReturn(new UserGroup("", ""));
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString(
            "The group name must contain only following characters: A-Z, a-z, 0-9, '_', '-'.",
            $response->output()
        );
    }

    public function testDoCreateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedGroup")->willReturn(new UserGroup("new", ""));
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoCreateRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedGroup")->willReturn(new UserGroup("new", ""));
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertCount(2, $this->dbService->readGroups());
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }

    public function testUpdateReportsMissingGroup(): void
    {
        $this->request->method("action")->willReturn("update");
        $this->request->method("selectedGroup")->willReturn("missing");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testRendersUpdateForm(): void
    {
        $this->request->method("action")->willReturn("update");
        $this->request->method("selectedGroup")->willReturn("guest");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoUpdateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_update");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoUpdateReportsMissingGroup(): void
    {
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("selectedGroup")->willReturn("missing");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testDoUpdateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("postedGroup")->willReturn(new UserGroup("guest", "Login"));
        $this->request->method("selectedGroup")->willReturn("guest");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoUpdateRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("postedGroup")->willReturn(new UserGroup("guest", "Login"));
        $this->request->method("selectedGroup")->willReturn("guest");
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertEquals([new UserGroup("guest", "Login")], $this->dbService->readGroups());
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }

    public function testDeleteReportsMissingGroup(): void
    {
        $this->request->method("action")->willReturn("delete");
        $this->request->method("selectedGroup")->willReturn("missing");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testRendersDeleteForm(): void
    {
        $this->request->method("action")->willReturn("delete");
        $this->request->method("selectedGroup")->willReturn("guest");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_delete");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }
    public function testDoDeleteReportsMissingGroup(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $this->request->method("action")->willReturn("do_delete");
        $this->request->method("selectedGroup")->willReturn("missing");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testDoDeleteReportsFailureToSave(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $this->request->method("action")->willReturn("do_delete");
        $this->request->method("selectedGroup")->willReturn("guest");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoDeleteRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_delete");
        $this->request->method("selectedGroup")->willReturn("guest");
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertEquals([], $this->dbService->readGroups());
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }
}
