<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\UserRepository;
use Register\Infra\View;

class ActivateUser
{
    /** @var array<string,string> */
    private $conf;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, UserRepository $userRepository, View $view)
    {
        $this->conf = $conf;
        $this->userRepository = $userRepository;
        $this->view = $view;
    }

    public function __invoke(): string
    {
        if (isset($_GET['username']) && isset($_GET['nonce'])) {
            return $this->activateUser($_GET['username'], $_GET['nonce']);
        }
        return "";
    }

    private function activateUser(string $username, string $nonce): string
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
        $user = $user->activate()->withAccessgroups([$this->conf['group_activated']]);
        $this->userRepository->update($user);
        return $this->view->message('success', 'activated');
    }
}
