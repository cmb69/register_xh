<?php

/**
 * Copyright (c) 2013-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\ActivityRepository;
use Register\Infra\Request;
use Register\Infra\View;
use Register\Value\Response;

class ActiveUsers
{
    /** @var array<string,string> */
    private $conf;

    /** @var ActivityRepository */
    private $activityRepository;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, ActivityRepository $activityRepository, View $view)
    {
        $this->conf = $conf;
        $this->activityRepository = $activityRepository;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        return Response::create($this->view->render("active_users", [
            "users" => $this->activityRepository->find($request->time() - (int) $this->conf["activity_period"]),
        ]));
    }
}
