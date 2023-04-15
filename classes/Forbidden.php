<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\View;
use Register\Value\Response;

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
        return Response::forbid("<h1>" . $this->view->text("access_error") . "</h1>\n"
            . "<p>" . $this->view->text("access_error_text") . "</p>\n")
            ->withTitle($this->view->text("access_error"));
    }
}
