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
use Register\Infra\FakePassword;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;
use Register\Value\UserGroup;

class UserAdminTest extends TestCase
{
    private $conf;
    private $csrfProtector;
    private $userRepository;
    private $userGroupRepository;
    private $dbService;
    private $password;
    private $random;
    private $mailer;
    private $view;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->csrfProtector = new FakeCsrfProtector;
        $this->random = $this->createMock(Random::class);
        $this->random->method("bytes")->willReturn(hex2bin("de69351538c8d0a32beec9e9a365a4"));
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->random);
        $this->dbService->writeUsers($this->users());
        $this->userRepository = new UserRepository($this->dbService);
        $this->userGroupRepository = new UserGroupRepository($this->dbService);
        $this->password = new FakePassword;
        $this->mailer = new FakeMailer(false, XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
        $this->request = $this->getMockBuilder(Request::class)
            ->onlyMethods(["action", "post", "postedPassword", "postedUser", "selectedUser", "url"])
            ->getMock();
    }

    private function sut(): UserAdmin
    {
        return new UserAdmin(
            $this->conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->userGroupRepository,
            $this->password,
            $this->random,
            $this->mailer,
            $this->view
        );
    }

    public function testRendersOverview(): void
    {
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $this->userGroupRepository->save(new UserGroup("admin", ""));
        $this->request->method("action")->willReturn("create");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_create");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoCreateReportsExistingUser(): void
    {
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The chosen username exists already.", $response->output());
    }

    public function testDoCreateReportsValidationErrors(): void
    {
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedUser")->willReturn(
            new User("cmb", "test", ["guest"], "Christoph M. Becker", "cmb@example.com", "activated", "")
        );
        $this->request->method("postedPassword")->willReturn("asd");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testDoCreateReportsExistingEmail(): void
    {
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedUser")->willReturn(
            new User("cmb", "test", ["guest"], "Christoph M. Becker", "john@example.com", "activated", "")
        );
        $this->request->method("postedPassword")->willReturn("test");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("A user with the given email address exists already.", $response->output());
    }

    public function testDoCreateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedUser")->willReturn(
            new User("cmb", "test", ["guest"], "Christoph M. Becker", "cmb@example.com", "activated", "")
        );
        $this->request->method("postedPassword")->willReturn("test");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testCreateRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_create");
        $this->request->method("postedUser")->willReturn(
            new User("cmb", "test", ["guest"], "Christoph M. Becker", "cmb@example.com", "activated", "")
        );
        $this->request->method("postedPassword")->willReturn("test");
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertNotNull($this->userRepository->findByUsername("cmb"));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testUpdateReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("update");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testRendersUpdateForm(): void
    {
        $this->request->method("action")->willReturn("update");
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoUpdateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_update");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoUpdateReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("do_update");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testDoUpdateReportsValidationErrors(): void
    {
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("postedUser")->willReturn(
            new User("jane", "test", ["admin"], "", "jane@example.com", "activated", "")
        );
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testDoUpdateReportsExistingEmail(): void
    {
        $this->userRepository->save(
            new User("cmb", "test", ["guest"], "Christoph Becker", "cmb@example.com", "activated", "secret")
        );
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("selectedUser")->willReturn("cmb");
        $this->request->method("post")->willReturn(
            ["name" => "Christoph M. Becker", "email" => "jane@example.com", "groups" => ["guest"], "status" => "activated"]
        );
        $this->request->method("postedPassword")->willReturn("test");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("A user with the given email address exists already.", $response->output());
    }

    public function testDoUpdateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("post")->willReturn(
            ["name" => "John Doe", "email" => "john@example.com", "groups" => ["guest"], "status" => "activated"]
        );
        $this->request->method("selectedUser")->willReturn("john");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoUpdateRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_update");
        $this->request->method("post")->willReturn(
            ["name" => "John Doe", "email" => "john@example.com", "groups" => ["guest"], "status" => "activated"]
        );
        $this->request->method("selectedUser")->willReturn("john");
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertEquals("activated", $this->userRepository->findByUsername("john")->getStatus());
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testChangePasswordReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("change_password");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testRendersChangePasswordForm(): void
    {
        $this->request->method("action")->willReturn("change_password");
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangePasswordIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_change_password");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoChangePasswordReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("do_change_password");
        $this->request->method("selectedUser")->willReturn("cmb");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoChangePasswordReportsValidationErrors(): void
    {
        $this->request->method("action")->willReturn("do_change_password");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("post")->willReturn(["password1" => "a", "password2" => "b"]);
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testDoChangePasswordReportsFailureToWrite(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("action")->willReturn("do_change_password");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("post")->willReturn(["password1" => "a", "password2" => "a"]);
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoChangePasswordRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_change_password");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("post")->willReturn(["password1" => "a", "password2" => "a"]);
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertTrue(password_verify("a", $this->userRepository->findByUsername("jane")->getPassword()));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testMailReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("mail");
        $this->request->method("selectedUser")->willReturn("cmb");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testRendersMailForm(): void
    {
        $this->request->method("action")->willReturn("mail");
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoMailIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_mail");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoMailReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("do_mail");
        $this->request->method("selectedUser")->willReturn("cmb");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoMailReportsValidationErrors(): void
    {
        $this->request->method("action")->willReturn("do_mail");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("post")->willReturn(["subject" => "", "message" => "message"]);
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Invalid subject!", $response->output());
    }

    public function testDoMailReportsFailureToSendMail(): void
    {
        $this->mailer->options(["sendMail" => false]);
        $this->request->method("action")->willReturn("do_mail");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("post")->willReturn(["subject" => "subject", "message" => "message"]);
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The email could not be sent!", $response->output());
    }

    public function testDoMailRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_mail");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("post")->willReturn(["subject" => "subject", "message" => "message"]);
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testDeleteReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("delete");
        $this->request->method("selectedUser")->willReturn("cmb");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDeleteRendersDeleteForm(): void
    {
        $this->request->method("action")->willReturn("delete");
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("action")->willReturn("do_delete");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoDeleteReportsMissingUser(): void
    {
        $this->request->method("action")->willReturn("do_delete");
        $this->request->method("selectedUser")->willReturn("cmb");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoDeleteReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("action")->willReturn("do_delete");
        $this->request->method("selectedUser")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoDeleteRedirectsOnSuccess(): void
    {
        $this->request->method("action")->willReturn("do_delete");
        $this->request->method("selectedUser")->willReturn("jane");
        $this->request->method("url")->willReturn(new Url("/", ""));
        $response = $this->sut()($this->request);
        $this->assertNull($this->userRepository->findByUsername("jane"));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    private function users(): array
    {
        return [
            new User("jane", "test", ["admin"], "Jane Doe", "jane@example.com", "activated", "nDZ8c8abkHTjpfI77TPi"),
            new User("john", "test", ["guest"], "John Doe", "john@example.com", "locked", "n+VaBbbvk934dmPF/fRw"),
        ];
    }
}
