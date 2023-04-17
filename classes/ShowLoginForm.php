<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Value\Response;
use Register\Value\User;

class ShowLoginForm
{
    /** @var array<string,string> */
    private $conf;

    /** @var UserRepository */
    private $userRepository;

    /** @var UserGroupRepository */
    private $userGroupRepository;

    /** @var LoginManager */
    private $loginManager;

    /** @var Logger */
    private $logger;

    /** @var Password */
    private $password;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository,
        LoginManager $loginManager,
        Logger $logger,
        Password $password,
        View $view
    ) {
        $this->conf = $conf;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
        $this->password = $password;
        $this->view = $view;
    }

    public function __invoke(Request $request, bool $loggedInOnly = false): Response
    {
        if (!$loggedInOnly && !$request->username() && $request->function() === "registerlogin") {
            return $this->loginAction($request);
        }
        if ($request->username() !== "") {
            return Response::create($this->renderLoggedInForm($request));
        }
        if (!$loggedInOnly) {
            $login = ["username" => "", "password" => "", "remember" => ""];
            return Response::create($this->renderLoginForm($request, $login));
        }
        return Response::create("");
    }

    private function loginAction(Request $request): Response
    {
        $post = $request->registerLoginPost();
        if (!($user = $this->userRepository->findByUsername($post["username"]))) {
            $this->logger->logInfo("login", $this->view->plain("log_login_user", $post["username"]));
            return Response::create($this->renderLoginForm($request, $post, [["login_error_text"]]));
        }
        if (!$user->isActivated() && !$user->isLocked()) {
            $this->logger->logInfo("login", $this->view->plain("log_login_forbidden", $post["username"]));
            return Response::create($this->renderLoginForm($request, $post, [["login_error_text"]]));
        }
        if (!$this->password->verify($post["password"], $user->getPassword())) {
            $this->logger->logInfo("login", $this->view->plain("log_login_password", $post["username"]));
            return Response::create($this->renderLoginForm($request, $post, [["login_error_text"]]));
        }
        if ($this->password->needsRehash($user->getPassword())) {
            $this->userRepository->save($user->withPassword($this->password->hash($post["password"])));
        }
        $this->loginManager->login($user);
        $this->logger->logInfo("login", $this->view->plain("log_login", $post["username"]));
        if ($this->conf["allowed_remember"] && $post["remember"]) {
            return Response::redirect($this->loginUrl($request, $user))->withCookie(
                "register_remember",
                $user->getUsername() . "." . Util::hmac($user->getUsername(), $user->secret()),
                $request->time() + (100 * 24 * 60 * 60)
            );
        }
        return Response::redirect($this->loginUrl($request, $user));
    }

    /**
     * @param array{username:string,password:string,remember:string} $login
     * @param list<array{string}> $errors
     */
    private function renderLoginForm(Request $request, array $login, array $errors = []): string
    {
        return $this->view->render("loginform", [
            "errors" => $errors,
            "username" => $login["username"],
            "password" => $login["password"],
            "checked" => $login["remember"] ? "checked" : "",
            "hasForgotPasswordLink" => $this->conf["allowed_password_forgotten"]
                && $request->url()->page() !== "register+password",
            "forgotPasswordUrl" => $request->url()->withPage("register+password")->relative(),
            "hasRememberMe" => (bool) $this->conf["allowed_remember"],
            "isRegisterAllowed" => (bool) $this->conf["allowed_register"],
            "registerUrl" => $request->url()->withPage("register+user")->relative(),
        ]);
    }

    private function renderLoggedInForm(Request $request): string
    {
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return $this->view->error("err_user_does_not_exist", $request->username());
        }
        return $this->view->render("loggedin_area", [
            "fullName" => $user->getName(),
            "hasUserPrefs" => $this->conf["allowed_settings"] && $user->isActivated()
                && $request->url()->page() !== "register+settings",
            "userPrefUrl" => $request->url()->withPage("register+settings")->relative(),
            "logoutUrl" => $request->url()->with("function", "registerlogout")->relative(),
        ]);
    }

    private function loginUrl(Request $request, User $user): string
    {
        if (!($group = $this->userGroupRepository->findByGroupname($user->getAccessgroups()[0]))) {
            return $request->url()->absolute();
        }
        if (!$group->getLoginpage()) {
            return $request->url()->absolute();
        }
        return $request->url()->withPage($group->getLoginpage())->absolute();
    }
}
