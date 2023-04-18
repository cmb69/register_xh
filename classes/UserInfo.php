<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\Response;
use Register\Value\Url;
use Register\Value\User;

class UserInfo
{
    /** @var array<string,string> */
    private $conf;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, UserRepository $userRepository, View $view)
    {
        $this->conf = $conf;
        $this->userRepository = $userRepository;
        $this->view = $view;
    }

    public function __invoke(Request $request, string $pageUrl): Response
    {
        if (!$request->username()) {
            return Response::create();
        }
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        return Response::create($this->render($user, $request->url()->withPage($pageUrl)));
    }

    public function render(User $user, Url $url): string
    {
        return $this->view->render("loggedin_area", [
            "fullName" => $user->getName(),
            "hasUserPrefs" => $this->conf["allowed_settings"] && $user->isActivated(),
            "userPrefUrl" => $url->with("function", "register_settings")->relative(),
            "logoutUrl" => $url->with("register_action", "logout")->relative(),
        ]);
    }
}
