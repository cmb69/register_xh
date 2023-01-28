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
     * @var string
     */
    private $pluginVersion;

    /**
     * @var SystemCheckService
     */
    private $systemCheckService;

    /**
     * @var View
     */
    private $view;

    public function __construct(string $pluginVersion, SystemCheckService $systemCheckService, View $view)
    {
        $this->pluginVersion = $pluginVersion;
        $this->systemCheckService = $systemCheckService;
        $this->view = $view;
    }

    public function execute(): string
    {
        return $this->view->render('info', [
            'version' => $this->pluginVersion,
            'checks' => $this->systemCheckService->getChecks(),
        ]);
    }
}
