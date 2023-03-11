<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\User;

/** @codeCoverageIgnore */
class LoginManager
{
    /** @return void */
    public function login(User $user)
    {
        XH_startSession();
        session_regenerate_id();
        $_SESSION["username"] = $user->getUsername();
    }

    /** @return void */
    public function logout()
    {
        XH_startSession();
        session_regenerate_id();
        unset($_SESSION["username"]);
    }
}
