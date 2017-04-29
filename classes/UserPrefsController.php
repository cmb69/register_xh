<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class UserPrefsController extends Controller
{
    public function registerUserPrefs()
    {
        $ERROR = '';
        $o = '';
    
        if(!Register_isLoggedIn()) {
            return $this->lang['access_error_text'];
        }

        // Get form data if available
        $action    = isset($_POST['action']) ? $_POST['action'] : "";
        $oldpassword  = htmlspecialchars(isset($_POST['oldpassword']) ? $_POST['oldpassword'] : "");
        $name      = htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : "");
        $password1 = htmlspecialchars(isset($_POST['password1']) ? $_POST['password1'] : "");
        $password2 = htmlspecialchars(isset($_POST['password2']) ? $_POST['password2'] : "");
        $email     = htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : "");
        $REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

        // set user name from session
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : "";

        // read user file in CSV format separated by colons
        (new DbService(Register_dataFolder()))->lock(LOCK_EX);
        $userArray = (new DbService(Register_dataFolder()))->readUsers();

        // search user in CSV data
        $entry = registerSearchUserArray($userArray, 'username', $username);
        if ($entry === false) {
            die($this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
        }

        // Test if user is locked
        if ($entry['status'] == "locked") {
            $o .= '<span class=regi_error>' . $this->lang['user_locked'] . ':' .$username.'</span>'."\n";
            return $o;
        }

        // Form Handling - Change User ================================================
        if ($username!="" && isset($_POST['submit']) && $action == "edit_user_prefs") {
            // check that old password got entered correctly
            if (!$this->config['encrypt_password'] && $oldpassword != $entry['password']) {
                $ERROR .= '<li>' . $this->lang['err_old_password_wrong'] . '</li>'."\n";
            } elseif ($this->config['encrypt_password']
                && !$this->hasher->checkPassword($oldpassword, $entry['password'])
            ) {
                $ERROR .= '<li>' . $this->lang['err_old_password_wrong'] . '</li>'."\n";
            }

            if ($password1 == "" && $password2 == "") {
                $password1 = $oldpassword;
                $password2 = $oldpassword;
            }
            if ($email == "") {
                $email = $entry['email'];
            }
            if ($name == "") {
                $name = $entry['name'];
            }

            $ERROR .= registerCheckEntry($name, $username, $password1, $password2, $email);
    
            // check for colons in fields
            $ERROR .= registerCheckColons($name, $username, $password1, $email);
            $oldemail = $entry['email'];

            // read user entry, update it and write it back to CSV file
            if ($ERROR == '') {
                if ($this->config['encrypt_password']) {
                    $entry['password'] = $this->hasher->hashPassword($password1);
                } else {
                    $entry['password'] = $password1;
                }
                $entry['email']    = $email;
                $entry['name']     = $name;
                $userArray = registerReplaceUserEntry($userArray, $entry);
    
                // write CSV file if no errors occurred so far
                if (!(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                    $ERROR .= '<li>' . $this->lang['err_cannot_write_csv'] .
                        ' (' . Register_dataFolder() . 'users.csv' . ')' .
                        '</li>'."\n";
                }
            }
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);

            if($ERROR != '') {
                $o .= '<span class="regi_error">' . $this->lang['error'] . '</span>'."\n" .
                    '<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
            } else {
                // update session variables
                $_SESSION['email'] = $email;
                $_SESSION['fullname'] = $name;

                // prepare email for user information about updates
                $content = $this->lang['emailprefsupdated'] . "\n\n" .
                    ' ' . $this->lang['name'] . ': '.$name."\n" .
                    ' ' . $this->lang['username'] . ': '.$username."\n" .
                    //' ' . $this->lang['password'] . ': '.$password1."\n" .
                    ' ' . $this->lang['email'] . ': '.$email."\n" .
                    ' ' . $this->lang['fromip'] . ': '.$REMOTE_ADDR."\n";
    
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
                $o .= '<b>' . $this->lang['prefsupdated'] . '</b>';
                return $o;
            }
        } elseif ($username!='' && isset($_POST['delete']) && $action == "edit_user_prefs") {
            // Form Handling - Delete User ================================================
            // check that old password got entered correctly
            if (!$this->config['encrypt_password'] && $oldpassword != $entry['password']) {
                $ERROR .= '<li>' . $this->lang['err_old_password_wrong'] . '</li>'."\n";
            } elseif ($this->config['encrypt_password']
                && !$this->hasher->checkPassword($oldpassword, $entry['password'])
            ) {
                $ERROR .= '<li>' . $this->lang['err_old_password_wrong'] . '</li>'."\n";
            }

            // read user entry, update it and write it back to CSV file
            if ($ERROR == '') {
                $userArray = registerDeleteUserEntry($userArray, $username);
                if (!(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                    $ERROR .= '<li>' . $this->lang['err_cannot_write_csv'] .
                        ' (' . Register_dataFolder() . 'users.csv' . ')' .
                        '</li>'."\n";
                }
            }
            // write CSV file if no errors occurred so far
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);

            if ($ERROR != '') {
                $o .= '<span class="regi_error">' . $this->lang['error'] . '</span>'."\n" .
                    '<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
            } else {
                $rememberPeriod = 24*60*60*100;

                $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

                // clear all session variables
                //$_SESSION = array();

                // end session
                unset($_SESSION['username']);
                unset($_SESSION['fullname']);
                unset($_SESSION['email']);
                unset($_SESSION['accessgroups']);
                unset($_SESSION['sessionnr']);
                unset($_SESSION['register_sn']);

                // clear cookies
                if (isset($_COOKIE['username'], $_COOKIE['password'])) {
                    setcookie("username", "", time() - $rememberPeriod, "/");
                    setcookie("password", "", time() - $rememberPeriod, "/");
                }

                XH_logMessage('info', 'register', 'logout', "$username deleted and logged out");

                $o .= '<b>' . $this->lang['user_deleted'] . ': '.$username.'</b>'."\n";
                return $o;
            }
        } else {
            $email = $entry['email'];
            $name  = $entry['name'];
        }

        // Form Creation
        $o .= $this->userPrefsForm($name, $email);
        return $o;
    }

    private function userPrefsForm($name, $email)
    {
        $view = new View('userprefs-form');
        $view->actionUrl = sv('REQUEST_URI');
        $view->name = $name;
        $view->email = $email;
        return (string) $view;
    }
}
