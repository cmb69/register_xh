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
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Response;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;

class LoginController
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var UserRepository */
    private $userRepository;

    /** @var UserGroupRepository */
    private $userGroupRepository;

    /** @var LoginManager */
    private $loginManager;

    /** @var Logger */
    private $logger;

    /** @var Session */
    private $session;

    /** @var CurrentUser */
    private $currentUser;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository,
        LoginManager $loginManager,
        Logger $logger,
        Session $session,
        CurrentUser $currentUser
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
        $this->session = $session;
        $this->currentUser = $currentUser;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->config["remember_user"]
            && isset($_COOKIE['register_username'], $_COOKIE['register_token']) && !$this->currentUser->get()) {
            return $this->loginAction($request);
        }
        if (!$this->currentUser->get() && $request->function() === "registerlogin") {
            return $this->loginAction($request);
        }
        if ($this->currentUser->get() && $request->function() === "registerlogout") {
            return $this->logoutAction($request);
        }
        return new Response();
    }

    private function loginAction(Request $request): Response
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $token = null;

        // set username and password in case cookies are set
        if ($this->config['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_token'])) {
            $username     = $_COOKIE['register_username'];
            $token = $_COOKIE['register_token'];
        }

        $entry = $this->userRepository->findByUsername($username);
        if ($this->loginManager->isUserAuthenticated($entry, $password, $token)) {
            assert($entry instanceof User);
            $this->loginManager->login($entry, $this->config['remember_user'] && isset($_POST['remember']));
            $this->logger->logInfo('login', "$username logged in");

            // go to login page if exists or to default page otherwise
            $group = $this->userGroupRepository->findByGroupname($entry->getAccessgroups()[0]);
            if ($group && $group->getLoginpage()) {
                $url = $request->url()->withEncodedPage($group->getLoginpage());
            } else {
                $url = $request->url()->withPage($this->lang['loggedin']);
            }
            return (new Response)->redirect($url->absolute());
        } else {
            $this->loginManager->forget();
            $this->logger->logError('login', "$username wrong password");
            return (new Response)->redirect($request->url()->withPage($this->lang['login_error'])->absolute());
        }
    }

    private function logoutAction(Request $request): Response
    {
        $this->session->start();
        $username = $_SESSION['username'] ?? '';
        $this->loginManager->logout();
        $this->logger->logInfo('logout', "$username logged out");
        return (new Response)->redirect($request->url()->withPage($this->lang['loggedout'])->absolute());
    }
}
