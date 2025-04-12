<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use Register\Infra\FakeLogger;
use Register\Infra\LoginManager;
use Register\Infra\Pages;
use Register\Model\ActiveUsers;
use Register\Model\User;
use Register\Model\Users;

class MainTest extends TestCase
{
    private $conf;
    private $store;
    private $pages;
    private $logger;
    private $loginManager;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $this->conf = $plugin_cf['register'];
        $this->store = $this->createStub(DocumentStore::class);
        $this->store->method("retrieve")->willReturn(new Users(["jane" => $this->jane(), "john" => $this->john()]));
        $this->store->method("update")->willReturn(new ActiveUsers([]));
        $this->pages = $this->createMock(Pages::class);
        $this->pages->method("data")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $this->logger = new FakeLogger;
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut()
    {
        return new Main(
            $this->conf,
            $this->store,
            $this->pages,
            $this->logger,
            $this->loginManager,
            $this->view
        );
    }

    public function testProtectsPages(): void
    {
        $this->pages->expects($this->once())->method("setContentOf")->with(
            2, "{{{register_forbidden()}}}#CMSimple hide#"
        );
        $request = new FakeRequest(["username" => "john"]);
        $this->sut()($request);
    }

    public function testDoesNotProtectPagesInEditMode(): void
    {
        $this->pages->expects($this->never())->method("setContentOf");
        $request = new FakeRequest(["username" => "john", "admin" => true, "edit" => true]);
        $this->sut()($request);
    }

    public function testAutoLoginFailsOnBorkedCookie(): void
    {
        $request = new FakeRequest(["cookie" => ["register_remember" => "jane"]]);
        $response = $this->sut()($request);
        $this->assertEquals(["register_remember", "", 0], $response->cookie());
    }

    public function testAutoLoginFailsForNonExistentUser(): void
    {
        $request = new FakeRequest(["cookie" => ["register_remember" => "colt.6M5brgkTOP4AaQ9ZGLss7MZYyG4"]]);
        $response = $this->sut()($request);
        $this->assertEquals(["register_remember", "", 0], $response->cookie());
    }

    public function testAutoLoginFailsForDeactivatedUser(): void
    {
        $request = new FakeRequest(["cookie" => ["register_remember" => "john.6M5brgkTOP4AaQ9ZGLss7MZYyG4"]]);
        $response = $this->sut()($request);
        $this->assertEquals(["register_remember", "", 0], $response->cookie());
    }

    public function testAutoLoginFailsForManipulatedCookie(): void
    {
        $request = new FakeRequest(["cookie" => ["register_remember" => "jane.6M5brgkTOP4AaQ9ZGLss7MZYyG4"]]);
        $response = $this->sut()($request);
        $this->assertEquals(["register_remember", "", 0], $response->cookie());
    }

    public function testAutoLoginSucceeds(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->jane());
        $request = new FakeRequest(["cookie" => ["register_remember" => "jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0"]]);
        $this->sut()($request);
        $this->assertEquals(
            ["info", "register", "login", "User “jane” automatically logged in"],
            $this->logger->lastEntry()
        );
    }

    public function testForcesLogoutForUnknownUser(): void
    {
        $this->loginManager->expects($this->once())->method("logout");
        $request = new FakeRequest(["username" => "colt"]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testDoesNothing(): void
    {
        $request = new FakeRequest(["username" => "jane"]);
        $this->sut()($request);
    }

    private function jane(): User
    {
        return new User(
            "jane",
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated",
            "secret"
        );
    }

    private function john(): User
    {
        return new User(
            "john",
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            ["guest"],
            "John Doe",
            "john@example.com",
            "deactivated",
            "secret"
        );
    }
}
