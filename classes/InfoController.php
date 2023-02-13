<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class InfoController
{
    /**
     * @var SystemCheckService
     */
    private $systemCheckService;

    /**
     * @var View
     */
    private $view;

    public function __construct(SystemCheckService $systemCheckService, View $view)
    {
        $this->systemCheckService = $systemCheckService;
        $this->view = $view;
    }

    public function execute(): string
    {
        return $this->view->render('info', [
            'version' => Plugin::VERSION,
            'checks' => $this->systemCheckService->getChecks(),
        ]);
    }
}
