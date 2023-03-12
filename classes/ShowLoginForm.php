<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\Response;
use Register\Value\User;

class ShowLoginForm
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(
        array $conf,
        array $text,
        UserRepository $userRepository,
        View $view
    ) {
        $this->conf = $conf;
        $this->text = $text;
        $this->userRepository = $userRepository;
        $this->view = $view;
    }

    public function __invoke(Request $request, bool $loggedInOnly = false): Response
    {
        if ($request->username() === "") {
            if ($loggedInOnly) {
                return Response::create("");
            }
            return Response::create($this->renderLoginForm($request));
        } else {
            return Response::create($this->renderLoggedInForm($request));
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
        $user = $this->userRepository->findByUsername($request->username());
        assert($user instanceof User);
        $userPrefPage = $this->text['user_prefs'];
        $data = [
            'fullName' => $user->getName(),
            'hasUserPrefs' => $user->isActivated() &&
                !$request->url()->pageMatches($userPrefPage),
            'userPrefUrl' => $request->url()->withPage($userPrefPage)->relative(),
            'logoutUrl' => $request->url()->withPage("")->withParams(["function" => "registerlogout"])->relative(),
        ];
        return $this->view->render('loggedin_area', $data);
    }
}
