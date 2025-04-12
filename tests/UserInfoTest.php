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
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use Register\Model\User;
use Register\Model\Users;

class UserInfoTest extends TestCase
{
    private $conf;
    private $store;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->store = $this->createStub(DocumentStore::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut(): UserInfo
    {
        return new UserInfo(
            $this->conf,
            $this->store,
            $this->view
        );
    }

    public function testShowsNothingToVisitors(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request, "Register");
        $this->assertEquals("", $response->output());
    }

    public function testReportsNonExistentUser(): void
    {
        $this->store->method("retrieve")->willReturn(new Users([]));
        $request = new FakeRequest(["username" => "colt"]);
        $response = $this->sut()($request, "Register");
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testRendersUserInfo(): void
    {
        $user = new User("cmb", "12345", ["guest"], "Christoph Becker", "cmb@example.com", "1", "1");
        $this->store->method("retrieve")->willReturn(new Users(["cmb" => $user]));
        $request = new FakeRequest(["username" => "cmb"]);
        $response = $this->sut()($request, "Register");
        Approvals::verifyHtml($response->output());
    }
}
