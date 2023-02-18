<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class FakeMailer extends Mailer
{
    /** @var string */
    private $to;

    /** @var string */
    private $subject;

    /** @var string */
    private $message;

    /** @var list<string> */
    private $headers;

    /** @param list<string> $headers */
    protected function sendMail(string $to, string $subject, string $message, array $headers): bool
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->headers = $headers;
        return true;
    }

    public function to(): string
    {
        return $this->to;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function message(): string
    {
        return $this->message;
    }

    /** @return list<array> */
    public function headers(): array
    {
        return $this->headers;
    }
}
