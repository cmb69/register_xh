<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class FakeMailer extends Mailer
{
    private $lastMail;
    private $options;

    protected function mail(string $to, string $subject, string $message, string $headers): bool
    {
        $this->lastMail = func_get_args();
        return $this->options["sendMail"] ?? true;
    }

    public function lastMail(): array
    {
        return $this->lastMail;
    }

    public function options(array $options)
    {
        $this->options = $options;
    }
}
