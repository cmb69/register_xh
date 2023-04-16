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

    /** @var array<string,string> */
    private $text;

    /** @param array<string,string> $text */
    public function __construct(bool $fixMailHeaders, array $text)
    {
        $this->$fixMailHeaders = $fixMailHeaders;
        $this->text = $text;
    }

    public function notifyUpdate(
        User $user,
        string $oldemail,
        string $from,
        string $serverName,
        string $remoteAddress
    ): bool {
        $content = <<<MAIL
{$this->text["emailprefsupdated"]}

 {$this->text["name"]}: {$user->getName()}
 {$this->text["username"]}: {$user->getUsername()}
 {$this->text["email"]}: {$user->getEmail()}
 {$this->text["fromip"]}: {$remoteAddress}

MAIL;
        return $this->sendMail(
            $user->getEmail(),
            sprintf($this->text['prefsemailsubject'], $serverName),
            $content,
            ["From: $from", "Cc: $oldemail, $from"]
        );
    }

    public function notifyPasswordForgotten(User $user, string $from, string $url, string $serverName): bool
    {
        $content = <<<MAIL
{$this->text["emailtext1"]}

 {$this->text["name"]}: {$user->getName()}
 {$this->text["username"]}: {$user->getUsername()}
 {$this->text["email"]}: {$user->getEmail()}

{$this->text["emailtext3"]}

<{$url}>
MAIL;
        return $this->sendMail(
            $user->getEmail(),
            sprintf($this->text['reminderemailsubject'], $serverName),
            $content,
            ["From: $from"]
        );
    }

    public function notifyPasswordReset(User $user, string $from, string $serverName): bool
    {
        $content = <<<MAIL
{$this->text["emailtext1"]}

 {$this->text["name"]}: {$user->getName()}
 {$this->text["username"]}: {$user->getUsername()}
 {$this->text["email"]}: {$user->getEmail()}

MAIL;
        return $this->sendMail(
            $user->getEmail(),
            sprintf($this->text['reminderemailsubject'], $serverName),
            $content,
            ["From: $from"]
        );
    }

    public function notifyActivation(
        User $user,
        string $from,
        string $url,
        string $key,
        string $serverName,
        string $remoteAddress
    ): bool {
        $content = <<<MAIL
{$this->text["emailtext1"]}

 {$this->text['name']}: {$user->getName()}
 {$this->text['username']}: {$user->getUsername()}
 {$this->text['email']}: {$user->getEmail()}
 {$this->text['fromip']}: {$remoteAddress}

{$this->text[$key]}

<{$url}>
MAIL;
        return $this->sendMail(
            $user->getEmail(),
            sprintf($this->text['emailsubject'], $serverName),
            $content,
            ["From: $from", "Cc: $from"]
        );
    }

    public function adHocMail(User $user, Mail $mail, string $from): bool
    {
        return $this->sendMail($user->getEmail(), $mail->subject(), $mail->message(), ["From: $from"]);
    }

    /** @param list<string> $headers */
    private function sendMail(string $to, string $subject, string $message, array $headers): bool
    {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
        $sep = $this->fixMailHeaders ? "\n" : "\r\n";
        return $this->mail(
            $to,
            '=?UTF-8?Q?' . quoted_printable_encode($subject) . '?=',
            $message,
            implode($sep, $headers)
        );
    }

    /** @codeCoverageIgnore */
    protected function mail(string $to, string $subject, string $message, string $headers): bool
    {
        return mail($to, $subject, $message, $headers);
    }
}
