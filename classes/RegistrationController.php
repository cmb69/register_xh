<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class RegistrationController extends Controller
{
    /**
     * @var View
     */
    private $view;

    /**
     * @var DbService
     */
    private $dbService;

    public function __construct(View $view, DbService $dbService)
    {
        parent::__construct();
        $this->view = $view;
        $this->dbService = $dbService;
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        echo $this->form('', '', '', '', '');
    }

    /**
     * @return void
     */
    public function registerUserAction()
    {
        /**
         * @var string $su
         */
        global $su;

        // $errors = [];
        $name      = isset($_POST['name']) && is_string($_POST["name"]) ? trim($_POST['name']) : '';
        $username  = isset($_POST['username']) && is_string($_POST["username"]) ? trim($_POST['username']) : '';
        $password1 = isset($_POST['password1']) && is_string($_POST["password1"]) ? trim($_POST['password1']) : '';
        $password2 = isset($_POST['password2']) && is_string($_POST["password2"]) ? trim($_POST['password2']) : '';
        $email     = isset($_POST['email']) && is_string($_POST["email"]) ? trim($_POST['email']) : '';

        $validationService = new ValidationService($this->lang);
        $errors = $validationService->validateUser($name, $username, $password1, $password2, $email);

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();

        // check if user or other user for same email address exists
        if (registerSearchUserArray($userArray, 'username', $username) !== false) {
            $errors[] = $this->lang['err_username_exists'];
        }
        $user = registerSearchUserArray($userArray, 'email', $email);

        // generate a nonce for the user activation
        $status = bin2hex(random_bytes(16));
        $userArray = registerAddUser(
            $userArray,
            $username,
            password_hash($password1, PASSWORD_DEFAULT),
            array($this->config['group_default']),
            $name,
            $email,
            $status
        );

        // write CSV file if no errors occurred so far
        if (empty($errors) && !$user && !$this->dbService->writeUsers($userArray)) {
            $errors[] = $this->lang['err_cannot_write_csv'];
        }
        $this->dbService->lock(LOCK_UN);

        if (!empty($errors)) {
            echo $this->view->render('error', ['errors' => $errors]);
            echo $this->form($name, $username, $password1, $password2, $email);
        } else {
            // prepare email content for registration activation
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": $name \n"
                . ' ' . $this->lang['username'] . ": $username \n"
                . ' ' . $this->lang['email'] . ": $email \n"
                . ' ' . $this->lang['fromip'] . ": {$_SERVER['REMOTE_ADDR']} \n\n";
            if (!$user) {
                $content .= $this->lang['emailtext2'] . "\n\n"
                    . '<' . CMSIMPLE_URL . '?' . $su . '&'
                    . 'action=register_activate_user&username='.$username.'&nonce='
                    . $status . '>';
            } else {
                $content .= $this->lang['emailtext4'] . "\n\n"
                    . '<' . CMSIMPLE_URL . '?' . uenc($this->lang['forgot_password']) . '>';
            }

            // send activation email
            (new MailService)->sendMail(
                $email,
                $this->lang['emailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array(
                    'From: ' . $this->config['senderemail'],
                    'Cc: '  . $this->config['senderemail']
                )
            );
            echo $this->view->message('success', $this->lang['registered']);
        }
    }

    /**
     * @return void
     */
    public function activateUserAction()
    {
        // Get form data if available
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';
        $password1 = $_POST['password1'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $email = $_POST['email'] ?? '';

        if (isset($_GET['username']) && isset($_GET['nonce'])) {
            echo $this->activateUser($_GET['username'], $_GET['nonce']);
        } else {
            echo $this->form($name, $username, $password1, $password2, $email);
        }
    }

    private function activateUser(string $user, string $nonce): string
    {
        $errors = [];
        $o ='';

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();
    
        // check if user or other user for same email address exists
        $entry = registerSearchUserArray($userArray, 'username', $user);
        if ($entry === false) {
            $errors[] = $this->lang['err_username_notfound'] . $user;
        } else {
            if (!isset($entry->status) || $entry->status == "") {
                $errors[] = $this->lang['err_status_empty'];
            }
            if ($nonce != $entry->status) {
                $errors[] = $this->lang['err_status_invalid'];
            }
        }

        if (!empty($errors)) {
            $o .= $this->view->render('error', ['errors' => $errors]);
        } else {
            $entry->status = "activated";
            $entry->accessgroups = array($this->config['group_activated']);
            $userArray = registerReplaceUserEntry($userArray, $entry);
            $this->dbService->writeUsers($userArray);
            $o .= $this->view->message('success', $this->lang['activated']);
        }
        $this->dbService->lock(LOCK_UN);
        return $o;
    }

    private function form(string $name, string $username, string $password1, string $password2, string $email): string
    {
        /**
         * @var string $sn
         * @var string $su
         */
        global $sn, $su;

        return $this->view->render('registerform', [
            'actionUrl' => "$sn?$su",
            'name' => $name,
            'username' => $username,
            'password1' => $password1,
            'password2' => $password2,
            'email' => $email,
        ]);
    }
}
