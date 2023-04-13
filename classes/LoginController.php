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
use Register\Logic\Util;
use Register\Value\Response;
use Register\Value\User;

class LoginController
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var UserRepository */
    private $userRepository;

    /** @var UserGroupRepository */
    private $userGroupRepository;

    /** @var Logger */
    private $logger;

    /** @var LoginManager */
    private $loginManager;

    /** @var Password */
    private $password;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(
        array $conf,
        array $text,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository,
        Logger $logger,
        LoginManager $loginManager,
        Password $password
    ) {
        $this->conf = $conf;
        $this->text = $text;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->logger = $logger;
        $this->loginManager = $loginManager;
        $this->password = $password;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->conf["remember_user"] && $request->cookie("register_remember") !== null
                && !$request->username()) {
            return $this->loginAction($request);
        }
        if (!$request->username() && $request->function() === "registerlogin") {
            return $this->loginAction($request);
        }
        if ($request->username() && $request->function() === "registerlogout") {
            return $this->logoutAction($request);
        }
        if ($request->username() && !$this->userRepository->findByUsername($request->username())) {
            return $this->forcedLogout($request);
        }
        return new Response();
    }

    private function loginAction(Request $request): Response
    {
        $response = new Response();
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $token = null;

        // set username and password in case cookies are set
        if ($this->conf['remember_user'] && $request->cookie("register_remember") !== null) {
            [$username, $token] = explode(".", $request->cookie("register_remember"));
        }

        $entry = $this->userRepository->findByUsername($username);
        if ($this->isUserAuthenticated($entry, $password, $token)) {
            assert($entry instanceof User);
            $this->loginManager->login($entry);

            $this->logger->logInfo('login', "$username logged in");

            // go to login page if exists or to default page otherwise
            $group = $this->userGroupRepository->findByGroupname($entry->getAccessgroups()[0]);
            if ($group && $group->getLoginpage()) {
                $url = $request->url()->withEncodedPage($group->getLoginpage());
            } else {
                $url = $request->url()->withPage($this->text['loggedin']);
            }
            $response = Response::redirect($url->absolute());
            if ($this->conf['remember_user'] && isset($_POST['remember'])) {
                $response = $response->withCookie(
                    "register_remember",
                    $entry->getUsername() . "." . Util::hmac($entry->getUsername(), $entry->secret()),
                    $request->time() + (100 * 24 * 60 * 60)
                );
            }
            return $response;
        } else {
            $this->logger->logError('login', "$username wrong password");
            $response = Response::redirect($request->url()->withPage($this->text['login_error'])->absolute());
            if ($request->cookie("register_remember")) {
                $response = $response->withCookie("register_remember", "", 0);
            }
            return $response;
        }
    }

    public function isUserAuthenticated(?User $user, string $password, ?string $token): bool
    {
        if (!$user) {
            return false;
        }
        if (!$user->isActivated() && !$user->isLocked()) {
            return false;
        }
        if ($token !== null) {
            return hash_equals(Util::hmac($user->getUsername(), $user->secret()), $token);
        }
        if (!$this->password->verify($password, $user->getPassword())) {
            return false;
        }
        if ($this->password->needsRehash($user->getPassword())) {
            $this->userRepository->save($user->withPassword($this->password->hash($password)));
        }
        return true;
    }

    private function logoutAction(Request $request): Response
    {
        $this->loginManager->logout();
        $this->logger->logInfo('logout', "{$request->username()} logged out");
        return Response::redirect($request->url()->withPage($this->text['loggedout'])->absolute());
    }

    private function forcedLogout(Request $request): Response
    {
        $this->loginManager->logout();
        return Response::redirect($request->url()->withPage($this->text['loggedout'])->absolute());
    }
}
