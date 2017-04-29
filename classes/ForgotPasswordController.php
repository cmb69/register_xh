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
    public function forgotPassword()
    {
        global $su;

        // In case user is logged in, no password forgotten page is shown
        if (Register_isLoggedIn()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }

        $ERROR = '';
        $o = '<p>' . $this->lang['reminderexplanation'] . '</p>'."\n";

        // Get form data if available
        $action    = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
        $email     = htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : "");

        // Form Handling
        if (isset($_POST['action']) && $action == "forgotten_password") {
            if ($email == '') {
                $ERROR .= '<li>' . $this->lang['err_email'] . '</li>'."\n";
            } elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
                $ERROR .= '<li>' . $this->lang['err_email_invalid'] . '</li>'."\n";
            }

            // read user file in CSV format separated by colons
            (new DbService(Register_dataFolder()))->lock(LOCK_SH);
            $userArray = (new DbService(Register_dataFolder()))->readUsers();
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);

            // search user for email
            $user = registerSearchUserArray($userArray, 'email', $email);
            if (!$user) {
                $ERROR .= '<li>' . $this->lang['err_email_does_not_exist'] . '</li>'."\n";
            }

            $password = $user['password'];

            if ($ERROR != '') {
                $o .= '<span class="regi_error">' . $this->lang['error'] . '</span>'."\n"
                    . '<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
            } else {
                // prepare email content for user data email
                $content = $this->lang['emailtext1'] . "\n\n"
                    . ' ' . $this->lang['name'] . ": " . $user['name'] . "\n"
                    . ' ' . $this->lang['username'] . ": " . $user['username'] . "\n";
                if (!$this->config['encrypt_password']) {
                    $content .= ' ' . $this->lang['password'] . ": " . $password . "\n";
                }
                $content .= ' ' . $this->lang['email'] . ": " . $user['email'] . "\n";
                if ($this->config['encrypt_password']) {
                    $content .= "\n" . $this->lang['emailtext3'] ."\n\n"
                        . CMSIMPLE_URL . '?' . $su . '&'
                        . 'action=registerResetPassword&username=' . urlencode($user['username']) . '&captcha='
                        . urlencode($user['password']);
                }

                // send reminder email
                (new MailService)->sendMail(
                    $email,
                    $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                    $content,
                    array('From: ' . $this->config['senderemail'])
                );
                $o .= '<b>' . $this->lang['remindersent'] . '</b>';
                return $o;
            }
        } elseif (isset($_GET['action']) && $action == 'registerResetPassword'
                && $this->config['encrypt_password']) {
            // read user file in CSV format separated by colons
            (new DbService(Register_dataFolder()))->lock(LOCK_EX);
            $userArray = (new DbService(Register_dataFolder()))->readUsers();

            // search user for email
            $user = registerSearchUserArray($userArray, 'username', $_GET['username']);
            if (!$user) {
                $ERROR .= '<li>' . $this->lang['err_username_does_not_exist'] . '</li>'."\n";
            }

            if ($user['password'] != stsl($_GET['captcha'])) {
                $ERROR .= '<li>' . $this->lang['err_status_invalid'] . '</li>';
            }

            // in case of encrypted password a new random password will be generated
            // and its value be written back to the CSV file
            if ($ERROR == '') {
                $password = generateRandomCode(8);
                $user['password'] = $this->hasher->hashPassword($password);
                $userArray = registerReplaceUserEntry($userArray, $user);
                if (!(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                    $ERROR .= '<li>' . $this->lang['err_cannot_write_csv']
                        . ' (' . Register_dataFolder() . 'users.csv' . ')'
                        . '</li>'."\n";
                }
            }
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);

            if ($ERROR != '') {
                $o .= '<span class="regi_error">' . $this->lang['error'] . '</span>'."\n"
                    . '<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
            } else {
                // prepare email content for user data email
                $content = $this->lang['emailtext1'] . "\n\n"
                    . ' ' . $this->lang['name'] . ": " . $user['name'] . "\n"
                    . ' ' . $this->lang['username'] . ": " . $user['username'] . "\n"
                    . ' ' . $this->lang['password'] . ": " . $password . "\n"
                    . ' ' . $this->lang['email'] . ": " . $user['email'] . "\n";

                // send reminder email
                (new MailService)->sendMail(
                    $user['email'],
                    $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                    $content,
                    array('From: ' . $this->config['senderemail'])
                );
                $o .= '<b>' . $this->lang['remindersent'] . '</b>';
                return $o;
            }
        }

        // Form Creation
        $o .= $this->forgotForm($email);
        return $o;
    }

    private function forgotForm($email)
    {
        $view = new View('forgotten-form');
        $view->actionUrl = sv('REQUEST_URI');
        $view->email = $email;
        return (string) $view;
    }
}
