<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\Response;
use Plib\View;

class Forbidden
{
    /** @var View */
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }
    public function __invoke(): Response
    {
        return Response::error(403, "<h1>" . $this->view->text("label_access_error") . "</h1>\n"
            . "<p>" . $this->view->text("error_access") . "</p>\n")
            ->withTitle($this->view->text("label_access_error"));
    }
}
