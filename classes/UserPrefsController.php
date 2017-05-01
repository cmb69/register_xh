<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH_CSRFProtection;

class UserPrefsController extends Controller
{
    /**
     * @var XH_CSRFProtection
     */
    private $csrfProtector;

    public function __construct()
    {
        global $pth;

        parent::__construct();
        if (!class_exists('\\XH_CSRFProtection')) {
            include_once $pth['folder']['classes'] . 'CSRFProtection.php';
        }
        $this->csrfProtector = new XH_CSRFProtection('register_csrf_token', false);
    }

    public function defaultAction()
    {
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();

        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            echo XH_message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
        } elseif ($entry->status == "locked") {
            echo XH_message('fail', $this->lang['user_locked'] . ':' .$username);
        } else {
            $this->prepareForm($entry->name, $entry->email)->render();
        }
    }

    public function editAction()
    {
        $this->csrfProtector->check();
        $errors = [];
        // Get form data if available
        $oldpassword  = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : '';
        $name      = isset($_POST['name']) ? $_POST['name'] : '';
        $password1 = isset($_POST['password1']) ? $_POST['password1'] : '';
        $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';
        $email     = isset($_POST['email']) ? $_POST['email'] : '';

        // set user name from session
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : "";

        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();

        // search user in CSV data
        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            echo XH_message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
            return;
        }

        // Test if user is locked
        if ($entry->status == "locked") {
            echo XH_message('fail', $this->lang['user_locked'] . ':' .$username);
            return;
        }

        // check that old password got entered correctly
        if (!$this->hasher->checkPassword($oldpassword, $entry->password)) {
            $errors[] = $this->lang['err_old_password_wrong'];
        }

        if ($password1 == "" && $password2 == "") {
            $password1 = $oldpassword;
            $password2 = $oldpassword;
        }
        if ($email == "") {
            $email = $entry->email;
        }
        if ($name == "") {
            $name = $entry->name;
        }

        $errors = array_merge($errors, registerCheckEntry($name, $username, $password1, $password2, $email));

        // check for colons in fields
        $errors = array_merge($errors, registerCheckColons($name, $username, $password1, $email));
        $oldemail = $entry->email;

        // read user entry, update it and write it back to CSV file
        if (empty($errors)) {
            $entry->password = $this->hasher->hashPassword($password1);
            $entry->email    = $email;
            $entry->name     = $name;
            $userArray = registerReplaceUserEntry($userArray, $entry);

            // write CSV file if no errors occurred so far
            if (!(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                $errors[] = $this->lang['err_cannot_write_csv'] .' (' . Register_dataFolder() . 'users.csv' . ')';
            }
        }
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);

        if (!empty($errors)) {
            $view = new View('error');
            $view->errors = $errors;
            $view->render();
            $this->prepareForm($name, $email)->render();
        } else {
            // prepare email for user information about updates
            $content = $this->lang['emailprefsupdated'] . "\n\n" .
                ' ' . $this->lang['name'] . ': '.$name."\n" .
                ' ' . $this->lang['username'] . ': '.$username."\n" .
                //' ' . $this->lang['password'] . ': '.$password1."\n" .
                ' ' . $this->lang['email'] . ': '.$email."\n" .
                ' ' . $this->lang['fromip'] . ': '. $_SERVER['REMOTE_ADDR'] ."\n";

            // send update email
            (new MailService)->sendMail(
                $email,
                $this->lang['prefsemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array(
                    'From: ' . $this->config['senderemail'],
                    'Cc: '  . $oldemail . ', ' . $this->config['senderemail']
                )
            );
            echo XH_message('success', $this->lang['prefsupdated']);
        }
    }

    public function deleteAction()
    {
        $this->csrfProtector->check();
        $errors = [];
    
        // Get form data if available
        $oldpassword  = isset($_POST['oldpassword']) ? $_POST['oldpassword'] : '';
        $name      = isset($_POST['name']) ? $_POST['name'] : '';
        $email     = isset($_POST['email']) ? $_POST['email'] : '';

        // set user name from session
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : "";

        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();

        // search user in CSV data
        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            echo XH_message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
            return;
        }

        // Test if user is locked
        if ($entry->status == "locked") {
            echo XH_message('fail', $this->lang['user_locked'] . ':' .$username);
            return;
        }

        // Form Handling - Delete User ================================================
        if (!$this->hasher->checkPassword($oldpassword, $entry->password)) {
            $errors[] = $this->lang['err_old_password_wrong'];
        }

        // read user entry, update it and write it back to CSV file
        if (empty($errors)) {
            $userArray = registerDeleteUserEntry($userArray, $username);
            if (!(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                $errors[] = $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'users.csv' . ')';
            }
        }
        // write CSV file if no errors occurred so far
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);

        if (!empty($errors)) {
            $view = new View('error');
            $view->errors = $errors;
            $view->render();
            $this->prepareForm($name, $email)->render();
        } else {
            $rememberPeriod = 24*60*60*100;

            $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

            session_regenerate_id(true);

            unset($_SESSION['username'], $_SESSION['register_sn']);

            // clear cookies
            if (isset($_COOKIE['username'], $_COOKIE['password'])) {
                setcookie("username", "", time() - $rememberPeriod, "/");
                setcookie("password", "", time() - $rememberPeriod, "/");
            }

            XH_logMessage('info', 'register', 'logout', "$username deleted and logged out");

            echo XH_message('success', $this->lang['user_deleted'] . ': '.$username);
        }
    }

    /**
     * @param string $name
     * @param string $email
     * @return View
     */
    private function prepareForm($name, $email)
    {
        $csrfTokenInput = $this->csrfProtector->tokenInput();
        $this->csrfProtector->store();
        $view = new View('userprefs-form');
        $view->csrfTokenInput = new HtmlString($csrfTokenInput);
        $view->actionUrl = sv('REQUEST_URI');
        $view->name = $name;
        $view->email = $email;
        return $view;
    }
}
