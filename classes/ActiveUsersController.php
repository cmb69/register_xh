<?php

/**
 * Copyright (c) 2013-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\DocumentStore;
use Plib\Request;
use Plib\Response;
use Plib\View;
use Register\Model\ActiveUsers;

class ActiveUsersController
{
    /** @var array<string,string> */
    private $conf;

    /** @var DocumentStore */
    private $store;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, DocumentStore $store, View $view)
    {
        $this->conf = $conf;
        $this->store = $store;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        $activeUsers = ActiveUsers::retrieve($this->store);
        return Response::create($this->view->render("active_users", [
            "users" => $activeUsers->fetch($request->time() - (int) $this->conf["activity_period"]),
        ]));
    }
}
