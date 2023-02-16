<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;

use Register\Value\HtmlString;
use Register\Value\User;
use Register\Value\UserGroup;
use Register\Logic\AdminProcessor;
use Register\Infra\DbService;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\Url;
use Register\Infra\View;

class UserAdminController
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var DbService */
    private $dbService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        string $pluginFolder,
        array $config,
        array $lang,
        CsrfProtector $csrfProtector,
        DbService $dbService
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->config = $config;
        $this->lang = $lang;
        $this->csrfProtector = $csrfProtector;
        $this->view = new View($this->pluginFolder, $this->lang);
        $this->dbService = $dbService;
    }

    public function editUsersAction(Request $request): Response
    {
        $response = new Response();
        $fn = $this->dbService->dataFolder() . 'users.csv';
        if ($this->dbService->hasUsersFile()) {
            $lock = $this->dbService->lock(false);
            $users  = $this->dbService->readUsers();
            $this->dbService->unlock($lock);
            $o = $this->renderUsersForm($users, $request->url(), $response)
                . $this->view->messagep('info', count($users), 'entries_in_csv', $fn);
        } else {
            $o = $this->view->message('fail', 'err_csv_missing', $fn);
        }
        return $response->body($o);
    }

    public function saveUsersAction(Request $request): Response
    {
        $response = new Response();
        $this->csrfProtector->check();
        $errors = [];
        if ($this->dbService->hasGroupsFile()) {
            $groups = $this->dbService->readGroups();
        } else {
            $groups = [];
            $errors[] = ['err_csv_missing', $this->dbService->dataFolder() . 'groups.csv'];
        }

        $username = $_POST['username'] ?? [];
        $password = $_POST['password'] ?? [];
        $oldpassword = $_POST['oldpassword'] ?? [];
        $name = $_POST['name'] ?? [];
        $email = $_POST['email'] ?? [];
        $groupString = $_POST['accessgroups'] ?? [];
        $status = $_POST['status'] ?? [];

        $processor = new AdminProcessor();
        [$newusers, $extraErrors] = $processor->processUsers(
            $groups,
            $username,
            $password,
            $oldpassword,
            $name,
            $email,
            $groupString,
            $status
        );
        $errors = array_merge($errors, $extraErrors);

        $o = "";
        if (empty($errors)) {
            $lock = $this->dbService->lock(true);
            $saved = $this->dbService->writeUsers($newusers);
            $this->dbService->unlock($lock);
            if (!$saved) {
                $filename = $this->dbService->dataFolder() . 'users.csv';
                $o .= $this->view->message("fail", "err_cannot_write_csv_adm", $filename);
            } else {
                $filename = $this->dbService->dataFolder() . 'users.csv';
                $o .= $this->view->message('success', 'csv_written', $filename);
            }
        } else {
            $o .= $this->renderErrorMessages($errors);
        }

        $o .= $this->renderUsersForm($newusers, $request->url(), $response);
        return $response->body($o);
    }

    /** @param list<array{string}|list<array{string}>> $errors */
    private function renderErrorMessages(array $errors): string
    {
        $o = "";
        foreach ($errors as $error) {
            if (is_string($error[0])) {
                /** @var array{string} $error */
                $o .= $this->view->message("fail", ...$error);
            } else {
                $o .= $this->view->message("info", ...$error[0]);
                foreach (array_slice($error, 1) as $error) {
                    $o .= $this->view->message("fail", ...$error);
                }
            }
        }
        return $o;
    }

    /** @param User[] $users */
    private function renderUsersForm(array $users, Url $url, Response $response): string
    {
        $response->addScript($this->pluginFolder . "admin.min.js");
        $response->addMeta("register_texts", $this->texts());
        $response->addMeta("register_max_number_of_users", $this->calcMaxRecords(7, 4));

        $data = [
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'defaultGroup' => $this->config['group_default'],
            'statusSelectActivated' => new HtmlString($this->statusSelectbox('activated')),
            'groups' => $this->findGroups(),
            'actionUrl' => $url->withPage("register")->relative(),
            'users' => $users,
        ];
        $groupStrings = $statusSelects = [];
        foreach ($users as $i => $entry) {
            $groupStrings[] = implode(",", $entry->getAccessgroups());
            $statusSelects[] = new HtmlString($this->statusSelectbox($entry->getStatus(), $i));
        }
        $data['groupStrings'] = $groupStrings;
        $data['statusSelects'] = $statusSelects;
        return $this->view->render('admin-users', $data);
    }

    /** @return array<string,string> */
    private function texts(): array
    {
        $extraKeys = ['name', 'username', 'password', 'accessgroups', 'status', 'email', 'prefsemailsubject'];
        $txts = [];
        foreach ($this->lang as $key => $val) {
            if (strpos($key, 'js_') === 0) {
                $txts[substr($key, 3)] =  $val;
            } elseif (in_array($key, $extraKeys, true)) {
                $txts[$key] = $val;
            }
        }
        return $txts;
    }

    /** @return UserGroup[] */
    private function findGroups(): array
    {
        $groups = $this->dbService->readGroups();
        usort($groups, function ($a, $b) {
            return strcasecmp($a->getGroupname(), $b->getGroupname());
        });
        return $groups;
    }

    /**
     * Returns the maximum number of records that may be successfully submitted from
     * a form.
     *
     * Takes into account <var>max_input_vars</var>,
     * <var>suhosin.post.max_vars</var> and <var>suhosin.request.max_vars</var>.
     * If none of these is set, <var>PHP_INT_MAX</var> is returned.
     *
     * @param int $varsPerRecord  Number of POST variables per record.
     * @param int $additionalVars Number of additional POST and GET variables.
     *
     * @return int
     */
    private function calcMaxRecords($varsPerRecord, $additionalVars)
    {
        $miv = ini_get('max_input_vars');
        $pmv = ini_get('suhosin.post.max_vars');
        $rmv = ini_get('suhosin.request.max_vars');
        foreach (array('miv', 'pmv', 'rmv') as $var) {
            if (!$$var) {
                $$var = PHP_INT_MAX;
            }
        }
        $maxVars = min((int) $miv, (int) $pmv, (int) $rmv);
        $maxRecords = intdiv(($maxVars - $additionalVars), $varsPerRecord);
        return $maxRecords;
    }

    /**
     * Returns the status selectbox.
     *
     * @param  string $value  The selected value.
     * @param  int $n  The running number.
     * @return string
     */
    private function statusSelectbox($value, $n = null)
    {
        $o = '<select name="status[' . $n . ']">';
        $opts = array('activated' => $this->lang['status_activated'], 'locked' => $this->lang['status_locked']);
        if (empty($value) || array_key_exists($value, $opts)) {
            $opts[''] = $this->lang['status_deactivated'];
        } else {
            $opts[$value] = $this->lang['status_not_yet_activated'];
        }
        foreach ($opts as $opt => $label) {
            $sel = $opt == $value ? ' selected="selected"' : '';
            $o .= '<option value="' . $opt . '"' . $sel . '>' . $label . '</option>';
        }
        $o .= '</select>';
        return $o;
    }
}
