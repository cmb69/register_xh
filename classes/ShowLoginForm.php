<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Infra\Request;
use Register\Infra\Response;
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
    private $text;

    /**
     * @var View
     */
    private $view;

    /** @var CurrentUser */
    private $currentUser;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(
        array $conf,
        array $text,
        View $view,
        CurrentUser $currentUser
    ) {
        $this->conf = $conf;
        $this->text = $text;
        $this->view = $view;
        $this->currentUser = $currentUser;
    }

    public function __invoke(Request $request, bool $loggedInOnly = false): Response
    {
        if ($this->currentUser->get() === null) {
            if ($loggedInOnly) {
                return new Response;
            }
            return (new Response)->body($this->renderLoginForm($request));
        } else {
            return (new Response)->body($this->renderLoggedInForm($request));
        }
    }

    private function renderLoginForm(Request $request): string
    {
        $forgotPasswordPage = $this->text['forgot_password'];
        $data = [
            'actionUrl' => $request->url()->relative(),
            'hasForgotPasswordLink' => $this->conf['password_forgotten']
                && !$request->url()->pageMatches($forgotPasswordPage),
            'forgotPasswordUrl' => $request->url()->withPage($forgotPasswordPage)->relative(),
            'hasRememberMe' => $this->conf['remember_user'],
            'isRegisterAllowed' => $this->conf['allowed_register'],
            'registerUrl' => $request->url()->withPage($this->text['register'])->relative(),
        ];
        return $this->view->render('loginform', $data);
    }

    private function renderLoggedInForm(Request $request): string
    {
        $user = $this->currentUser->get();
        assert($user instanceof User);
        $userPrefPage = $this->text['user_prefs'];
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
