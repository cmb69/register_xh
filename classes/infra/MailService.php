<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class MailService
{
    /**
     * @var array<string,string>
     */
    private $config;

    public function __construct()
    {
        /**
         * @var array<string,array<string,string>> $plugin_cf
         */
        global $plugin_cf;

        $this->config = $plugin_cf['register'];
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string[] $headers
     * @return bool
     */
    public function sendMail($to, $subject, $message, array $headers)
    {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/plain; charset=UTF-8';
        $sep = $this->config['fix_mail_headers'] ? "\n" : "\r\n";
        return mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, implode($sep, $headers));
    }
}