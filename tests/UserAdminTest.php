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
use Register\Infra\FakeRequest;
use Register\Infra\Random;
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
        $request = new FakeRequest();
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $this->userGroupRepository->save(new UserGroup("admin", ""));
        $request = new FakeRequest(["query" => "&action=create"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoCreateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "&action=do_create"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoCreateReportsExistingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=do_create&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The chosen username exists already.", $response->output());
    }

    public function testDoCreateReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&action=do_create",
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
        $request = new FakeRequest([
            "query" => "&action=do_create",
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
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&action=do_create",
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
        $request = new FakeRequest([
            "query" => "&action=do_create",
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
        $this->assertNotNull($this->userRepository->findByUsername("cmb"));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testUpdateReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=update"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testRendersUpdateForm(): void
    {
        $request = new FakeRequest(["query" => "&action=update&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoUpdateIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "&action=do_update"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoUpdateReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=do_update"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testDoUpdateReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&action=do_update&user=jane",
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
        $this->userRepository->save(
            new User("cmb", "test", ["guest"], "Christoph Becker", "cmb@example.com", "activated", "secret")
        );
        $request = new FakeRequest([
            "query" => "&action=do_update&user=cmb",
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
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&action=do_update&user=john",
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
        $request = new FakeRequest([
            "query" => "&action=do_update&user=john",
            "post" => [
                "groups" => ["guest"],
                "name" => "John Doe",
                "email" => "john@example.com",
                "status" => "activated",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("activated", $this->userRepository->findByUsername("john")->getStatus());
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testChangePasswordReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=change_password"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testRendersChangePasswordForm(): void
    {
        $request = new FakeRequest(["query" => "&action=change_password&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoChangePasswordIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "&action=do_change_password"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoChangePasswordReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=do_change_password&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoChangePasswordReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&action=do_change_password&user=jane",
            "post" => ["password1" => "a", "password2" => "b"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testDoChangePasswordReportsFailureToWrite(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&action=do_change_password&user=jane",
            "post" => ["password1" => "a", "password2" => "a"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoChangePasswordRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&action=do_change_password&user=jane",
            "post" => ["password1" => "a", "password2" => "a"],
        ]);
        $response = $this->sut()($request);
        $this->assertTrue(password_verify("a", $this->userRepository->findByUsername("jane")->getPassword()));
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
    }

    public function testMailReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=mail&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testRendersMailForm(): void
    {
        $request = new FakeRequest(["query" => "&action=mail&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoMailIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "&action=do_mail"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoMailReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=do_mail&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoMailReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&action=do_mail&user=jane",
            "post" => ["subject" => "", "message" => "message"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Invalid subject!", $response->output());
    }

    public function testDoMailReportsFailureToSendMail(): void
    {
        $this->mailer->options(["sendMail" => false]);
        $request = new FakeRequest([
            "query" => "&action=do_mail&user=jane",
            "post" => ["subject" => "subject", "message" => "message"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("The email could not be sent!", $response->output());
    }

    public function testDoMailRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&action=do_mail&user=jane",
            "post" => ["subject" => "subject", "message" => "message"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?register&admin=users", $response->location());
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testDeleteReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=delete&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDeleteRendersDeleteForm(): void
    {
        $request = new FakeRequest(["query" => "&action=delete&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testDoDeleteIsCsrfProtected(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "&action=do_delete"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDoDeleteReportsMissingUser(): void
    {
        $request = new FakeRequest(["query" => "&action=do_delete&user=cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("User 'cmb' does not exist!", $response->output());
    }

    public function testDoDeleteReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest(["query" => "&action=do_delete&user=jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("Register – Users", $response->title());
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testDoDeleteRedirectsOnSuccess(): void
    {
        $request = new FakeRequest(["query" => "&action=do_delete&user=jane"]);
        $response = $this->sut()($request);
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
