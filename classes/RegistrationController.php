<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class RegistrationController
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
     * @var ValidationService
     */
    private $validationService;

    /**
     * @var View
     */
    private $view;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
        ValidationService $validationService,
        View $view,
        UserRepository $userRepository,
        MailService $mailService
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->validationService = $validationService;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
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

        $name      = isset($_POST['name']) && is_string($_POST["name"]) ? trim($_POST['name']) : '';
        $username  = isset($_POST['username']) && is_string($_POST["username"]) ? trim($_POST['username']) : '';
        $password1 = isset($_POST['password1']) && is_string($_POST["password1"]) ? trim($_POST['password1']) : '';
        $password2 = isset($_POST['password2']) && is_string($_POST["password2"]) ? trim($_POST['password2']) : '';
        $email     = isset($_POST['email']) && is_string($_POST["email"]) ? trim($_POST['email']) : '';

        $errors = $this->validationService->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            echo $this->view->render('error', ['errors' => $errors]);
            echo $this->form($name, $username, $password1, $password2, $email);
            return;
        }

        if ($this->userRepository->findByUsername($username)) {
            $this->view->message("fail", $this->lang['err_username_exists']);
            return;
        }
        $user = $this->userRepository->findByEmail($email);

        // generate a nonce for the user activation
        $status = bin2hex(random_bytes(16));
        if (!$user) {
            $newUser = new User(
                $username,
                password_hash($password1, PASSWORD_DEFAULT),
                array($this->config['group_default']),
                $name,
                $email,
                $status
            );

            if (!$this->userRepository->add($newUser)) {
                $this->view->message("fail", $this->lang['err_cannot_write_csv']);
                return;
            }
        }

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
        $this->mailService->sendMail(
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

    /**
     * @return void
     */
    public function activateUserAction()
    {
        if (isset($_GET['username']) && isset($_GET['nonce'])) {
            $this->activateUser($_GET['username'], $_GET['nonce']);
        }
    }

    /**
     * @return void
     */
    private function activateUser(string $user, string $nonce)
    {
        $entry = $this->userRepository->findByUsername($user);
        if ($entry === null) {
            echo $this->view->message("fail", $this->lang['err_username_notfound'] . $user);
            return;
        }
        if ($entry->getStatus() == "") {
            echo $this->view->message("fail", $this->lang['err_status_empty']);
            return;
        }
        if ($nonce != $entry->getStatus()) {
            echo $this->view->message("fail", $this->lang['err_status_invalid']);
            return;
        }

        $entry->activate();
        $entry->setAccessgroups(array($this->config['group_activated']));
        $this->userRepository->update($entry);
        echo $this->view->message('success', $this->lang['activated']);
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
