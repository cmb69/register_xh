<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
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
use Register\Infra\Logger;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class HandleUserPreferencesTest extends TestCase
{
    private $subject;

    private $users;
    private $csrfProtector;
    private $dbService;
    private $userRepository;
    private $view;
    private $mailer;
    private $logger;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $hash = "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW";
        $this->users = [
            "john" => new User("john", $hash, ["guest"], "John Doe", "john@example.com", "activated", "secret"),
            "jane" => new User("jane", $hash, ["guest"], "Jane Doe", "jane@example.com", "locked", "secret"),
        ];
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = new FakeCsrfProtector;
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->writeUsers($this->users);
        $this->userRepository = new UserRepository($this->dbService);
        $this->view = new View("./views/", $text);
        $this->mailer = new FakeMailer(false, $text);
        $this->logger = $this->createMock(Logger::class);
        $password = new FakePassword;
        $this->subject = new HandleUserPreferences(
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $this->mailer,
            $this->logger,
            $password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "User-Preferences"));
    }

    public function testReportsNonExistentUser(): void
    {
        $this->request->method("registerAction")->willReturn("");
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testReportsIfUserIsLocked(): void
    {
        $this->request->method("registerAction")->willReturn("");
        $this->request->method("username")->willReturn("jane");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersForm(): void
    {
        $this->request->method("registerAction")->willReturn("");
        $this->request->method("username")->willReturn("john");
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangePrefsReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("john");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testChangePrefsReportsNonExistentUser(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testChangePrefsReportsLockedUser(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("jane");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testChangePrefsReportsWrongPassword(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "54321",
            "name" => "",
            "password1" => "",
            "password2" => "",
            "email" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testChangePrefsReportsValidationErrors(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "password1" => "one",
            "password2" => "two",
            "email" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testChangePrefsReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "John Doe",
            "password1" => "12345",
            "password2" => "12345",
            "email" => "new@example.com",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangePrefsRedirectsOnSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "John Doe",
            "password1" => "12345",
            "password2" => "12345",
            "email" => "new@example.com",
        ]);
        $this->request->method("serverName")->willReturn("example.com");
        $this->request->method("remoteAddress")->willReturn("127.0.0.1");
        $response = ($this->subject)($this->request);
        $this->assertEquals("new@example.com", $this->userRepository->findByUsername("john")->getEmail());
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertEquals("http://example.com/?User-Preferences", $response->location());
    }

    public function testUnregisterReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("john");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testUnregisterReportsNonExistentUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User '' does not exist!", $response->output());
    }

    public function testUnregisterReportsLockedUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("jane");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testUnregisterReportsWrongPassword(): void
    {
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("john");
        $this->request->method("unregisterPost")->willReturn([
            "oldpassword" => "54321",
            "name" => "",
            "email" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testUnregisterReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("john");
        $this->request->method("unregisterPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "email" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(" ", $response->output());
    }

    public function testUnregisterRedirectsOnSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("john");
        $this->request->method("unregisterPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "email" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertNull($this->userRepository->findByUsername("john"));
        // todo logging
        $this->assertEquals("http://example.com/?User-Preferences", $response->location());
    }
}
