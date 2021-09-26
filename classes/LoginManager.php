<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class LoginManager
{
    const REMEMBER_PERIOD = 100 * 24 * 60 * 60;

    /**
     * @return void
     */
    public function login(User $user, bool $remember)
    {
        if ($remember) {
            setcookie('register_username', $user->getUsername(), time() + self::REMEMBER_PERIOD, CMSIMPLE_ROOT);
            setcookie('register_password', $user->getPassword(), time() + self::REMEMBER_PERIOD, CMSIMPLE_ROOT);
        }

        XH_startSession();
        session_regenerate_id(true);
        $_SESSION['username'] = $user->getUsername();
    }

    /**
     * @return void
     */
    public function logout()
    {
        XH_startSession();
        session_regenerate_id(true);
        unset($_SESSION['username']);
        $this->forget();
    }

    /**
     * @return void
     */
    public function forget()
    {
        if (isset($_COOKIE['register_username'], $_COOKIE['register_password'])) {
            setcookie('register_username', '', 0, CMSIMPLE_ROOT);
            setcookie('register_password', '', 0, CMSIMPLE_ROOT);
        }
    }
}
