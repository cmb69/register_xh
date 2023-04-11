<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

/** @codeCoverageIgnore */
class CsrfProtector
{
    public function token(): string
    {
        if (isset($_SESSION["register_token"])) {
            return $_SESSION["register_token"];
        }
        $token = base64_encode(random_bytes(15));
        $_SESSION["register_token"] = $token;
        return $token;
    }

    public function check(): bool
    {
        return isset($_SESSION["register_token"], $_POST["register_token"])
            && hash_equals($_SESSION["register_token"], $_POST["register_token"]);
    }
}
