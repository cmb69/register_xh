<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\CurrentUser;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Value\User;

class HandlePageProtectionTest extends TestCase
{
    /** @var HandlePageProtection */
    private $sut;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var Pages&MockObject */
    private $pages;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->pages = $this->createStub(Pages::class);
        $this->pages->method("data")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $this->sut = new HandlePageProtection($conf, $this->currentUser, $this->pages);
        $this->request = $this->createStub(Request::class);
    }

    public function testProtectsPages(): void
    {
        $this->currentUser->method("get")->willReturn(new User("cmb", "", ["guest"], "", "", "", ""));
        $this->pages->method("setContentOf")->withConsecutive(
            [2, "#CMSimple hide# {{{register_access('admin')}}}"],
        );
        ($this->sut)($this->request);
    }
}
