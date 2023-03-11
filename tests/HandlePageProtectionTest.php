<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Value\User;

class HandlePageProtectionTest extends TestCase
{
    /** @var HandlePageProtection */
    private $sut;

    private $userRepository;

    /** @var Pages&MockObject */
    private $pages;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->pages = $this->createStub(Pages::class);
        $this->pages->method("data")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $this->sut = new HandlePageProtection($conf, $this->userRepository, $this->pages);
        $this->request = $this->createStub(Request::class);
    }

    public function testProtectsPages(): void
    {
        $this->userRepository->method("findByUsername")->willReturn(new User("cmb", "", ["guest"], "", "", "", ""));
        $this->pages->method("setContentOf")->withConsecutive(
            [2, "#CMSimple hide# {{{register_access('admin')}}}"],
        );
        ($this->sut)($this->request);
    }
}
