<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Value\User;

class HandlePageAccessTest extends TestCase
{
    /** @var HandlePageAccess */
    private $sut;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->sut = new HandlePageAccess($text, $this->userRepository);
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "Foo"));
    }

    public function testVisitorCannotAccessProtectedPage(): void
    {
        $response = ($this->sut)($this->request, "admin");
        $this->assertEquals("http://example.com/?Access-Restricted", $response->location());
    }

    public function testUserCanAccessProtectedPage(): void
    {
        $this->userRepository->method("findByUsername")->willReturn(new User("jane", "", ["admin"], "", "", "", ""));
        $response = ($this->sut)($this->request, "admin");
        $this->assertNull($response->location());
    }

    public function testUserCannotAccessProtectedPage(): void
    {
        $this->userRepository->method("findByUsername")->willReturn(new User("john", "", ["guest"], "", "", "", ""));
        $response = ($this->sut)($this->request, "admin");
        $this->assertEquals("http://example.com/?Access-Restricted", $response->location());
    }

    public function testDoesNotRedirectWhileSearching(): void
    {
        $this->request->method("function")->willReturn("search");
        $response = ($this->sut)($this->request, "admin");
        $this->assertNull($response->location());
    }
}
