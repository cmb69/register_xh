<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;

use Register\Value\HtmlString;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowUserPreferences
{
    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @param array<string,string> $lang */
    public function __construct(
        array $lang,
        Session $session,
        CsrfProtector $csrfProtector,
        UserRepository $userRepository,
        View $view
    ) {
        $this->lang = $lang;
        $session->start();
        $this->csrfProtector = $csrfProtector;
        $this->userRepository = $userRepository;
        $this->view = $view;
    }

    public function __invoke(Request $request): string
    {
        $username = $_SESSION['username'] ?? '';

        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            return $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
        } elseif ($user->isLocked()) {
            return $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
        } else {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->render('userprefs-form', [
                'csrfTokenInput' => new HtmlString($csrfTokenInput),
                'actionUrl' => $request->url()->relative(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]);
        }
    }
}
