<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\PHPMailer\PHPMailer;

class Mailer
{
    /** @var array<string,string> */
    private $conf;

    /** @var PHPMailer */
    private $mail;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, PHPMailer $mail)
    {
        $this->conf = $conf;
        $this->mail = $mail;
    }

    public function sendMail(
        string $to,
        string $subject,
        string $message,
        string $from,
        ?string $cc = null
    ): bool {
        if ($this->conf["mail_smtp"]) {
            $this->mail->isSMTP();
            $this->mail->Host = $this->conf["smtp_host"];
            $this->mail->Port = (int) $this->conf["smtp_port"];
            if ($this->conf["smtp_tls"] === "STARTTLS") {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->conf["smtp_tls"] === "SMTPS") {
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            if ($this->conf["smtp_username"]) {
                $this->mail->SMTPAuth = true;
                $this->mail->Username = $this->conf["smtp_username"];
                $this->mail->Password = $this->conf["smtp_password"];
            }
        } else {
            $this->mail->isMail();
        }
        $this->mail->CharSet = PHPMAILER::CHARSET_UTF8;
        $this->mail->setFrom($from);
        $this->mail->addAddress($to);
        if ($cc !== null) {
            foreach (explode(", ", $cc) as $address) {
                $this->mail->addCC($address);
            }
        }
        $this->mail->isHTML(false);
        $this->mail->Subject = $subject;
        $this->mail->Body = $message;
        return $this->mail->send();
    }
}
