<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection;

class UserPrefsController
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
     * @var CSRFProtection
     */
    private $csrfProtector;

    /**
     * @var View
     */
    private $view;

    /**
     * @var DbService
     */
    private $dbService;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(array $config, array $lang, View $view, DbService $dbService, MailService $mailService)
    {
        $this->config = $config;
        $this->lang = $lang;
        XH_startSession();
        $this->csrfProtector = new CSRFProtection('register_csrf_token', false);
        $this->view = $view;
        $this->dbService = $dbService;
        $this->mailService = $mailService;
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        $username = $_SESSION['username'] ?? '';

        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();
        $this->dbService->lock(LOCK_UN);

        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            echo $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
        } elseif ($entry->status == "locked") {
            echo $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
        } else {
            echo $this->renderForm($entry->name, $entry->email);
        }
    }

    /**
     * @return void
     */
    public function editAction()
    {
        $this->csrfProtector->check();

        // Get form data if available
        $oldpassword  = isset($_POST['oldpassword']) && is_string($_POST["oldpassword"])
            ? trim($_POST['oldpassword'])
            : '';
        $name      = isset($_POST['name']) && is_string($_POST["name"]) ? trim($_POST['name']) : '';
        $password1 = isset($_POST['password1']) && is_string($_POST["password1"]) ? trim($_POST['password1']) : '';
        $password2 = isset($_POST['password2']) && is_string($_POST["password2"]) ? trim($_POST['password2']) : '';
        $email     = isset($_POST['email']) && is_string($_POST["email"]) ? trim($_POST['email']) : '';

        // set user name from session
        $username = $_SESSION['username'] ?? "";

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();

        // search user in CSV data
        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            echo $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
            $this->dbService->lock(LOCK_UN);
            return;
        }

        // Test if user is locked
        if ($entry->status == "locked") {
            echo $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
            $this->dbService->lock(LOCK_UN);
            return;
        }

        // check that old password got entered correctly
        if (!password_verify($oldpassword, $entry->password)) {
            echo $this->view->message("fail", $this->lang['err_old_password_wrong']);
            echo $this->renderForm($name, $email);
            $this->dbService->lock(LOCK_UN);
            return;
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

        $validationService = new ValidationService($this->lang);
        $errors = $validationService->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            echo $this->view->render('error', ['errors' => $errors]);
            echo $this->renderForm($name, $email);
            $this->dbService->lock(LOCK_UN);
            return;
        }

        $oldemail = $entry->email;

        // read user entry, update it and write it back to CSV file
        $entry->password = password_hash($password1, PASSWORD_DEFAULT);
        $entry->email    = $email;
        $entry->name     = $name;
        $userArray = registerReplaceUserEntry($userArray, $entry);

        // write CSV file if no errors occurred so far
        if (!$this->dbService->writeUsers($userArray)) {
            echo $this->view->message("fail", $this->lang['err_cannot_write_csv']);
            $this->dbService->lock(LOCK_UN);
            return;
        }
        $this->dbService->lock(LOCK_UN);

        // prepare email for user information about updates
        $content = $this->lang['emailprefsupdated'] . "\n\n" .
            ' ' . $this->lang['name'] . ': '.$name."\n" .
            ' ' . $this->lang['username'] . ': '.$username."\n" .
            //' ' . $this->lang['password'] . ': '.$password1."\n" .
            ' ' . $this->lang['email'] . ': '.$email."\n" .
            ' ' . $this->lang['fromip'] . ': '. $_SERVER['REMOTE_ADDR'] ."\n";

        // send update email
        $this->mailService->sendMail(
            $email,
            $this->lang['prefsemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
            $content,
            array(
                'From: ' . $this->config['senderemail'],
                'Cc: '  . $oldemail . ', ' . $this->config['senderemail']
            )
        );
        echo $this->view->message('success', $this->lang['prefsupdated']);
    }

    /**
     * @return void
     */
    public function deleteAction()
    {
        $this->csrfProtector->check();
    
        // Get form data if available
        $oldpassword = $_POST['oldpassword'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        // set user name from session
        $username = $_SESSION['username'] ?? "";

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();

        // search user in CSV data
        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            echo $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
            $this->dbService->lock(LOCK_UN);
            return;
        }

        // Test if user is locked
        if ($entry->status == "locked") {
            echo $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
            $this->dbService->lock(LOCK_UN);
            return;
        }

        // Form Handling - Delete User ================================================
        if (!password_verify($oldpassword, $entry->password)) {
            echo $this->view->message("fail", $this->lang['err_old_password_wrong']);
            echo $this->renderForm($name, $email);
            $this->dbService->lock(LOCK_UN);
            return;
        }

        // read user entry, update it and write it back to CSV file
        $userArray = registerDeleteUserEntry($userArray, $username);
        if (!$this->dbService->writeUsers($userArray)) {
            echo $this->view->message("fail", $this->lang['err_cannot_write_csv']);
            $this->dbService->lock(LOCK_UN);
            return;
        }
        // write CSV file if no errors occurred so far
        $this->dbService->lock(LOCK_UN);

        $username = $_SESSION['username'] ?? '';
        Register_logout();
        XH_logMessage('info', 'register', 'logout', "$username deleted and logged out");
        echo $this->view->message('success', $this->lang['user_deleted'] . ': '.$username);
    }

    /**
     * @param string $name
     * @param string $email
     */
    private function renderForm($name, $email): string
    {
        /**
         * @var string $sn
         * @var string $su
         */
        global $sn, $su;

        $csrfTokenInput = $this->csrfProtector->tokenInput();
        $this->csrfProtector->store();
        return $this->view->render('userprefs-form', [
            'csrfTokenInput' => new HtmlString($csrfTokenInput),
            'actionUrl' => "$sn?$su",
            'name' => $name,
            'email' => $email,
        ]);
    }
}
