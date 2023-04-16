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
use Register\Infra\FakeMailer;
use Register\Infra\FakeRequest;
use Register\Infra\Pages;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserGroupRepository;
use Register\Infra\View;
use Register\Value\UserGroup;

class GroupAdminTest extends TestCase
{
    private $csrfProtector;
    private $dbService;
    private $userGroupRepository;
    private $pages;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->csrfProtector = new FakeCsrfProtector;
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->dataFolder();
        $this->userGroupRepository = new UserGroupRepository($this->dbService);
        $this->pages = $this->createStub(Pages::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut(): GroupAdmin
    {
        return new GroupAdmin(
            $this->csrfProtector,
            $this->userGroupRepository,
            $this->pages,
            $this->view
        );
    }

    public function testRendersOverview(): void
    {
        $this->userGroupRepository->save(new UserGroup("new", "Start"));
        $request = new FakeRequest();
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $this->pages->method("count")->willReturn(1);
        $this->pages->method("url")->willReturn("Start");
        $this->pages->method("heading")->willReturn("Start");
        $this->pages->method("level")->willReturn(1);
        $request = new FakeRequest(["query" => "&action=create"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["post" => ["action" => "do_create"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoCreateReportsExistingGroup(): void
    {
        $request = new FakeRequest(["query" => "&group=guest", "post" => ["action" => "do_create"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("This groupname already exists!", $response->output());
    }

    public function testDoCreateReportsInvalidGroup(): void
    {
        $request = new FakeRequest(["post" => ["action" => "do_create", "groupname" => "", "loginpage" => ""]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString(
            "The group name must contain only following characters: A-Z, a-z, 0-9, '_', '-'.",
            $response->output()
        );
    }

    public function testDoCreateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $request = new FakeRequest(["post" => ["action" => "do_create", "groupname" => "new", "loginpage" => ""]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoCreateRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "post" => ["action" => "do_create", "groupname" => "new", "loginpage" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(new UserGroup("new", ""), $this->userGroupRepository->findByGroupName("new"));
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }

    public function testUpdateReportsMissingGroup(): void
    {
        $request = new FakeRequest(["query" => "register&admin=groups&action=update&group=missing"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testRendersUpdateForm(): void
    {
        $request = new FakeRequest(["query" => "&action=update&group=guest"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoUpdateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["post" => ["action" => "do_update"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoUpdateReportsMissingGroup(): void
    {
        $request = new FakeRequest(["query" => "&group=missing", "post" => ["action" => "do_update"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testDoUpdateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $request = new FakeRequest([
            "query" => "&group=guest",
            "post" => ["action" => "do_update", "groupname" => "guest", "loginpage" => "Login"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoUpdateRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&group=guest",
            "post" => ["action" => "do_update", "groupname" => "guest", "loginpage" => "Login"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(new UserGroup("guest", "Login"), $this->userGroupRepository->findByGroupName("guest"));
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }

    public function testDeleteReportsMissingGroup(): void
    {
        $request = new FakeRequest(["query" => "&action=delete&group=missing"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testRendersDeleteForm(): void
    {
        $request = new FakeRequest(["query" => "&action=delete&group=guest"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["post" => ["action" => "do_delete"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }
    public function testDoDeleteReportsMissingGroup(): void
    {
        $request = new FakeRequest(["query" => "&group=missing", "post" => ["action" => "do_delete"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testDoDeleteReportsFailureToSave(): void
    {
        $this->dbService->options(["writeGroups" => false]);
        $request = new FakeRequest(["query" => "&group=guest", "post" => ["action" => "do_delete"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoDeleteRedirectsOnSuccess(): void
    {
        $request = new FakeRequest(["query" => "&group=guest", "post" => ["action" => "do_delete"]]);
        $response = $this->sut()($request);
        $this->assertNull($this->userGroupRepository->findByGroupName("guest"));
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }
}
