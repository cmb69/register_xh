<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Value\User;
use Register\Infra\View;

class LoginFormController
{
    /**
     * @var array<string,string>
     */
    private $conf;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var string
     */
    private $scriptName;

    /**
     * @var string
     */
    private $currentPage;

    /**
     * @var User|null
     */
    private $currentUser;

    /**
     * @var View
     */
    private $view;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $lang
     * @param User|null $currentUser
     */
    public function __construct(
        array $conf,
        array $lang,
        string $scriptName,
        string $currentPage,
        $currentUser,
        View $view
    ) {
        $this->conf = $conf;
        $this->lang = $lang;
        $this->scriptName = $scriptName;
        $this->currentPage = $currentPage;
        $this->currentUser = $currentUser;
        $this->view = $view;
    }

    public function execute(): string
    {
        if ($this->currentUser === null) {
            return $this->renderLoginForm();
        } else {
            return $this->renderLoggedInForm();
        }
    }

    private function renderLoginForm(): string
    {
        $forgotPasswordUrl = uenc($this->lang['forgot_password']);
        $registerUrl = uenc($this->lang['register']);
        $data = [
            'actionUrl' => "$this->scriptName?$this->currentPage",
            'hasForgotPasswordLink' => $this->conf['password_forgotten']
                && urldecode($this->currentPage) != $forgotPasswordUrl,
            'forgotPasswordUrl' => "$this->scriptName?$forgotPasswordUrl",
            'hasRememberMe' => $this->conf['remember_user'],
            'isRegisterAllowed' => $this->conf['allowed_register'],
            'registerUrl' => "$this->scriptName?$registerUrl",
        ];
        return $this->view->render('loginform', $data);
    }

    private function renderLoggedInForm(): string
    {
        $user = $this->currentUser;
        assert($user instanceof User);
        $userPrefUrl = uenc($this->lang['user_prefs']);
        $data = [
            'fullName' => $user->getName(),
            'hasUserPrefs' => $user->isActivated() &&
                urldecode($this->currentPage) != $userPrefUrl,
            'userPrefUrl' => "?$userPrefUrl",
            'logoutUrl' => "$this->scriptName?&function=registerlogout",
        ];
        return $this->view->render('loggedin-area', $data);
    }
}
