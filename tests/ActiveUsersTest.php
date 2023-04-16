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
use Register\Infra\ActivityRepository;
use Register\Infra\FakeDbService;
use Register\Infra\FakeRequest;
use Register\Infra\Random;
use Register\Infra\View;

class ActiveUsersTest extends TestCase
{
    private $conf;
    private $activityRepository;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->activityRepository = new ActivityRepository(
            new FakeDbService("vfs://root/register/active_users.dat", "guest", $this->createMock(Random::class))
        );
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut()
    {
        return new ActiveUsers(
            $this->conf,
            $this->activityRepository,
            $this->view
        );
    }

    public function testRendersActiveUsers(): void
    {
        $this->activityRepository->update("cmb", strtotime("2023-04-16T17:13"));
        $this->activityRepository->update("jane", strtotime("2023-04-16T17:14"));
        $this->activityRepository->update("john", strtotime("2023-04-16T17:15"));
        $response = $this->sut()(new FakeRequest());
        Approvals::verifyHtml($response->output());
    }
}
