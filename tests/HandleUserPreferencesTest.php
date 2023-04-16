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
use Register\Infra\FakeRequest;
use Register\Infra\Logger;
use Register\Infra\Random;
use Register\Infra\Request;
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
    }

    public function testReportsNonExistentUser(): void
    {
        $request = new FakeRequest(["query" => "User-Preferences", "username" => "colt"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest(["query" => "User-Preferences", "username" => "jane"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersForm(): void
    {
        $request = new FakeRequest(["query" => "User-Preferences", "username" => "john"]);
        $response = ($this->subject)($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangePrefsReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "User-Preferences&register_action=change_prefs", "username" => "john"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testChangePrefsReportsNonExistentUser(): void
    {
        $request = new FakeRequest(["query" => "User-Preferences&register_action=change_prefs", "username" => "colt"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangePrefsReportsLockedUser(): void
    {
        $request = new FakeRequest(["query" => "User-Preferences&register_action=change_prefs", "username" => "jane"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testChangePrefsReportsWrongPassword(): void
    {
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=change_prefs",
            "username" => "john",
            "post" => [
                "oldpassword" => "54321",
                "name" => "",
                "password1" => "",
                "password2" => "",
                "email" => "",
            ]
        ]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testChangePrefsReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=change_prefs",
            "username" => "john",
            "post" => [
                "oldpassword" => "12345",
                "name" => "",
                "password1" => "one",
                "password2" => "two",
                "email" => "",
            ]
        ]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testChangePrefsReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=change_prefs",
            "username" => "john",
            "post" => [
                "oldpassword" => "12345",
                "name" => "John Doe",
                "password1" => "12345",
                "password2" => "12345",
                "email" => "new@example.com",
            ]
        ]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangePrefsRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=change_prefs",
            "username" => "john",
            "post" => [
                "oldpassword" => "12345",
                "name" => "John Doe",
                "password1" => "12345",
                "password2" => "12345",
                "email" => "new@example.com",
            ],
            "serverName" => "example.com",
            "remoteAddress" => "127.0.0.1",
        ]);
        $response = ($this->subject)($request);
        $this->assertEquals("new@example.com", $this->userRepository->findByUsername("john")->getEmail());
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertEquals(
            "http://example.com/?User-Preferences&register_action=change_prefs",
            $response->location()
        );
    }

    public function testUnregisterReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest(["query" => "User-Preferences&register_action=unregister", "username" => "john"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testUnregisterReportsNonExistentUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $request = new FakeRequest(["query" => "User-Preferences&register_action=unregister", "username" => "colt"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testUnregisterReportsLockedUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $request = new FakeRequest(["query" => "User-Preferences&register_action=unregister", "username" => "jane"]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testUnregisterReportsWrongPassword(): void
    {
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "54321", "name" => "", "email" => ""],
        ]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testUnregisterReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "", "email" => ""],
        ]);
        $response = ($this->subject)($request);
        $this->assertStringContainsString(" ", $response->output());
    }

    public function testUnregisterRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "User-Preferences&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "", "email" => ""],
        ]);
        $response = ($this->subject)($request);
        $this->assertNull($this->userRepository->findByUsername("john"));
        // todo logging
        $this->assertEquals("http://example.com/?User-Preferences&register_action=unregister", $response->location());
    }
}
