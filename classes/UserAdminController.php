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
use Register\Logic\ValidationService;
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
            $errors[] = sprintf($this->lang['err_csv_missing'], $this->dbService->dataFolder() . 'groups.csv');
        }

        // put all available group Ids in an array for easier handling
        $groupIds = array();
        foreach ($groups as $entry) {
            $groupIds[] = $entry->getGroupname();
        }

        $delete = $_POST['delete'] ?? [];
        $add = $_POST['add'] ?? '';
        $username = $_POST['username'] ?? [];
        $password = $_POST['password'] ?? [];
        $oldpassword = $_POST['oldpassword'] ?? [];
        $name = $_POST['name'] ?? [];
        $email = $_POST['email'] ?? [];
        $groupString = $_POST['accessgroups'] ?? [];
        $status = $_POST['status'] ?? [];

        $deleted = false;
        $added   = false;

        $validationService = new ValidationService($this->lang);

        $newusers = array();
        foreach (array_keys($username) as $j) {
            if (!isset($delete[$j]) || $delete[$j] == '') {
                $userGroups = explode(",", $groupString[$j]);
                // Error Checking
                $entryErrors = [];
                if ($password[$j] == $oldpassword[$j]) {
                    $entryErrors = array_merge(
                        $entryErrors,
                        $validationService->validateUser($name[$j], $username[$j], "dummy", "dummy", $email[$j])
                    );
                } else {
                    $entryErrors = array_merge(
                        $entryErrors,
                        $validationService->validateUser(
                            $name[$j],
                            $username[$j],
                            $password[$j],
                            $password[$j],
                            $email[$j]
                        )
                    );
                }
                foreach ($newusers as $newuser) {
                    if ($newuser->getUsername() === $username[$j]) {
                        $entryErrors[] = $this->lang['err_username_exists'];
                    }
                    if ($newuser->getEmail() === $email[$j]) {
                        $entryErrors[] = $this->lang['err_email_exists'];
                    }
                }
                foreach ($userGroups as $groupName) {
                    if (!in_array($groupName, $groupIds)) {
                        $entryErrors[] = $this->lang['err_group_does_not_exist'] . ' (' . $groupName . ')';
                    }
                }
                if (!empty($entryErrors)) {
                    $errors[] = new HtmlString($this->view->render('user-error', [
                        'username' => $username[$j],
                        'errors' => $entryErrors,
                    ]));
                }
                if ($password[$j] == '') {
                    $password[$j] = base64_encode(random_bytes(16));
                }
                if (empty($entryErrors) && $password[$j] != $oldpassword[$j]) {
                    $password[$j] = password_hash($password[$j], PASSWORD_DEFAULT);
                }
                $entry = new User(
                    $username[$j],
                    $password[$j],
                    $userGroups,
                    $name[$j],
                    $email[$j],
                    $status[$j]
                );
                $newusers[] = $entry;
            } else {
                $deleted = true;
            }
        }
        if ($add != '') {
            $entry = new User(
                "NewUser",
                "",
                array($this->config['group_default']),
                "Name Lastname",
                "user@domain.com",
                "activated"
            );
            $newusers[] = $entry;
            $added = true;
        }

        $o = "";
        // In case that nothing got deleted or added, store back (save got pressed)
        if (!$deleted && !$added && empty($errors)) {
            $lock = $this->dbService->lock(true);
            if (!$this->dbService->writeUsers($newusers)) {
                $errors[] = $this->lang['err_cannot_write_csv']
                    . ' (' . $this->dbService->dataFolder() . 'users.csv' . ')';
            }
            $this->dbService->unlock($lock);

            if (!empty($errors)) {
                $o .= $this->view->render('error', ['errors' => $errors]);
            } else {
                $filename = $this->dbService->dataFolder() . 'users.csv';
                $o .= $this->view->message('success', 'csv_written', $filename);
            }
        } elseif (!empty($errors)) {
            $o .= $this->view->render('error', ['errors' => $errors]);
        }

        $o .= $this->renderUsersForm($newusers, $request->url(), $response);
        return $response->body($o);
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
