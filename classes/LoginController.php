<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\RedirectResponse;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;

class LoginController
{
    /**
     * @var array<string,string>
     */
    private $config;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserGroupRepository
     */
    private $userGroupRepository;

    /**
     * @var LoginManager
     */
    private $loginManager;

    /**
     * @var Logger
     */
    private $logger;

    /** @var Session */
    private $session;

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
        Session $session
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
        $this->session = $session;
    }

    public function loginAction(Request $request): RedirectResponse
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
            return new RedirectResponse($url->absolute());
        } else {
            $this->loginManager->forget();
            $this->logger->logError('login', "$username wrong password");
            return new RedirectResponse($request->url()->withPage($this->lang['login_error'])->absolute());
        }
    }

    public function logoutAction(Request $request): RedirectResponse
    {
        $this->session->start();
        $username = $_SESSION['username'] ?? '';
        $this->loginManager->logout();
        $this->logger->logInfo('logout', "$username logged out");
        return new RedirectResponse($request->url()->withPage($this->lang['loggedout'])->absolute());
    }
}
