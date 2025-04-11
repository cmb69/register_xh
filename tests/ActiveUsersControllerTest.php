<?php

/**
 * Copyright (c) 2013-2023 Christoph M. Becker
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
use Register\Model\ActiveUsers;

class ActiveUsersControllerTest extends TestCase
{
    private $conf;
    private $store;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->store = $this->createMock(DocumentStore::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut()
    {
        return new ActiveUsersController(
            $this->conf,
            $this->store,
            $this->view
        );
    }

    public function testRendersActiveUsers(): void
    {
        $this->store->method("retrieve")->willReturn(new ActiveUsers([
            "cmb" => strtotime("2023-04-16T17:13"),
            "jane" => strtotime("2023-04-16T17:14"),
            "john" => strtotime("2023-04-16T17:15"),
        ]));
        $response = $this->sut()(new FakeRequest(["time" => 0]));
        Approvals::verifyHtml($response->output());
    }
}
