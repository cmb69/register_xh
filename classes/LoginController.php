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
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Logic\Util;
use Register\Value\Response;

class LoginController
{
    /** @var array<string,string> */
    private $conf;

    /** @var UserRepository */
    private $userRepository;

    /** @var ActivityRepository */
    private $activityRepository;

    /** @var Logger */
    private $logger;

    /** @var LoginManager */
    private $loginManager;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        UserRepository $userRepository,
        ActivityRepository $activityRepository,
        Logger $logger,
        LoginManager $loginManager
    ) {
        $this->conf = $conf;
        $this->userRepository = $userRepository;
        $this->activityRepository = $activityRepository;
        $this->logger = $logger;
        $this->loginManager = $loginManager;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->username()) {
            $this->activityRepository->update($request->username(), $request->time());
        }
        if ($this->conf["allowed_remember"] && $request->cookie("register_remember")
                && !$request->username()) {
            return $this->autoLogin($request);
        }
        if ($request->username() && $request->function() === "registerlogout") {
            return $this->logoutAction($request);
        }
        if ($request->username() && !$this->userRepository->findByUsername($request->username())) {
            return $this->forcedLogout($request);
        }
        return new Response();
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
            return Response::create()->withCookie("register_remember", "", 0);
        }
        return Response::create();
    }

    private function forcedLogout(Request $request): Response
    {
        $this->loginManager->logout();
        $this->activityRepository->update($request->username(), 0);
        return Response::create();
    }
}
