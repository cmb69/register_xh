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
use Register\Infra\FakePassword;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;
use Register\Value\UserGroup;

class ShowLoginFormTest extends TestCase
{
    private $text;
    private $userRepository;
    private $userGroupRepository;
    private $loginManager;
    private $logger;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers(array_values($this->users()));
        $dbService->writeGroups([new UserGroup("guest", ""), new UserGroup("admin", "Admin")]);
        $this->userRepository = new UserRepository($dbService);
        $this->userGroupRepository = new UserGroupRepository($dbService, "guest", $this->createMock(Random::class));
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->view = new View("./views/", $this->text);
    }

    private function sut()
    {
        return new ShowLoginForm(
            XH_includeVar("./config/config.php", "plugin_cf")["register"],
            $this->text,
            $this->userRepository,
            $this->userGroupRepository,
            $this->loginManager,
            $this->logger,
            new FakePassword,
            $this->view
        );
    }

    private function request(): Request
    {
        $request = $this->createMock(Request::class);
        $request->method("url")->willReturn(new Url("/", "Foo"));
        return $request;
    }

    public function testRendersLoginForm(): void
    {
        $response = $this->sut()($this->request());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersNoLoggedInFormForVisitors(): void
    {
        $response = $this->sut()($this->request(), true);
        $this->assertEquals("", $response->output());
    }

    public function testLoggedInFormReportsMissingUser(): void
    {
        $request = $this->request();
        $request->method("username")->willReturn("colt");
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testRendersLoggedInForm(): void
    {
        $request = $this->request();
        $request->method("username")->willReturn("jane");
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testLoginReportsMissingUser(): void
    {
        $this->logger->expects($this->once())->method("logError")->with("login", "Unknown user 'colt'");
        $request = $this->request();
        $request->method("function")->willReturn("registerlogin");
        $request->method("registerLoginPost")->willReturn(["username" => "colt", "password" => "", "remember" => ""]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "You entered a wrong username or password, or your account still is not activated.",
            $response->output()
        );
    }

    public function testLoginReportsDeactivatedUser(): void
    {
        $this->logger->expects($this->once())->method("logError")->with("login", "User 'john' is not allowed to login");
        $request = $this->request();
        $request->method("function")->willReturn("registerlogin");
        $request->method("registerLoginPost")->willReturn(["username" => "john", "password" => "", "remember" => ""]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "You entered a wrong username or password, or your account still is not activated.",
            $response->output()
        );
    }

    public function testLoginReportsWrongPassword(): void
    {
        $this->logger->expects($this->once())->method("logError")->with("login", "User 'jane' submitted wrong password");
        $request = $this->request();
        $request->method("function")->willReturn("registerlogin");
        $request->method("registerLoginPost")->willReturn(["username" => "jane", "password" => "", "remember" => ""]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "You entered a wrong username or password, or your account still is not activated.",
            $response->output()
        );
    }

    public function testLoginRedirectsWithCookieOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["james"]);
        $this->logger->expects($this->once())->method("logInfo")->with("login", "User 'james' logged in");
        $request = $this->request();
        $request->method("function")->willReturn("registerlogin");
        $request->method("registerLoginPost")->willReturn(["username" => "james", "password" => "test", "remember" => "on"]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Foo", $response->location());
        $this->assertEquals(
            [["register_remember", "james.6M5brgkTOP4AaQ9ZGLss7MZYyG4", "8640000"]],
            $response->cookies()
        );
    }

    public function testLoginRedirectsToGroupPageOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["jane"]);
        $this->logger->expects($this->once())->method("logInfo")->with("login", "User 'jane' logged in");
        $request = $this->request();
        $request->method("function")->willReturn("registerlogin");
        $request->method("registerLoginPost")->willReturn(["username" => "jane", "password" => "12345", "remember" => ""]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Admin", $response->location());
    }

    public function testLoginRedirectsToSamePageOnSuccess(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->users()["joan"]);
        $this->logger->expects($this->once())->method("logInfo")->with("login", "User 'joan' logged in");
        $request = $this->request();
        $request->method("function")->willReturn("registerlogin");
        $request->method("registerLoginPost")->willReturn(["username" => "joan", "password" => "test", "remember" => ""]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?Foo", $response->location());
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
