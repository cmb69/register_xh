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
use Register\Infra\Pages;
use Register\Logic\Util;
use Register\Model\ActiveUsers;
use Register\Model\User;
use Register\Model\Users;

class Main
{
    /** @var array<string,string> */
    private $conf;

    /** @var DocumentStore */
    private $store;

    /** @var Pages */
    private $pages;

    /** @var Logger */
    private $logger;

    /** @var LoginManager */
    private $loginManager;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        DocumentStore $store,
        Pages $pages,
        Logger $logger,
        LoginManager $loginManager,
        View $view
    ) {
        $this->conf = $conf;
        $this->store = $store;
        $this->pages = $pages;
        $this->logger = $logger;
        $this->loginManager = $loginManager;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->username()) {
            $activeUsers = ActiveUsers::update($this->store);
            $activeUsers->updateUser($request->username(), $request->time());
            $this->store->commit();
        }
        if (!$request->admin() || !$request->edit()) {
            $this->protectPages($request);
        }
        if ($this->conf["allowed_remember"] && $request->cookie("register_remember") && !$request->username()) {
            return $this->autoLogin($request);
        }
        if ($request->username() && Users::retrieve($this->store)->user($request->username()) === null) {
            return $this->forcedLogout($request);
        }
        return Response::create();
    }

    /** @return void */
    private function protectPages(Request $request)
    {
        $user = Users::retrieve($this->store)->user($request->username() ?? "");
        $this->protectPagesNew($user);
        $this->protectedPagesLegacy($user);
    }

    /** @return void */
    private function protectPagesNew(?User $user)
    {
        $data = array_map(function (int $i, array $pd) {
            return [$this->pages->level($i), $pd["register_access"] ?? ""];
        }, array_keys($this->pages->data()), array_values($this->pages->data()));
        foreach (Util::accessAuthorization($user, $data) as $i => $auth) {
            if (!$auth) {
                $this->pages->setContentOf($i, $this->content());
            }
        }
    }

    /** @return void */
    private function protectedPagesLegacy(?User $user)
    {
        $contents = [];
        for ($i = 0; $i < $this->pages->count(); $i++) {
            $contents[] = $this->pages->content($i);
        }
        foreach (Util::accessAuthorizationLegacy($user, $contents) as $i => $auth) {
            if (!$auth) {
                $this->pages->setContentOf($i, $this->content());
            }
        }
    }

    private function content(): string
    {
        $content = "{{{register_forbidden()}}}";
        if ($this->conf["hide_pages"]) {
            $content .= "#CMSimple hide#";
        }
        return $content;
    }

    private function autoLogin(Request $request): Response
    {
        assert($this->conf["allowed_remember"] && $request->cookie("register_remember"));
        $parts = explode(".", $request->cookie("register_remember"));
        if (count($parts) !== 2) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        [$username, $token] = $parts;
        if (($user = Users::retrieve($this->store)->user($username)) === null) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        if (!$user->isActivated() && !$user->isLocked()) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        if (!hash_equals(Util::hmac($user->getUsername(), $user->secret()), $token)) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        $this->loginManager->login($user);
        $this->logger->logInfo("login", $this->view->plain("log_autologin", $username));
        return Response::create();
    }


    private function forcedLogout(Request $request): Response
    {
        assert($request->username() !== null);
        $activeUsers = ActiveUsers::update($this->store);
        $activeUsers->removeUser($request->username());
        $this->store->commit();
        $this->loginManager->logout();
        return Response::redirect($request->url()->absolute());
    }
}
