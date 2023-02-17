<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Logic\Validator;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\UserRepository;
use Register\Infra\View;

class HandleUserRegistration
{
    /** @var CurrentUser */
    private $currentUser;

    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var MailService */
    private $mailService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        CurrentUser $currentUser,
        array $config,
        array $lang,
        View $view,
        UserRepository $userRepository,
        MailService $mailService
    ) {
        $this->currentUser = $currentUser;
        $this->config = $config;
        $this->lang = $lang;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->currentUser->get()) {
            return (new Response)->redirect(CMSIMPLE_URL);
        }
        if (isset($_POST['action']) && $_POST['action'] === 'register_user') {
            return $this->registerUser($request);
        }
        if (isset($_GET['action']) && $_GET['action'] === 'register_activate_user') {
            return $this->activateUser();
        }
        return $this->showForm($request);
    }

    private function showForm(Request $request): Response
    {
        $response = new Response;
        $response->body($this->view->render('registerform', [
            'actionUrl' => $request->url()->relative(),
            'name' => "",
            'username' => "",
            'password1' => "",
            'password2' => "",
            'email' => "",
        ]));
        return $response;
    }

    private function registerUser(Request $request): Response
    {
        $response = new Response;
        $name      = isset($_POST['name']) && is_string($_POST["name"]) ? trim($_POST['name']) : '';
        $username  = isset($_POST['username']) && is_string($_POST["username"]) ? trim($_POST['username']) : '';
        $password1 = isset($_POST['password1']) && is_string($_POST["password1"]) ? trim($_POST['password1']) : '';
        $password2 = isset($_POST['password2']) && is_string($_POST["password2"]) ? trim($_POST['password2']) : '';
        $email     = isset($_POST['email']) && is_string($_POST["email"]) ? trim($_POST['email']) : '';

        $validator = new Validator();
        $errors = $validator->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            $response->body($this->renderErrorMessages($errors)
                . $this->view->render('registerform', [
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'username' => $username,
                    'password1' => $password1,
                    'password2' => $password2,
                    'email' => $email,
                ]));
            return $response;
        }

        if ($this->userRepository->findByUsername($username)) {
            return $response->body($this->view->message("fail", 'err_username_exists'));
        }
        $user = $this->userRepository->findByEmail($email);

        // generate a nonce for the user activation
        $status = bin2hex(random_bytes(16));
        if (!$user) {
            $newUser = new User(
                $username,
                (string) password_hash($password1, PASSWORD_DEFAULT),
                array($this->config['group_default']),
                $name,
                $email,
                $status
            );

            if (!$this->userRepository->add($newUser)) {
                return $response->body($this->view->message("fail", 'err_cannot_write_csv'));
            }
        }

        // prepare email content for registration activation
        $content = $this->lang['emailtext1'] . "\n\n"
            . ' ' . $this->lang['name'] . ": $name \n"
            . ' ' . $this->lang['username'] . ": $username \n"
            . ' ' . $this->lang['email'] . ": $email \n"
            . ' ' . $this->lang['fromip'] . ": {$_SERVER['REMOTE_ADDR']} \n\n";
        if (!$user) {
            $url = $request->url()->withParams([
                "action" => "register_activate_user",
                "username" => $username,
                "nonce" => $status,
            ]);
                $content .= $this->lang['emailtext2'] . "\n\n"
                . '<' . $url->absolute() . '>';
        } else {
            $url = $request->url()->withPage($this->lang['forgot_password']);
            $content .= $this->lang['emailtext4'] . "\n\n"
                . '<' . $url->absolute() . '>';
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
        return $response->body($this->view->message('success', 'registered'));
    }

    /** @param list<array{string}> $errors */
    private function renderErrorMessages(array $errors): string
    {
        return implode("", array_map(function ($args) {
            return $this->view->message("fail", ...$args);
        }, $errors));
    }

    private function activateUser(): Response
    {
        $response = new Response;
        if (isset($_GET['username']) && isset($_GET['nonce'])) {
            return $response->body($this->doActivateUser($_GET['username'], $_GET['nonce']));
        }
        return $response->body("");
    }

    private function doActivateUser(string $username, string $nonce): string
    {
        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            return $this->view->message("fail", 'err_username_notfound', $username);
        }
        if ($user->getStatus() == "") {
            return $this->view->message("fail", 'err_status_empty');
        }
        if ($nonce != $user->getStatus()) {
            return $this->view->message("fail", 'err_status_invalid');
        }
        $user = $user->activate()->withAccessgroups([$this->config['group_activated']]);
        $this->userRepository->update($user);
        return $this->view->message('success', 'activated');
    }
}
