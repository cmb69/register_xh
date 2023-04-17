<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\ActivityRepository;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Logic\Util;
use Register\Value\Response;
use Register\Value\User;

class Main
{
    /** @var array<string,string> */
    private $conf;

    /** @var UserRepository */
    private $userRepository;

    /** @var ActivityRepository */
    private $activityRepository;

    /** @var Pages */
    private $pages;

    /** @var Logger */
    private $logger;

    /** @var LoginManager */
    private $loginManager;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        UserRepository $userRepository,
        ActivityRepository $activityRepository,
        Pages $pages,
        Logger $logger,
        LoginManager $loginManager
    ) {
        $this->conf = $conf;
        $this->userRepository = $userRepository;
        $this->activityRepository = $activityRepository;
        $this->pages = $pages;
        $this->logger = $logger;
        $this->loginManager = $loginManager;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->username()) {
            $this->activityRepository->update($request->username(), $request->time());
        }
        if (!$request->editMode()) {
            $this->protectPages($request);
        }
        if ($this->conf["allowed_remember"] && $request->cookie("register_remember") && !$request->username()) {
            return $this->autoLogin($request);
        }
        if ($request->username() && $request->function() === "registerlogout") {
            return $this->logoutAction($request);
        }
        if ($request->username() && !$this->userRepository->findByUsername($request->username())) {
            return $this->forcedLogout($request);
        }
        return Response::create();
    }

    /** @return void */
    private function protectPages(Request $request)
    {
        $user = $this->userRepository->findByUsername($request->username());
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
        if (!($user = $this->userRepository->findByUsername($username))) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        if (!$user->isActivated() && !$user->isLocked()) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        if (!hash_equals(Util::hmac($user->getUsername(), $user->secret()), $token)) {
            return Response::create()->withCookie("register_remember", "", 0);
        }
        $this->loginManager->login($user);
        $this->logger->logInfo("login", "$username automatically logged in");
        return Response::create();
    }


    private function logoutAction(Request $request): Response
    {
        $this->loginManager->logout();
        $this->activityRepository->update($request->username(), 0);
        $this->logger->logInfo('logout', "{$request->username()} logged out");
        if ($this->conf["allowed_remember"] && $request->cookie("register_remember")) {
            return Response::redirect($request->url()->absolute())->withCookie("register_remember", "", 0);
        }
        return Response::redirect($request->url()->absolute());
    }

    private function forcedLogout(Request $request): Response
    {
        $this->loginManager->logout();
        $this->activityRepository->update($request->username(), 0);
        return Response::redirect($request->url()->absolute());
    }
}
