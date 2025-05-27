<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\DocumentStore;
use Plib\Request;
use Plib\Response;
use Plib\View;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Password;
use Register\Logic\Util;
use Register\Model\ActiveUsers;
use Register\Model\Groups;
use Register\Model\User;
use Register\Model\Users;

class ShowLoginForm
{
    /** @var array<string,string> */
    private $conf;

    /** @var DocumentStore */
    private $store;

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
        DocumentStore $store,
        LoginManager $loginManager,
        Logger $logger,
        Password $password,
        View $view
    ) {
        $this->conf = $conf;
        $this->store = $store;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
        $this->password = $password;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->post("register_action") ?? $request->get("register_action")) {
            default:
                return $this->show($request);
            case "login":
                return $this->loginAction($request);
            case "logout":
                return $this->logoutAction($request);
        }
    }

    private function show(Request $request): Response
    {
        if ($request->username() !== null) {
            return Response::create($this->renderLoggedInForm($request));
        }
        $login = ["username" => "", "password" => "", "remember" => ""];
        return Response::create($this->renderLoginForm($request, $login));
    }

    private function loginAction(Request $request): Response
    {
        if ($request->username()) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        $post = [
            "username" => $request->post("username") ?? "",
            "password" => $request->post("password") ?? "",
            "remember" => $request->post("remember") ?? "",
        ];
        $users = Users::update($this->store);
        if (!($user = $users->user($post["username"]))) {
            $this->store->rollback();
            $this->logger->logInfo("login", $this->view->plain("log_login_user", $post["username"]));
            return Response::create($this->renderLoginForm($request, $post, [["error_login"]]));
        }
        if (!$user->isActivated() && !$user->isLocked()) {
            $this->store->rollback();
            $this->logger->logInfo("login", $this->view->plain("log_login_forbidden", $post["username"]));
            return Response::create($this->renderLoginForm($request, $post, [["error_login"]]));
        }
        if (!$this->password->verify($post["password"], $user->getPassword())) {
            $this->store->rollback();
            $this->logger->logInfo("login", $this->view->plain("log_login_password", $post["username"]));
            return Response::create($this->renderLoginForm($request, $post, [["error_login"]]));
        }
        if ($this->password->needsRehash($user->getPassword())) {
            $user->setPassword($this->password->hash($post["password"]));
            $this->store->commit();
        }
        $this->store->rollback();
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
            "hasForgotPasswordLink" => $this->conf["allowed_password_forgotten"],
            "forgotPasswordUrl" => $request->url()->with("function", "register_password")->relative(),
            "hasRememberMe" => (bool) $this->conf["allowed_remember"],
            "isRegisterAllowed" => (bool) $this->conf["allowed_register"],
            "registerUrl" => $request->url()->with("function", "register_user")->relative(),
            "action" => $request->url()->with("register_action", "login")->relative(),
        ]);
    }

    private function renderLoggedInForm(Request $request): string
    {
        if (!($user = Users::retrieve($this->store)->user($request->username() ?? ""))) {
            return $this->view->message("fail", "error_user_does_not_exist", $request->username() ?? "");
        }
        return $this->view->render("loggedin_area", [
            "fullName" => $user->getName(),
            "hasUserPrefs" => $this->conf["allowed_settings"] && $user->isActivated(),
            "userPrefUrl" => $request->url()->with("function", "register_settings")->relative(),
            "password_url" => $this->conf["allowed_settings"] && $user->isActivated()
                ? $request->url()->with("function", "register_settings")
                    ->with("register_action", "password")->relative()
                : null,
            "delete_url" => $this->conf["allowed_settings"]  && $user->isActivated()
                ? $request->url()->with("function", "register_settings")
                    ->with("register_action", "delete")->relative()
                : null,
            "logoutUrl" => $request->url()->with("register_action", "logout")->relative(),
        ]);
    }

    private function loginUrl(Request $request, User $user): string
    {
        $groups = Groups::retrieve($this->store);
        if (!($group = $groups->group($user->getAccessgroups()[0]))) {
            return $request->url()->without("register_action")->absolute();
        }
        if (!$group->getLoginpage()) {
            return $request->url()->without("register_action")->absolute();
        }
        return $request->url()->page($group->getLoginpage())->absolute();
    }

    private function logoutAction(Request $request): Response
    {
        if (!$request->username()) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        $activeUsers = ActiveUsers::update($this->store);
        $activeUsers->removeUser($request->username());
        $this->store->commit();
        $this->loginManager->logout();
        $this->logger->logInfo("logout", $this->view->plain("log_logout", $request->username()));
        if ($this->conf["allowed_remember"] && $request->cookie("register_remember")) {
            return Response::redirect($request->url()->without("register_action")->absolute())
                ->withCookie("register_remember", "", 0);
        }
        return Response::redirect($request->url()->without("register_action")->absolute());
    }
}
