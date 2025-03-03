<?php

/**
 * Copyright (c) 2025 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\Mailer;
use Register\PHPMailer\PHPMailer;

class MailerTest extends TestCase
{
    public function testStartTl(): void
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $conf["mail_smtp"] = "true";
        $conf["smtp_host"] = "localhost";
        $conf["smtp_port"] = "25";
        $conf["smtp_tls"] = "STARTTLS";
        $phpMailer = $this->getMockBuilder(PHPMailer::class)->onlyMethods(["send"])->getMock();
        $mailer = new Mailer($conf, $phpMailer);
        $phpMailer->expects($this->any())->method("send")->willReturn(true);
        $mailer->sendMail("to@example.com", "test", "irrelevant message", "from@example.com");
        $this->assertEquals("smtp", $phpMailer->Mailer);
        $this->assertEquals($conf["smtp_host"], $phpMailer->Host);
        $this->assertEquals($conf["smtp_port"], $phpMailer->Port);
        $this->assertEquals(PHPMailer::ENCRYPTION_STARTTLS, $phpMailer->SMTPSecure);
        $this->assertFalse($phpMailer->SMTPAuth);
    }

    public function testSmtpsWithAuth(): void
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $conf["mail_smtp"] = "true";
        $conf["smtp_host"] = "localhost";
        $conf["smtp_port"] = "25";
        $conf["smtp_tls"] = "SMTPS";
        $conf["smtp_username"] = "user";
        $conf["smtp_password"] = "pass";
        $phpMailer = $this->getMockBuilder(PHPMailer::class)->onlyMethods(["send"])->getMock();
        $mailer = new Mailer($conf, $phpMailer);
        $phpMailer->expects($this->any())->method("send")->willReturn(true);
        $mailer->sendMail("to@example.com", "test", "irrelevant message", "from@example.com");
        $this->assertEquals("smtp", $phpMailer->Mailer);
        $this->assertEquals($conf["smtp_host"], $phpMailer->Host);
        $this->assertEquals($conf["smtp_port"], $phpMailer->Port);
        $this->assertEquals(PHPMailer::ENCRYPTION_SMTPS, $phpMailer->SMTPSecure);
        $this->assertTrue($phpMailer->SMTPAuth);
        $this->assertEquals($conf["smtp_username"], $phpMailer->Username);
        $this->assertEquals($conf["smtp_password"], $phpMailer->Password);
    }
}
