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
use Register\Infra\Request;
use Register\Infra\View;

class ShowLoginForm
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
     * @var View
     */
    private $view;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $lang
     */
    public function __construct(
        array $conf,
        array $lang,
        View $view
    ) {
        $this->conf = $conf;
        $this->lang = $lang;
        $this->view = $view;
    }

    public function __invoke(?User $currentUser, Request $request): string
    {
        if ($currentUser === null) {
            return $this->renderLoginForm($request);
        } else {
            return $this->renderLoggedInForm($currentUser, $request);
        }
    }

    private function renderLoginForm(Request $request): string
    {
        $forgotPasswordPage = $this->lang['forgot_password'];
        $data = [
            'actionUrl' => $request->url()->relative(),
            'hasForgotPasswordLink' => $this->conf['password_forgotten']
                && !$request->url()->pageMatches($forgotPasswordPage),
            'forgotPasswordUrl' => $request->url()->withPage($forgotPasswordPage)->relative(),
            'hasRememberMe' => $this->conf['remember_user'],
            'isRegisterAllowed' => $this->conf['allowed_register'],
            'registerUrl' => $request->url()->withPage($this->lang['register'])->relative(),
        ];
        return $this->view->render('loginform', $data);
    }

    private function renderLoggedInForm(?User $currentUser, Request $request): string
    {
        $user = $currentUser;
        assert($user instanceof User);
        $userPrefPage = $this->lang['user_prefs'];
        $data = [
            'fullName' => $user->getName(),
            'hasUserPrefs' => $user->isActivated() &&
                !$request->url()->pageMatches($userPrefPage),
            'userPrefUrl' => $request->url()->withPage($userPrefPage)->relative(),
            'logoutUrl' => $request->url()->withPage("")->withParams(["function" => "registerlogout"])->relative(),
        ];
        return $this->view->render('loggedin-area', $data);
    }
}