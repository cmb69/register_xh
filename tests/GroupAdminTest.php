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
use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use Register\Infra\Pages;
use Register\Model\Groups;
use Register\Model\UserGroup;

class GroupAdminTest extends TestCase
{
    private $csrfProtector;
    private $store;
    private $pages;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("0+pVtDm4xXAxUmA3/mrL");
        $this->store = $this->createMock(DocumentStore::class);
        $this->pages = $this->createStub(Pages::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut(): GroupAdmin
    {
        return new GroupAdmin(
            $this->csrfProtector,
            $this->store,
            $this->pages,
            $this->view
        );
    }

    public function testRendersOverview(): void
    {
        $this->store->method("retrieve")->willReturn(new Groups([
            new UserGroup("guest", ""),
            new UserGroup("new", "Start"),
        ]));
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
        $request = new FakeRequest(["url" => "http://example.com/?&action=create"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["post" => ["action" => "do_create"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoCreateReportsExistingGroup(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups(["guest" => new UserGroup("guest", "")]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=guest",
            "post" => ["action" => "do_create"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("This groupname already exists!", $response->output());
    }

    public function testDoCreateReportsInvalidGroup(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups([]));
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
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups([]));
        $this->store->method("commit")->willReturn(false);
        $request = new FakeRequest(["post" => ["action" => "do_create", "groupname" => "new", "loginpage" => ""]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoCreateRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $groups = new Groups([]);
        $this->store->method("update")->willReturn($groups);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "post" => ["action" => "do_create", "groupname" => "new", "loginpage" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(new UserGroup("new", ""), $groups->group("new"));
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }

    public function testUpdateReportsMissingGroup(): void
    {
        $this->store->method("retrieve")->willReturn(new Groups([
            new UserGroup("guest", ""),
        ]));
        $request = new FakeRequest([
            "url" => "http://example.com/?register&admin=groups&action=update&group=missing",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testRendersUpdateForm(): void
    {
        $this->store->method("retrieve")->willReturn(new Groups(["guest" => new UserGroup("guest", "")]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=update&group=guest",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoUpdateIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["post" => ["action" => "do_update"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoUpdateReportsMissingGroup(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups([]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=missing",
            "post" => ["action" => "do_update"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testDoUpdateReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups(["guest" => new UserGroup("guest", "")]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=guest",
            "post" => ["action" => "do_update", "loginpage" => "Login"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoUpdateRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $groups = new Groups(["guest" => new UserGroup("guest", "")]);
        $this->store->method("update")->willReturn($groups);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=guest",
            "post" => ["action" => "do_update", "loginpage" => "Login"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(new UserGroup("guest", "Login"), $groups->group("guest"));
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }

    public function testDeleteReportsMissingGroup(): void
    {
        $this->store->method("retrieve")->willReturn(new Groups([]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=delete&group=missing",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testRendersDeleteForm(): void
    {
        $this->store->method("retrieve")->willReturn(new Groups(["guest" => new UserGroup("guest", "")]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=delete&group=guest"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["post" => ["action" => "do_delete"]]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }
    public function testDoDeleteReportsMissingGroup(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups([]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=missing",
            "post" => ["action" => "do_delete"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Group 'missing' does not exist!", $response->output());
    }

    public function testDoDeleteReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Groups(["guest" => new UserGroup("guest", "")]));
        $this->store->method("commit")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=guest",
            "post" => ["action" => "do_delete"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Groups", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoDeleteRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $groups = new Groups(["guest" => new UserGroup("guest", "")]);
        $this->store->method("update")->willReturn($groups);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&group=guest",
            "post" => ["action" => "do_delete"],
        ]);
        $response = $this->sut()($request);
        $this->assertNull($groups->group("guest"));
        $this->assertEquals("http://example.com/?register&admin=groups", $response->location());
    }
}
