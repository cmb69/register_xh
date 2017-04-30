<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class RegistrationController extends Controller
{
    public function register()
    {
        global $su;

        // In case user is logged in, no registration page is shown
        if (Register_isLoggedIn()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }

        $errors = [];
        $o = '';

        // Get form data if available
        $action    = isset($_POST['action']) ? $_POST['action'] : "";
        $name      = XH_hsc(isset($_POST['name']) ? $_POST['name'] : "");
        $username  = XH_hsc(isset($_POST['username']) ? $_POST['username'] : "");
        $password1 = XH_hsc(isset($_POST['password1']) ? $_POST['password1'] : "");
        $password2 = XH_hsc(isset($_POST['password2']) ? $_POST['password2'] : "");
        $email     = XH_hsc(isset($_POST['email']) ? $_POST['email'] : "");
        $captcha   = isset($_POST['captcha']) ? $_POST['captcha'] : "";
        $register_validate  = isset($_POST['register_validate']) ? $_POST['register_validate'] : "";
        $REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

        // Form Handling
        if (isset($_POST['action']) && $action == "register_user") {
            $errors = array_merge(
                $errors,
                registerCheckEntry($name, $username, $password1, $password2, $email)
            );
            if ($this->config['captcha_mode'] != "none") {
                if ($this->config['captcha_mode'] == "image") {
                    $code = md5_decrypt($captcha, $this->config['captcha_crypt']);
                } elseif ($this->config['captcha_mode'] == "formula") {
                    $formula = md5_decrypt($captcha, $this->config['captcha_crypt']);
                    $addends = explode('+', $formula);
                    $addends = array_filter($addends, function ($x) {
                        return is_numeric(trim($x));
                    });
                    $code = array_sum($addends);
                }

                if ($register_validate == '' || strtolower($register_validate) != $code) {
                    $errors[] = $this->lang['err_validation'];
                }
            }

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

            // generate another captcha code for the user activation email
            $status = generateRandomCode((int)$this->config['captcha_chars']);
            if ($this->config['encrypt_password']) {
                $userArray = registerAddUser(
                    $userArray,
                    $username,
                    $this->hasher->hashPassword($password1),
                    array($this->config['group_default']),
                    $name,
                    $email,
                    $status
                );
            } else {
                $userArray = registerAddUser(
                    $userArray,
                    $username,
                    $password1,
                    array($this->config['group_default']),
                    $name,
                    $email,
                    $status
                );
            }

            // write CSV file if no errors occurred so far
            if (empty($errors) && !(new DbService(Register_dataFolder()))->writeUsers($userArray)) {
                $errors[] = $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'users.csv' . ')';
            }
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);

            if (!empty($errors)) {
                $view = new View('error');
                $view->errors = $errors;
                $o .= $view;
            } else {
                // prepare email content for registration activation
                $content = $this->lang['emailtext1'] . "\n\n"
                    . ' ' . $this->lang['name'] . ": $name \n"
                    . ' ' . $this->lang['username'] . ": $username \n"
                    . ' ' . $this->lang['email'] . ": $email \n"
                    . ' ' . $this->lang['fromip'] . ": $REMOTE_ADDR \n\n"
                    . $this->lang['emailtext2'] . "\n\n"
                    . CMSIMPLE_URL . '?' . $su . '&'
                    . 'action=registerActivateUser&username='.$username.'&captcha='
                    . md5_encrypt($status, $this->config['captcha_crypt']);

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
                $o .= '<b>' . $this->lang['registered'] . '</b>';
                return $o;
            }
        } elseif (isset($_GET['action']) && $_GET['action'] == 'registerActivateUser'
            && isset($_GET['username']) && isset($_GET['captcha'])
        ) {
            $o .= $this->activateUser($_GET['username'], $_GET['captcha']);
            return $o;
        }

        // Form Creation
        if ($captcha == '' || md5_decrypt($captcha, $this->config['captcha_crypt']) == '') {
            if ($this->config['captcha_mode'] == "image") {
                $code = generateRandomCode((int)$this->config['captcha_chars']);
            } elseif ($this->config['captcha_mode'] == "formula") {
                $code = generateCaptchaFormula((int)$this->config['captcha_chars']);
            } else {
                $code = '';
            }
        } else {
            $code = md5_decrypt($captcha, $this->config['captcha_crypt']);
        }
        $o .= $this->form($code, $name, $username, $password1, $password2, $email);
        return $o;
    }

    private function activateUser($user, $captcha)
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
            if (!isset($entry['status']) || $entry['status'] == "") {
                $errors[] = $this->lang['err_status_empty'];
            }
            $status = md5_decrypt($captcha, $this->config['captcha_crypt']);
            if ($status != $entry['status']) {
                $errors[] = $this->lang['err_status_invalid']
                    . "($status&ne;" . $entry['status'] . ')';
            }
        }

        if (!empty($errors)) {
            $view = new View('error');
            $view->errors = $errors;
            $o .= $view;
        } else {
            $entry['status'] = "activated";
            $entry['accessgroups'] = array($this->config['group_activated']);
            $userArray = registerReplaceUserEntry($userArray, $entry);
            (new DbService(Register_dataFolder()))->writeUsers($userArray);
            $o .= '<b>' . $this->lang['activated'] . '</b>'."\n";
        }
        (new DbService(Register_dataFolder()))->lock(LOCK_UN);
        return $o;
    }

    private function form($code, $name, $username, $password1, $password2, $email)
    {
        $view = new View('registerform');
        $view->actionUrl = sv('REQUEST_URI');
        $view->captcha = md5_encrypt($code, $this->config['captcha_crypt']);
        $view->name = $name;
        $view->username = $username;
        $view->password1 = $password1;
        $view->password2 = $password2;
        $view->email = $email;
        $hasCaptcha =  $this->config['captcha_mode'] != "none";
        $view->hasCaptcha = $hasCaptcha;
        if ($hasCaptcha) {
            $view->captchaHtml = new HtmlString(
                getCaptchaHtml(
                    "register_captcha",
                    $code,
                    (int) $this->config['captcha_image_width'],
                    (int) $this->config['captcha_image_height'],
                    $this->config['captcha_crypt'],
                    $this->config['captcha_mode']
                )
            );
        }
        return (string) $view;
    }
}
