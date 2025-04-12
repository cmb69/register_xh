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
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use Register\Infra\FakeLogger;
use Register\Infra\FakePassword;
use Register\Infra\LoginManager;
use Register\Model\ActiveUsers;
use Register\Model\Groups;
use Register\Model\User;
use Register\Model\Users;

class ShowLoginFormTest extends TestCase
{
    private $store;
    private $loginManager;
    private $logger;
    private $view;

    public function setUp(): void
    {
        $this->setUpStore();
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->logger = new FakeLogger;
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function setUpStore(): void
    {
        vfsStream::setup("root");
        $this->store = new DocumentStore(vfsStream::url("root/content/register/"));
        $groups = Groups::update($this->store);
        $groups->createGroup("admin", "Admin");
        $users = Users::update($this->store);
        $users->createUser(
            "jane",
            "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW",
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated",
            "secret"
        );
        $users->createUser("john", "12345", ["guest"], "John Doe", "john@example.com", "deactivated", "secret");
        $users->createUser(
            "james",
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            ["unknown"],
            "James Doe",
            "james@example.com",
            "activated",
            "secret"
        );
        $users->createUser(
            "joan",
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            ["guest"],
            "Joan Doe",
            "joan@example.com",
            "activated",
            "secret"
        );
        $this->store->commit();
    }
    private function sut()
    {
        return new ShowLoginForm(
            XH_includeVar("./config/config.php", "plugin_cf")["register"],
            $this->store,
            $this->loginManager,
            $this->logger,
            new FakePassword,
            $this->view
        );
    }

    public function testRendersLoginForm(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testLoggedInFormReportsMissingUser(): void
    {
        $request = new FakeRequest(["username" => "colt"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testRendersLoggedInForm(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?Foo", "username" => "jane"]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testLoginReportsMissingAuthorization(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&register_action=login", "username" => "cmb"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testLoginReportsMissingUser(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&register_action=login",
            "post" => ["username" => "colt", "password" => "", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "You entered a wrong username or password, or your account still is not activated.",
            $response->output()
        );
        $this->assertEquals(["info", "register", "login", "User “colt” does not exist"], $this->logger->lastEntry());
    }

    public function testLoginReportsDeactivatedUser(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&register_action=login",
            "post" => ["username" => "john", "password" => "", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "You entered a wrong username or password, or your account still is not activated.",
            $response->output()
        );
        $this->assertEquals(
            ["info", "register", "login", "User “john” is not allowed to log in"],
            $this->logger->lastEntry()
        );
    }

    public function testLoginReportsWrongPassword(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&register_action=login",
            "post" => ["username" => "jane", "password" => "", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "You entered a wrong username or password, or your account still is not activated.",
            $response->output()
        );
        $this->assertEquals(
            ["info", "register", "login", "User “jane” submitted wrong password"],
            $this->logger->lastEntry()
        );
    }

    public function testLoginRedirectsWithCookieOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["james"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Foo",
            "post" => ["register_action" => "login", "username" => "james", "password" => "test", "remember" => "on"],
            "time" => 0,
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Foo", $response->location());
        $this->assertEquals(
            ["register_remember", "james.6M5brgkTOP4AaQ9ZGLss7MZYyG4", "8640000"],
            $response->cookie()
        );
        $this->assertEquals(["info", "register", "login", "User “james” logged in"], $this->logger->lastEntry());
    }

    public function testLoginRedirectsToGroupPageOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["jane"]);
        $request = new FakeRequest([
            "post" => ["register_action" => "login", "username" => "jane", "password" => "12345", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Admin", $response->location());
        $this->assertEquals(["info", "register", "login", "User “jane” logged in"], $this->logger->lastEntry());
    }

    public function testLoginRedirectsToSamePageOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["joan"]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Foo",
            "post" => ["register_action" => "login", "username" => "joan", "password" => "test", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Foo", $response->location());
        $this->assertEquals(["info", "register", "login", "User “joan” logged in"], $this->logger->lastEntry());
    }

    public function testLogoutReportsMissingAuthorization(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&register_action=logout"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testLogoutSucceeds(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&register_action=logout", "username" => "jane"]);
        $response = $this->sut()($request);
        $this->assertCount(0, ActiveUsers::retrieve($this->store)->fetch(12345678));
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testSuccessfulLogoutDeletesCookie(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&register_action=logout",
            "username" => "jane",
            "cookie" => ["register_remember" => "jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0"],
        ]);
        $response = $this->sut()($request);
        $this->assertCount(0, ActiveUsers::retrieve($this->store)->fetch(12345678));
        $this->assertEquals(["register_remember", "", 0], $response->cookie());
        $this->assertEquals("http://example.com/", $response->location());
        $this->assertEquals(["info", "register", "logout", "User “jane” logged out"], $this->logger->lastEntry());
    }

    private function users(): array
    {
        return [
            "jane" => new User(
                "jane",
                "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW",
                ["admin"],
                "Jane Doe",
                "jane@example.com",
                "activated",
                "secret"
            ),
            "john" => new User("john", "12345", ["guest"], "John Doe", "john@example.com", "deactivated", "secret"),
            "james" => new User(
                "james",
                "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
                ["unknown"],
                "James Doe",
                "james@example.com",
                "activated",
                "secret"
            ),
            "joan" => new User(
                "joan",
                "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
                ["guest"],
                "Joan Doe",
                "joan@example.com",
                "activated",
                "secret"
            ),
        ];
    }

    private function activeUsers(): ActiveUsers
    {
        return new ActiveUsers([
            "jane" => 12345678,
        ]);
    }
}
