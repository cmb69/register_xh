<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\CurrentUser;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Value\User;

class HandlePageAccessTest extends TestCase
{
    /** @var HandlePageAccess */
    private $sut;

    /** @var CurrentUser */
    private $currentUser;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->sut = new HandlePageAccess($text, $this->currentUser);
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "Foo"));
    }

    public function testVisitorCannotAccessProtectedPage(): void
    {
        $response = ($this->sut)("admin", $this->request);
        $this->assertEquals("http://example.com/?Access-Restricted", $response->location());
    }

    public function testUserCanAccessProtectedPage(): void
    {
        $this->currentUser->method("get")->willReturn(new User("jane", "", ["admin"], "", "", ""));
        $response = ($this->sut)("admin", $this->request);
        $this->assertNull($response->location());
    }

    public function testUserCannotAccessProtectedPage(): void
    {
        $this->currentUser->method("get")->willReturn(new User("john", "", ["guest"], "", "", ""));
        $response = ($this->sut)("admin", $this->request);
        $this->assertEquals("http://example.com/?Access-Restricted", $response->location());
    }

    public function testDoesNotRedirectWhileSearching(): void
    {
        $this->request->expects($this->any())->method("function")->willReturn("search");
        $response = ($this->sut)("admin", $this->request);
        $this->assertNull($response->location());
    }
}
