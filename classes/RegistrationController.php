<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\View;

class RegistrationController
{
    /** @var string */
    private $scriptName;

    /** @var string */
    private $selectedUrl;

    /**
     * @var View
     */
    private $view;

    public function __construct(
        string $scriptName,
        string $selectedUrl,
        View $view
    ) {
        $this->scriptName = $scriptName;
        $this->selectedUrl = $selectedUrl;
        $this->view = $view;
    }

    public function defaultAction(): string
    {
        return $this->view->render('registerform', [
            'actionUrl' => $this->scriptName . "?" .$this->selectedUrl,
            'name' => "",
            'username' => "",
            'password1' => "",
            'password2' => "",
            'email' => "",
        ]);
    }
}
