<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Error;

class FakePassword extends Password
{
    public function hash(string $password): string
    {
        switch ($password) {
            default:
                throw new Error("Password '$password' is not configured!");
            case "12345":
                return "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW";
            case "a":
                return "\$2y\$04\$itNcBtvDec7HeYY1GGjQ9OQMEP.F7HjAjwY7j1YfAy8fdIQ8El.uC";
            case "admin":
                return "\$2y\$04\$mnuEbmCivb6LilNbTmUAbO1P68J0fxXWeONpTvDP2RR5N2ENRLm0S";
            case "test":
                return "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6";
        }
    }
}
