<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class ForgotPasswordController extends Controller
{
    public function defaultAction()
    {
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $this->prepareForgotForm($email)->render();
    }

    public function passwordForgottenAction()
    {
        global $su;

        $errors = [];

        $email = isset($_POST['email']) ? $_POST['email'] : '';

        if ($email == '') {
            $errors[] = $this->lang['err_email'];
        } elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            $errors[] = $this->lang['err_email_invalid'];
        }

        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_SH);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);

        // search user for email
        if (!$user = registerSearchUserArray($userArray, 'email', $email)) {
            $errors[] = $this->lang['err_email_does_not_exist'];
        }

        if (!empty($errors)) {
            $view = new View('error');
            $view->errors = $errors;
            $view->render();
            $this->prepareForgotForm($email)->render();
        } else {
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->name . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->username . "\n";
            $content .= ' ' . $this->lang['email'] . ": " . $user->email . "\n";
            $content .= "\n" . $this->lang['emailtext3'] ."\n\n"
                . '<' . CMSIMPLE_URL . '?' . $su . '&'
                . 'action=registerResetPassword&username=' . urlencode($user->username) . '&nonce='
                . urlencode($user->password) . '>';

            // send reminder email
            (new MailService)->sendMail(
                $email,
                $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array('From: ' . $this->config['senderemail'])
            );
            echo XH_message('success', $this->lang['remindersent']);
        }
    }

    public function resetPasswordAction()
    {
        $errors = [];

        $email = isset($_POST['email']) ? $_POST['email'] : '';

        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();

        // search user for email
        $user = registerSearchUserArray($userArray, 'username', $_GET['username']);
        if (!$user) {
            $errors[] = $this->lang['err_username_does_not_exist'];
        }

        if ($user->password != $_GET['nonce']) {
            $errors[] = $this->lang['err_status_invalid'];
        }

        // in case of encrypted password a new random password will be generated
        // and its value be written back to the CSV file
        if (empty($errors)) {
            $password = base64_encode($this->hasher->get_random_bytes(8));
            $user->password = $this->hasher->hashPassword($password);
            $userArray = registerReplaceUserEntry($userArray, $user);
            if (!(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                $errors[] = $this->lang['err_cannot_write_csv']
                    . ' (' . Register_dataFolder() . 'users.csv' . ')';
            }
        }
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);

        if (!empty($errors)) {
            $view = new View('error');
            $view->errors = $errors;
            $view->render();
            $this->prepareForgotForm($email)->render();
        } else {
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->name . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->username . "\n"
                . ' ' . $this->lang['password'] . ": " . $password . "\n"
                . ' ' . $this->lang['email'] . ": " . $user->email . "\n";

            // send reminder email
            (new MailService)->sendMail(
                $user->email,
                $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array('From: ' . $this->config['senderemail'])
            );
            echo XH_message('success', $this->lang['remindersent']);
        }
    }

    /**
     * @param string $email
     * @return View
     */
    private function prepareForgotForm($email)
    {
        $view = new View('forgotten-form');
        $view->actionUrl = sv('REQUEST_URI');
        $view->email = $email;
        return $view;
    }
}
