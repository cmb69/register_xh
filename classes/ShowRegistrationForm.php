<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\View;

class ShowRegistrationForm
{
    /**
     * @var View
     */
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function __invoke(Request $request): string
    {
        return $this->view->render('registerform', [
            'actionUrl' => $request->url()->relative(),
            'name' => "",
            'username' => "",
            'password1' => "",
            'password2' => "",
            'email' => "",
        ]);
    }
}
