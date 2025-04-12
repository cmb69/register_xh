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
use Plib\Random;
use Plib\View;
use Register\Infra\FakePassword;
use Register\Infra\Mailer;
use Register\Model\Groups;
use Register\Model\UserGroup;
use Register\Model\User;
use Register\Model\Users;

class UserAdminTest extends TestCase
{
    private $conf;
    private $csrfProtector;
    private $store;
    private $password;
    private $random;
    private $mailer;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("0+pVtDm4xXAxUmA3/mrL");
        $this->random = $this->createMock(Random::class);
        $this->random->method("bytes")->willReturn(hex2bin("de69351538c8d0a32beec9e9a365a4"));
        $this->store = $this->createStub(DocumentStore::class);
        $this->password = new FakePassword;
        $this->mailer = $this->createMock(Mailer::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut(): UserAdmin
    {
        return new UserAdmin(
            $this->conf,
            $this->csrfProtector,
            $this->store,
            $this->password,
            $this->random,
            $this->mailer,
            $this->view
        );
    }

    public function testRendersOverview(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane(), "john" => $this->john()])],
        ]);
        $request = new FakeRequest();
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $this->store->method("retrieve")->willReturn(new Groups([
            new UserGroup("guest", ""),
            new UserGroup("admin", "")
        ]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=create"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_create",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoCreateReportsExistingUser(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane(), "john" => $this->john()])],
        ]);
        $users = new Users(["jane" => $this->jane(), "john" => $this->john()]);
        $this->store->method("update")->willReturn($users);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_create&user=jane",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The chosen username exists already.", $response->output());
    }

    public function testDoCreateReportsValidationErrors(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane(), "john" => $this->john()])],
        ]);
        $users = new Users(["jane" => $this->jane(), "john" => $this->john()]);
        $this->store->method("update")->willReturn($users);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_create",
            "post" => [
                "username" => "cmb",
                "password1" => "test",
                "password2" => "asd",
                "groups" => ["guest"],
                "name" => "Christoph M. Becker",
                "email" => "cmb@example.com",
                "status" => "activated",
            ]
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testDoCreateReportsExistingEmail(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane(), "john" => $this->john()])],
        ]);
        $users = new Users(["jane" => $this->jane(), "john" => $this->john()]);
        $this->store->method("update")->willReturn($users);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_create",
            "post" => [
                "username" => "cmb",
                "password1" => "test",
                "password2" => "test",
                "groups" => ["guest"],
                "name" => "Christoph M. Becker",
                "email" => "john@example.com",
                "status" => "activated",
            ]
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("A user with the given email address exists already.", $response->output());
    }

    public function testDoCreateReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane(), "john" => $this->john()])],
        ]);
        $users = new Users(["jane" => $this->jane(), "john" => $this->john()]);
        $this->store->method("update")->willReturn($users);
        $this->store->method("commit")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_create",
            "post" => [
                "username" => "cmb",
                "password1" => "test",
                "password2" => "test",
                "groups" => ["guest"],
                "name" => "Christoph M. Becker",
                "email" => "cmb@example.com",
                "status" => "activated",
            ]
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testCreateRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane(), "john" => $this->john()])],
        ]);
        $users = new Users(["jane" => $this->jane(), "john" => $this->john()]);
        $this->store->method("update")->willReturn($users);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_create",
            "post" => [
                "username" => "cmb",
                "password1" => "test",
                "password2" => "test",
                "groups" => ["guest"],
                "name" => "Christoph M. Becker",
                "email" => "cmb@example.com",
                "status" => "activated",
            ]
        ]);
        $response = $this->sut()($request);
        $this->assertNotNull($users->user("cmb"));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testUpdateReportsMissingUser(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users([])],
        ]);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=update",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testRendersUpdateForm(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users(["jane" => $this->jane()])],
        ]);
        $request = new FakeRequest(["url" => "http://example.com/?&action=update&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoUpdateIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_update"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoUpdateReportsMissingUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Users([]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_update"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testDoUpdateReportsValidationErrors(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturn(new Groups([new UserGroup("guest", "")]));
        $this->store->method("update")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_update&user=jane",
            "post" => [
                "username" => "jane",
                "password1" => "test",
                "groups" => ["admin"],
                "name" => "",
                "email" => "jane@example.com",
                "status" => "activated",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testDoUpdateReportsExistingEmail(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $cmb = new User("cmb", "test", ["guest"], "Christoph Becker", "cmb@example.com", "activated", "secret");
        $this->store->method("retrieve")->willReturn(new Groups([new UserGroup("guest", "")]));
        $this->store->method("update")->willReturn(new Users(["cmb" => $cmb]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_update&user=cmb",
            "post" => [
                "groups" => ["guest"],
                "name" => "Christoph M. Becker",
                "email" => "jane@example.com",
                "status" => "activated",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("A user with the given email address exists already.", $response->output());
    }

    public function testDoUpdateReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturn(new Groups([new UserGroup("guest", "")]));
        $this->store->method("update")->willReturn(new Users(["john" => $this->john()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_update&user=john",
            "post" => [
                "groups" => ["guest"],
                "name" => "John Doe",
                "email" => "john@example.com",
                "status" => "activated",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoUpdateRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $users = new Users(["john" => $this->john()]);
        $this->store->method("update")->willReturn($users);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_update&user=john",
            "post" => [
                "groups" => ["guest"],
                "name" => "John Doe",
                "email" => "john@example.com",
                "status" => "activated",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("activated", $users->user("john")->getStatus());
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testChangePasswordReportsMissingUser(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users([])],
        ]);
        $request = new FakeRequest(["url" => "http://example.com/?&action=change_password"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testRendersChangePasswordForm(): void
    {
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=change_password&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangePasswordIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_change_password"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoChangePasswordReportsMissingUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Users([]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_change_password&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoChangePasswordReportsValidationErrors(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_change_password&user=jane",
            "post" => ["password1" => "a", "password2" => "b"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testDoChangePasswordReportsFailureToWrite(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_change_password&user=jane",
            "post" => ["password1" => "a", "password2" => "a"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoChangePasswordRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $users = new Users(["jane" => $this->jane()]);
        $this->store->method("update")->willReturn($users);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_change_password&user=jane",
            "post" => ["password1" => "a", "password2" => "a"],
        ]);
        $response = $this->sut()($request);
        $this->assertTrue(password_verify("a", $users->user("jane")->getPassword()));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testMailReportsMissingUser(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users([])],
        ]);
        $request = new FakeRequest(["url" => "http://example.com/?&action=mail&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testRendersMailForm(): void
    {
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=mail&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoMailIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_mail"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoMailReportsMissingUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturn(new Users([]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_mail&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoMailReportsValidationErrors(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_mail&user=jane",
            "post" => ["subject" => "", "message" => "message"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Invalid subject!", $response->output());
    }

    public function testDoMailReportsFailureToSendMail(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_mail&user=jane",
            "post" => ["subject" => "subject", "message" => "message"],
        ]);
        $this->mailer->expects($this->once())->method("sendMail")->willReturn(false);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The email could not be sent!", $response->output());
    }

    public function testDoMailRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=do_mail&user=jane",
            "post" => ["subject" => "subject", "message" => "message"],
        ]);
        $this->mailer->expects($this->once())->method("sendMail")->with(
            "jane@example.com",
            "subject",
            "message",
            "postmaster@example.com",
        )->willReturn(true);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testDeleteReportsMissingUser(): void
    {
        $this->store->method("retrieve")->willReturnMap([
            ["groups.csv", Groups::class, new Groups([new UserGroup("guest", "")])],
            ["users.csv", Users::class, new Users([])],
        ]);
        $request = new FakeRequest(["url" => "http://example.com/?&action=delete&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDeleteRendersDeleteForm(): void
    {
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=delete&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_delete"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoDeleteReportsMissingUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Users([]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_delete&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoDeleteReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store->method("update")->willReturn(new Users(["jane" => $this->jane()]));
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_delete&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoDeleteRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $users = new Users(["jane" => $this->jane()]);
        $this->store->method("update")->willReturn($users);
        $this->store->method("commit")->willReturn(true);
        $request = new FakeRequest(["url" => "http://example.com/?&action=do_delete&user=jane"]);
        $response = $this->sut()($request);
        $this->assertNull($users->user("jane"));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    private function users(): array
    {
        return [
            new User("jane", "test", ["admin"], "Jane Doe", "jane@example.com", "activated", "nDZ8c8abkHTjpfI77TPi"),
            new User("john", "test", ["guest"], "John Doe", "john@example.com", "locked", "n+VaBbbvk934dmPF/fRw"),
        ];
    }

    private function jane(): User
    {
        return new User("jane", "test", ["admin"], "Jane Doe", "jane@example.com", "activated", "nDZ8c8abkHTjpfI77TPi");
    }

    private function john(): User
    {
        return new User("john", "test", ["guest"], "John Doe", "john@example.com", "locked", "n+VaBbbvk934dmPF/fRw");
    }
}
