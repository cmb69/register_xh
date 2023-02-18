<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\User;

class CurrentUser
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function get(): ?User
    {
        /**
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         */
        global $pth;
        static $user = null;

        $session = new Session();
        if (!$user) {
            // it would be nice if XH had an API to get the session name without starting a session
            $sessionfile = $pth['folder']['cmsimple'] . '.sessionname';
            if (is_file($sessionfile) && isset($_COOKIE[file_get_contents($sessionfile)])) {
                $session->start();
            }
            if (isset($_SESSION['username'])) {
                $rec = $this->userRepository->findByUsername($_SESSION['username']);
                if ($rec) {
                    $user = $rec;
                } else {
                    (new LoginManager(time(), $session, $this->userRepository))->logout();
                    $user = null;
                }
            } else {
                $user = null;
            }
        }
        return $user;
    }
}
