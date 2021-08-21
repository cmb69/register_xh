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
        global $su;

        $errors = [];
        $name      = isset($_POST['name']) ? $_POST['name'] : '';
        $username  = isset($_POST['username']) ? $_POST['username'] : '';
        $password1 = isset($_POST['password1']) ? $_POST['password1'] : '';
        $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';
        $email     = isset($_POST['email']) ? $_POST['email'] : '';

        $errors = array_merge(
            $errors,
            registerCheckEntry($name, $username, $password1, $password2, $email)
        );

        // check for colons in fields
        $errors = array_merge($errors, registerCheckColons($name, $username, $password1, $email));

        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();

        // check if user or other user for same email address exists
        if (registerSearchUserArray($userArray, 'username', $username) !== false) {
            $errors[] = $this->lang['err_username_exists'];
        }
        if (registerSearchUserArray($userArray, 'email', $email) !== false) {
            $errors[] = $this->lang['err_email_exists'];
        }

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
        if (empty($errors) && !(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
            $errors[] = $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'users.csv' . ')';
        }
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);

        if (!empty($errors)) {
            $view = new View('error');
            $view->setData(['errors' => $errors]);
            $view->render();
            echo $this->form($name, $username, $password1, $password2, $email);
        } else {
            // prepare email content for registration activation
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": $name \n"
                . ' ' . $this->lang['username'] . ": $username \n"
                . ' ' . $this->lang['email'] . ": $email \n"
                . ' ' . $this->lang['fromip'] . ": {$_SERVER['REMOTE_ADDR']} \n\n"
                . $this->lang['emailtext2'] . "\n\n"
                . '<' . CMSIMPLE_URL . '?' . $su . '&'
                . 'action=register_activate_user&username='.$username.'&nonce='
                . $status . '>';

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
            echo XH_message('success', $this->lang['registered']);
        }
    }

    /**
     * @return void
     */
    public function activateUserAction()
    {
        // Get form data if available
        $name      = isset($_POST['name']) ? $_POST['name'] : '';
        $username  = isset($_POST['username']) ? $_POST['username'] : '';
        $password1 = isset($_POST['password1']) ? $_POST['password1'] : '';
        $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';
        $email     = isset($_POST['email']) ? $_POST['email'] : '';

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
        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();
    
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
            $view = new View('error');
            $view->setData(['errors' => $errors]);
            $o .= $view;
        } else {
            $entry->status = "activated";
            $entry->accessgroups = array($this->config['group_activated']);
            $userArray = registerReplaceUserEntry($userArray, $entry);
            (new DbService(Register_dataFolder()))->writeUsers($userArray);
            $o .= XH_message('success', $this->lang['activated']);
        }
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);
        return $o;
    }

    private function form(string $name, string $username, string $password1, string $password2, string $email): string
    {
        $view = new View('registerform');
        $view->setData([
            'actionUrl' => sv('REQUEST_URI'),
            'name' => $name,
            'username' => $username,
            'password1' => $password1,
            'password2' => $password2,
            'email' => $email,
        ]);
        return (string) $view;
    }
}
