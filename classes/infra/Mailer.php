<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Mailer
{
    /** @var bool */
    private $fixMailHeaders;

    public function __construct(bool $fixMailHeaders)
    {
        $this->fixMailHeaders = $fixMailHeaders;
    }

    /** @param list<string> $headers */
    public function sendMail(string $to, string $encodedSubject, string $message, array $headers): bool
    {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
        $sep = $this->fixMailHeaders ? "\n" : "\r\n";
        return $this->mail(
            $to,
            (string) preg_replace('/\R/', $sep, $encodedSubject),
            (string) preg_replace('/\R/u', $sep, $message),
            implode($sep, $headers)
        );
    }

    /** @codeCoverageIgnore */
    protected function mail(string $to, string $subject, string $message, string $headers): bool
    {
        return mail($to, $subject, $message, $headers);
    }
}
