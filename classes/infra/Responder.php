<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\Response;

class Responder
{
    /** @return string|never */
    public static function respond(Response $response)
    {
        global $hjs, $title;

        if ($response->location() !== null) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Location: ' . $response->location());
            foreach ($response->cookies() as [$name, $value, $expires]) {
                setcookie($name, $value, $expires, CMSIMPLE_ROOT);
            }
            echo $response->output();
            exit;
        }
        if ($response->isForbidden()) {
            header("HTTP/1.1 403 Forbidden");
        }
        foreach ($response->meta() as $key => $data) {
            $key = XH_hsc($key);
            $content = (string) json_encode($data, JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hjs .= "\n<meta name=\"$key\" content='$content'>";
        }
        if ($response->script() !== null) {
            $filename = XH_hsc($response->script());
            $hjs .= "\n<script src=\"$filename\"></script>";
        }
        if ($response->title() !== null) {
            $title = XH_hsc($response->title());
        }
        foreach ($response->cookies() as [$name, $value, $expires]) {
            setcookie($name, $value, $expires, CMSIMPLE_ROOT);
        }
        return $response->output();
    }
}
