<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class LoginController extends Controller
{
    public function loginAction()
    {
        $rememberPeriod = 24*60*60*100;

        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // set username and password in case cookies are set
        if ($this->config['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_password'])) {
            $username     = $_COOKIE['register_username'];
            $passwordHash = $_COOKIE['register_password'];
        }
    
        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_SH);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);

        // search user in CSV data
        $entry = registerSearchUserArray($userArray, 'username', $username);

        // check password and set session variables
        if ($entry && $entry->username == $username
                && ($entry->status == 'activated' || $entry->status == 'locked')
                && (!isset($passwordHash) || $passwordHash == $entry->password)
                && (isset($passwordHash)
                || (password_verify($password, $entry->password)))) {
            // set cookies if requested by user
            if ($this->config['remember_user'] && isset($_POST['remember'])) {
                setcookie('register_username', $username, time() + $rememberPeriod, CMSIMPLE_ROOT);
                setcookie('register_password', $entry->password, time() + $rememberPeriod, CMSIMPLE_ROOT);
            }

            XH_startSession();
            session_regenerate_id(true);

            $_SESSION['username']     = $entry->username;
            $_SESSION['register_sn']  = Register_sessionName();

            XH_logMessage('info', 'register', 'login', "$username logged in");

            // go to login page if exists or to default page otherwise
            if ($glp = Register_groupLoginPage($entry->accessgroups[0])) {
                $loginPage = '?' . $glp;
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

    public function logoutAction()
    {
        XH_startSession();
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
        Register_logout();
        XH_logMessage('info', 'register', 'logout', "$username logged out");
    
        $logoutTitle = uenc($this->lang['loggedout']);
        header('Location: ' . CMSIMPLE_URL . '?' . $logoutTitle);
        exit;
    }
}
