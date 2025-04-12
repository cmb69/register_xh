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
use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use Register\Infra\FakeLogger;
use Register\Infra\FakePassword;
use Register\Infra\Mailer;
use Register\Model\User;
use Register\Model\Users;
use Register\PHPMailer\PHPMailer;

class HandleUserPreferencesTest extends TestCase
{
    private const HASH = "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW";

    private $conf;
    private $csrfProtector;
    private $store;
    private $view;
    /** @var PHPMailer&MockObject */
    private $phpMailer;
    private $mailer;
    private $logger;
    private $password;

    public function setUp(): void
    {
        $this->setUpStore();
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("0+pVtDm4xXAxUmA3/mrL");
        $this->view = new View("./views/", $text);
        $this->phpMailer = $this->getMockBuilder(PHPMailer::class)->onlyMethods(["send"])->getMock();
        $this->mailer = new Mailer($this->conf, $this->phpMailer);
        $this->logger = new FakeLogger;
        $this->password = new FakePassword;
    }

    private function setUpStore(): void
    {
        vfsStream::setup("root");
        $this->store = new DocumentStore(vfsStream::url("root/content/register/"));
        $users = Users::update($this->store);
        $users->createUser("john", self::HASH, ["guest"], "John Doe", "john@example.com", "activated", "secret");
        $users->createUser("jane", self::HASH, ["guest"], "Jane Doe", "jane@example.com", "locked", "secret");
        $this->store->commit();
    }

    private function sut(): HandleUserPreferences
    {
        return new HandleUserPreferences(
            $this->conf,
            $this->csrfProtector,
            $this->store,
            $this->view,
            $this->mailer,
            $this->logger,
            $this->password
        );
    }

    public function testReportsUnauthorizedAccessToVisitors(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&function=register_settings"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testReportsNonExistentUser(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&function=register_settings", "username" => "colt"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&function=register_settings", "username" => "jane"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersForm(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&function=register_settings", "username" => "john"]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangePrefsReportsCsrf(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testChangePrefsReportsNonExistentUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangePrefsReportsLockedUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testChangePrefsReportsWrongPassword(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "54321", "name" => "", "email" => ""]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testChangePrefsReportsValidationErrors(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "", "email" => ""]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testChangePrefsReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        vfsStream::setQuota(0);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "John Doe", "email" => "new@example.com"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangePrefsRedirectsOnSuccess(): void
    {
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_prefs",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "name" => "John Doe", "email" => "new@example.com"],
            "header" => ["HOST" => "example.com"],
        ]);
        $this->phpMailer->expects($this->any())->method("send")->willReturn(true);
        $response = $this->sut()($request);
        $this->assertEquals("postmaster@example.com", $this->phpMailer->From);
        $this->assertEquals([["new@example.com", ""]], $this->phpMailer->getToAddresses());
        $this->assertEquals(
            [["john@example.com", ""], ["postmaster@example.com", ""]],
            $this->phpMailer->getCcAddresses()
        );
        $this->assertEquals("Your user account at example.com", $this->phpMailer->Subject);
        Approvals::verifyString($this->phpMailer->Body);
        $this->assertEquals("new@example.com", Users::retrieve($this->store)->user("john")->getEmail());
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testPasswordReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=password",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testPasswordReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=password",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersPasswordForm(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=password",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangePasswordReportsCsrf(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testChangePasswordReportsNonExistentUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangePasswordReportsLockedUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testChangePasswordReportsWrongPassword(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "54321", "password1" => "", "password2" => ""]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testChangePasswordReportsValidationErrors(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "password1" => "a", "password2" => "b"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testChangePasswordReportsFailureToSave(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        vfsStream::setQuota(0);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "password1" => "test", "password2" => "test"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangePasswordRedirectsOnSuccess(): void
    {
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=change_password",
            "username" => "john",
            "post" => ["oldpassword" => "12345", "password1" => "test", "password2" => "test"],
            "header" => ["HOST" => "example.com"],
        ]);
        $this->phpMailer->expects($this->any())->method("send")->willReturn(true);
        $response = $this->sut()($request);
        $this->assertEquals("postmaster@example.com", $this->phpMailer->From);
        $this->assertEquals([["john@example.com", ""]], $this->phpMailer->getToAddresses());
        $this->assertEquals([["postmaster@example.com", ""]], $this->phpMailer->getCcAddresses());
        $this->assertEquals("Your user account at example.com", $this->phpMailer->Subject);
        Approvals::verifyString($this->phpMailer->Body);
        $this->assertEquals(
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            Users::retrieve($this->store)->user("john")->getPassword()
        );
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testDeleteReportsNonExistentUser(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=delete",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testDeleteReportsIfUserIsLocked(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=delete",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testRendersDeleteForm(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=delete",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testUnregisterReportsCsrf(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=unregister",
            "username" => "john",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testUnregisterReportsNonExistentUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=unregister",
            "username" => "colt",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testUnregisterReportsLockedUser(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=unregister",
            "username" => "jane",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testUnregisterReportsWrongPassword(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "54321"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testUnregisterReportsFailureToSave(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "12345"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(" ", $response->output());
    }

    public function testUnregisterRedirectsOnSuccess(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&function=register_settings&register_action=unregister",
            "username" => "john",
            "post" => ["oldpassword" => "12345"],
        ]);
        $response = $this->sut()($request);
        $this->assertNull(Users::retrieve($this->store)->user("john"));
        $this->assertEquals(["info", "register", "logout", "User “john” deleted account"], $this->logger->lastEntry());
        $this->assertEquals("http://example.com/", $response->location());
    }
}
