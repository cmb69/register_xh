<?php

/**
 * Copyright 2017-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

spl_autoload_register(function ($class) {
    $parts = explode('\\', $class, 2);
    if ($parts[0] == 'Register') {
        include_once __DIR__ . '/' . $parts[1] . '.php';
    }
});
