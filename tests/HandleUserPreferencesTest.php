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
use Register\Infra\FakeLogger;
use Register\Infra\FakeMailer;
use Register\Infra\FakePassword;
use Register\Infra\FakeRequest;
use Register\Infra\Random;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class HandleUserPreferencesTest extends TestCase
{
    private $conf;
    private $csrfProtector;
    private $dbService;
    private $userRepository;
    private $view;
    private $mailer;
    private $logger;
    private $password;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $hash = "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW";
        $users = [
            "john" => new User("john", $hash, ["guest"], "John Doe", "john@example.com", "activated", "secret"),
            "jane" => new User("jane", $hash, ["guest"], "Jane Doe", "jane@example.com", "locked", "secret"),
        ];
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = new FakeCsrfProtector;
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->writeUsers($users);
        $this->userRepository = new UserRepository($this->dbService);
        $this->view = new View("./views/", $text);
        $this->mailer = new FakeMailer(false, $text);
        $this->logger = new FakeLogger;
        $this->password = new FakePassword;
    }

    private function sut(): HandleUserPreferences
    {
        return new HandleUserPreferences(
            $this->conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $this->mailer,
            $this->logger,
            $this->password
        );
    }

    public function testReportsUnauthorizedAccessToVisitors(): void
    {
        $request = new FakeRequest(["query" => "&function=register_settings"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testReportsNonExistentUser(): void
    {
        $request = new FakeRequest(["query" => "&function=register_settings", "username" => "colt"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest(["query" => "&function=register_settings", "username" => "jane"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersForm(): void
    {
        $request = new FakeRequest(["query" => "&function=register_settings", "username" => "john"]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangePrefsReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testChangePrefsReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangePrefsReportsLockedUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testChangePrefsReportsWrongPassword(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "54321", "name" => "", "email" => ""]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testChangePrefsReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "", "email" => ""]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testChangePrefsReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "John Doe", "email" => "new@example.com"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangePrefsRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "John Doe", "email" => "new@example.com"],
            "serverName" => "example.com",
            "remoteAddress" => "127.0.0.1",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("new@example.com", $this->userRepository->findByUsername("john")->getEmail());
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testPasswordReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=password",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testPasswordReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=password",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersPasswordForm(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=password",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangePasswordReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testChangePasswordReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangePasswordReportsLockedUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testChangePasswordReportsWrongPassword(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "54321", "password1" => "", "password2" => ""]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testChangePasswordReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "password1" => "a", "password2" => "b"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testChangePasswordReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "password1" => "test", "password2" => "test"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangePasswordRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "password1" => "test", "password2" => "test"],
            "serverName" => "example.com",
            "remoteAddress" => "127.0.0.1",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals(
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            $this->userRepository->findByUsername("john")->getPassword()
        );
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testDeleteReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=delete",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testDeleteReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=delete",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersDeleteForm(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=delete",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testUnregisterReportsCsrf(): void
    {
        $this->csrfProtector->options(["check" => false]);
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=unregister",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testUnregisterReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=unregister",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testUnregisterReportsLockedUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=unregister",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testUnregisterReportsWrongPassword(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "54321"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testUnregisterReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "12345"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(" ", $response->output());
    }

    public function testUnregisterRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&function=register_settings&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "12345"],
        ]);
        $response = $this->sut()($request);
        $this->assertNull($this->userRepository->findByUsername("john"));
        $this->assertEquals(["info", "register", "logout", "User “john” deleted account"], $this->logger->lastEntry());
        $this->assertEquals("http://example.com/", $response->location());
    }
}
