<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CurrentUser;
use Register\Logic\Util;
use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\Password;
use Register\Infra\Response;
use Register\Infra\Request;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;

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

    /** @var CurrentUser */
    private $currentUser;

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
        CurrentUser $currentUser,
        Password $password
    ) {
        $this->conf = $conf;
        $this->text = $text;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->logger = $logger;
        $this->currentUser = $currentUser;
        $this->password = $password;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->conf["remember_user"] && $request->cookie("register_remember") !== null
                && !$this->currentUser->get()) {
            return $this->loginAction($request);
        }
        if (!$this->currentUser->get() && $request->function() === "registerlogin") {
            return $this->loginAction($request);
        }
        if ($this->currentUser->get() && $request->function() === "registerlogout") {
            return $this->logoutAction($request);
        }
        if ($this->currentUser->invalid()) {
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
            if ($this->conf['remember_user'] && isset($_POST['remember'])) {
                $response = $response->withCookie(
                    "register_remember",
                    $entry->getUsername() . "." . Util::hmac($entry->getUsername(), $entry->secret()),
                    $request->time() + (100 * 24 * 60 * 60)
                );
            }
            $this->currentUser->login($entry);

            $this->logger->logInfo('login', "$username logged in");

            // go to login page if exists or to default page otherwise
            $group = $this->userGroupRepository->findByGroupname($entry->getAccessgroups()[0]);
            if ($group && $group->getLoginpage()) {
                $url = $request->url()->withEncodedPage($group->getLoginpage());
            } else {
                $url = $request->url()->withPage($this->text['loggedin']);
            }
            return $response->redirect($url->absolute());
        } else {
            if ($request->cookie("register_remember")) {
                $response->withCookie("register_remember", "", 0);
            }
            $this->logger->logError('login', "$username wrong password");
            return $response->redirect($request->url()->withPage($this->text['login_error'])->absolute());
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
            $this->userRepository->update($user->withPassword($this->password->hash($password)));
        }
        return true;
    }

    private function logoutAction(Request $request): Response
    {
        $user = $this->currentUser->get();
        assert($user !== null);
        $this->currentUser->logout();
        $this->logger->logInfo('logout', "{$user->getUsername()} logged out");
        return (new Response)->redirect($request->url()->withPage($this->text['loggedout'])->absolute());
    }

    private function forcedLogout(Request $request): Response
    {
        $this->currentUser->logout();
        return (new Response)->redirect($request->url()->withPage($this->text['loggedout'])->absolute());
    }
}
