<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\Mail;
use Register\Value\User;

class Mailer
{
    /** @var bool */
    private $fixMailHeaders;

    public function __construct(bool $fixMailHeaders)
    {
        $this->$fixMailHeaders = $fixMailHeaders;
    }

    public function adHocMail(User $user, Mail $mail, string $from): bool
    {
        return $this->sendMail($user->getEmail(), $mail->subject(), $mail->message(), ["From: $from"]);
    }

    /** @param list<string> $headers */
    public function sendMail(string $to, string $subject, string $message, array $headers): bool
    {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
        $sep = $this->fixMailHeaders ? "\n" : "\r\n";
        return $this->mail(
            $to,
            '=?UTF-8?Q?' . quoted_printable_encode($subject) . '?=',
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
