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
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
    }

    /**
     * @return void
     */
    public function loginAction()
    {
        $rememberPeriod = 24*60*60*100;

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
        // check password and set session variables
        if ($this->isUserAuthenticated($entry, $password, $passwordHash)) {
            // set cookies if requested by user
            if ($this->config['remember_user'] && isset($_POST['remember'])) {
                setcookie('register_username', $username, time() + $rememberPeriod, CMSIMPLE_ROOT);
                setcookie('register_password', $entry->getPassword(), time() + $rememberPeriod, CMSIMPLE_ROOT);
            }

            XH_startSession();
            session_regenerate_id(true);

            $_SESSION['username']     = $entry->getUsername();

            XH_logMessage('info', 'register', 'login', "$username logged in");

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
            // clear cookies
            if (isset($_COOKIE['register_username'], $_COOKIE['register_password'])) {
                setcookie('register_username', '', 0, CMSIMPLE_ROOT);
                setcookie('register_password', '', 0, CMSIMPLE_ROOT);
            }

            XH_logMessage('error', 'register', 'login', "$username wrong password");

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
        Register_logout();
        XH_logMessage('info', 'register', 'logout', "$username logged out");
    
        $logoutTitle = uenc($this->lang['loggedout']);
        header('Location: ' . CMSIMPLE_URL . '?' . $logoutTitle);
        exit;
    }
}
