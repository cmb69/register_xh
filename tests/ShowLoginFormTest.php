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
use Register\Infra\FakeDbService;
use Register\Infra\FakeLogger;
use Register\Infra\FakePassword;
use Register\Infra\FakeRequest;
use Register\Infra\LoginManager;
use Register\Infra\Random;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;
use Register\Value\UserGroup;

class ShowLoginFormTest extends TestCase
{
    private $userRepository;
    private $userGroupRepository;
    private $loginManager;
    private $logger;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers(array_values($this->users()));
        $dbService->writeGroups([new UserGroup("guest", ""), new UserGroup("admin", "Admin")]);
        $this->userRepository = new UserRepository($dbService);
        $this->userGroupRepository = new UserGroupRepository($dbService, "guest", $this->createMock(Random::class));
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->logger = new FakeLogger;
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut()
    {
        return new ShowLoginForm(
            XH_includeVar("./config/config.php", "plugin_cf")["register"],
            $this->userRepository,
            $this->userGroupRepository,
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

    public function testRendersNoLoggedInFormForVisitors(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request, true);
        $this->assertEquals("", $response->output());
    }

    public function testLoggedInFormReportsMissingUser(): void
    {
        $request = new FakeRequest(["username" => "colt"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testRendersLoggedInForm(): void
    {
        $request = new FakeRequest(["query" => "Foo", "username" => "jane"]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testLoginReportsMissingUser(): void
    {
        $request = new FakeRequest([
            "query" => "&function=registerlogin",
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
            "query" => "&function=registerlogin",
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
            "query" => "&function=registerlogin",
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
            "query" => "Foo",
            "post" => ["function" => "registerlogin", "username" => "james", "password" => "test", "remember" => "on"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Foo", $response->location());
        $this->assertEquals(
            [["register_remember", "james.6M5brgkTOP4AaQ9ZGLss7MZYyG4", "8640000"]],
            $response->cookies()
        );
        $this->assertEquals(["info", "register", "login", "User “james” logged in"], $this->logger->lastEntry());
    }

    public function testLoginRedirectsToGroupPageOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["jane"]);
        $request = new FakeRequest([
            "post" => ["function" => "registerlogin", "username" => "jane", "password" => "12345", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Admin", $response->location());
        $this->assertEquals(["info", "register", "login", "User “jane” logged in"], $this->logger->lastEntry());
    }

    public function testLoginRedirectsToSamePageOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["joan"]);
        $request = new FakeRequest([
            "query" => "Foo",
            "post" => ["function" => "registerlogin", "username" => "joan", "password" => "test", "remember" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Foo", $response->location());
        $this->assertEquals(["info", "register", "login", "User “joan” logged in"], $this->logger->lastEntry());
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
}
