<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

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
        Logger $logger
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function loginAction()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordHash = null;

        // set username and password in case cookies are set
        if ($this->config['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_password'])) {
            $username     = $_COOKIE['register_username'];
            $passwordHash = $_COOKIE['register_password'];
        }

        $entry = $this->userRepository->findByUsername($username);
        if ($this->isUserAuthenticated($entry, $password, $passwordHash)) {
            $this->loginManager->login($entry, $this->config['remember_user'] && isset($_POST['remember']));
            $this->logger->logInfo('login', "$username logged in");

            // go to login page if exists or to default page otherwise
            $group = $this->userGroupRepository->findByGroupname($entry->getAccessgroups()[0]);
            if ($group && $group->getLoginpage()) {
                $loginPage = '?' . $group->getLoginpage();
            } else {
                $loginPage = '?'. uenc($this->lang['loggedin']);
            }
            header('Location: ' . CMSIMPLE_URL . $loginPage);
            exit;
        } else {
            $this->loginManager->forget();
            $this->logger->logError('login', "$username wrong password");

            // go to login error page
            $errorTitle = uenc($this->lang['login_error']);
            header('Location: ' . CMSIMPLE_URL . '?' . $errorTitle);
            exit;
        }
    }

    /**
     * @param User|null $user
     * @param string|null $passwordHash
     */
    private function isUserAuthenticated($user, string $password, $passwordHash): bool
    {
        return $user
            && ($user->isActivated() || $user->isLocked())
            && (!isset($passwordHash) || $passwordHash == $user->getPassword())
            && (isset($passwordHash) || (password_verify($password, $user->getPassword())));
    }

    /**
     * @return void
     */
    public function logoutAction()
    {
        XH_startSession();
        $username = $_SESSION['username'] ?? '';
        $this->loginManager->logout();
        $this->logger->logInfo('logout', "$username logged out");
    
        $logoutTitle = uenc($this->lang['loggedout']);
        header('Location: ' . CMSIMPLE_URL . '?' . $logoutTitle);
        exit;
    }
}
