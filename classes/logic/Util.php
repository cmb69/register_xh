<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

class Util
{
    public static function base64url(string $string): string
    {
        return rtrim(strtr(base64_encode($string), "+/", "-_"), "=");
    }

    public static function hmac(string $data, string $key): string
    {
        return self::base64url(hash_hmac("sha1", $data, $key, true));
    }
}
