<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection;

class MainAdminController extends Controller
{
    /**
     * @var CSRFProtection
     */
    private $csrfProtector;

    public function __construct()
    {
        global $_XH_csrfProtection;

        parent::__construct();
        $this->csrfProtector = $_XH_csrfProtection;
    }

    /**
     * @return void
     */
    public function editUsersAction()
    {
        $fn = Register_dataFolder() . 'users.csv';
        if (is_file($fn)) {
            $dbService = new DbService(Register_dataFolder());
            $dbService->lock(LOCK_SH);
            $users  = $dbService->readUsers();
            $dbService->lock(LOCK_UN);
            $this->prepareUsersForm($users)->render();
            echo XH_message('info', count($users) . ' ' . $this->lang['entries_in_csv'] . $fn);
        } else {
            echo XH_message('fail', $this->lang['err_csv_missing'] . ' (' . $fn . ')');
        }
    }

    /**
     * @return void
     */
    public function saveUsersAction()
    {
        $this->csrfProtector->check();
        $errors = [];
        if (is_file(Register_dataFolder() . 'groups.csv')) {
            $groups = (new DbService(Register_dataFolder()))->readGroups();
        } else {
            $groups = [];
            $errors[] = $this->lang['err_csv_missing'] . ' (' . Register_dataFolder() . 'groups.csv' . ')';
        }

        // put all available group Ids in an array for easier handling
        $groupIds = array();
        foreach ($groups as $entry) {
            $groupIds[] = $entry->groupname;
        }

        $delete      = isset($_POST['delete'])       ? $_POST['delete']       : [];
        $add         = isset($_POST['add'])          ? $_POST['add']          : '';
        $username    = isset($_POST['username'])     ? $_POST['username']     : [];
        $password    = isset($_POST['password'])     ? $_POST['password']     : [];
        $oldpassword = isset($_POST['oldpassword'])  ? $_POST['oldpassword']  : [];
        $name        = isset($_POST['name'])         ? $_POST['name']         : [];
        $email       = isset($_POST['email'])        ? $_POST['email']        : [];
        $groupString = isset($_POST['accessgroups']) ? $_POST['accessgroups'] : [];
        $status      = isset($_POST['status'])       ? $_POST['status']       : [];

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
                if (registerSearchUserArray($newusers, 'username', $username[$j]) !== false) {
                    $entryErrors[] = $this->lang['err_username_exists'];
                }
                if (registerSearchUserArray($newusers, 'email', $email) !== false) {
                    $entryErrors[] = $this->lang['err_email_exists'];
                }
                foreach ($userGroups as $groupName) {
                    if (!in_array($groupName, $groupIds)) {
                        $entryErrors[] = $this->lang['err_group_does_not_exist'] . ' (' . $groupName . ')';
                    }
                }
                if (!empty($entryErrors)) {
                    $view = new View('user-error');
                    $view->setData([
                        'username' => $username[$j],
                        'errors' => $entryErrors,
                    ]);
                    ob_start();
                    $view->render();
                    $errors[] = new HtmlString(ob_get_clean());
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

        // In case that nothing got deleted or added, store back (save got pressed)
        if (!$deleted && !$added && empty($errors)) {
            $dbService = new DbService(Register_dataFolder());
            $dbService->lock(LOCK_EX);
            if (!$dbService->writeUsers($newusers)) {
                $errors[] = $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'users.csv' . ')';
            }
            $dbService->lock(LOCK_UN);

            if (!empty($errors)) {
                $this->renderErrorView($errors);
            } else {
                echo XH_message(
                    'success',
                    $this->lang['csv_written'] . ' (' . Register_dataFolder() . 'users.csv' . ')'
                );
            }
        } elseif (!empty($errors)) {
            $this->renderErrorView($errors);
        }

        $this->prepareUsersForm($newusers)->render();
    }

    /**
     * @param string[] $errors
     * @return void
     */
    private function renderErrorView(array $errors)
    {
        $view = new View('error');
        $view->setData(['errors' => $errors]);
        $view->render();
    }

    /**
     * @param User[] $users
     * @return View
     */
    private function prepareUsersForm(array $users)
    {
        global $tx, $pth, $sn, $hjs;

        $jsKeys = ['name', 'username', 'password', 'accessgroups', 'status', 'email', 'prefsemailsubject'];
        $txts = array();
        foreach ($this->lang as $key => $val) {
            $val = addcslashes($val, "\0..\037\"\$");
            if (strpos($key, 'js_') === 0) {
                $txts[] = substr($key, 3) . ':"' . $val . '"';
            } elseif (in_array($key, $jsKeys)) {
                $txts[] = "$key:\"$val\"";
            }
        }

        $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins'] . 'register/admin.min.js"></script>'
            . '<script type="text/javascript">register.tx={' . implode(',', $txts) . '};'
            . 'register.maxNumberOfUsers=' . $this->calcMaxRecords(7, 4) . ';</script>';

        $view = new View('admin-users');
        $data = [
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'saveLabel' => ucfirst($tx['action']['save']),
            'defaultGroup' => $this->config['group_default'],
            'statusSelectActivated' => new HtmlString($this->statusSelectbox('activated')),
            'groups' => $this->findGroups(),
            'actionUrl' => "$sn?&register",
            'users' => $users,
        ];
        $groupStrings = $statusSelects = [];
        foreach ($users as $i => $entry) {
            $groupStrings[] = implode(",", $entry->accessgroups);
            $statusSelects[] = new HtmlString($this->statusSelectbox($entry->status, $i));
        }
        $data['groupStrings'] = $groupStrings;
        $data['statusSelects'] = $statusSelects;
        $view->setData($data);
        return $view;
    }

    /**
     * @return UserGroup[]
     */
    private function findGroups(): array
    {
        $groups = (new DbService(Register_dataFolder()))->readGroups();
        usort($groups, function ($a, $b) {
            return strcasecmp($a->groupname, $b->groupname);
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
        $maxVars = min($miv, $pmv, $rmv);
        $maxRecords = intval(($maxVars - $additionalVars) / $varsPerRecord);
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

    /**
     * @return void
     */
    public function editGroupsAction()
    {
        $filename = Register_dataFolder() . 'groups.csv';
        if (is_file($filename)) {
            $groups = (new DbService(Register_dataFolder()))->readGroups();
            $this->renderGroupsForm($groups);
            echo XH_message('info', count($groups) . ' ' . $this->lang['entries_in_csv'] . $filename);
        } else {
            echo XH_message('fail', $this->lang['err_csv_missing'] . ' (' . $filename . ')');
        }
    }

    /**
     * @return void
     */
    public function saveGroupsAction()
    {
        $this->csrfProtector->check();
        $errors = [];

        $delete      = isset($_POST['delete'])       ? $_POST['delete']       : [];
        $add         = isset($_POST['add'])          ? $_POST['add']          : '';
        $groupname   = isset($_POST['groupname'])    ? $_POST['groupname']    : [];

        $deleted = false;
        $added   = false;

        $newgroups = array();
        foreach (array_keys($groupname) as $j) {
            if (!preg_match("/^[A-Za-z0-9_-]+$/", $groupname[$j])) {
                $errors[] = $this->lang['err_group_illegal'];
            }

            if (!isset($delete[$j]) || $delete[$j] == '') {
                $entry = new UserGroup($groupname[$j], $_POST['grouploginpage'][$j]);
                $newgroups[] = $entry;
            } else {
                $deleted = true;
            }
        }
        if ($add != '') {
            $entry = new UserGroup("NewGroup", '');
            $newgroups[] = $entry;
            $added = true;
        }

        // In case that nothing got deleted or added, store back (save got pressed)
        if (!$deleted && !$added && empty($errors)) {
            if (!(new DbService(Register_dataFolder()))->writeGroups($newgroups)) {
                $errors[] = $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'groups.csv' . ')';
            }
            if (!empty($errors)) {
                $this->renderErrorView($errors);
            } else {
                echo XH_message(
                    'success',
                    $this->lang['csv_written'] . '(' . Register_dataFolder() . 'groups.csv' . ')'
                );
            }
        } elseif (!empty($errors)) {
            $this->renderErrorView($errors);
        }

        $this->renderGroupsForm($newgroups);
    }

    /**
     * @param UserGroup[] $groups
     * @return void
     */
    private function renderGroupsForm(array $groups)
    {
        global $tx, $sn;
    
        $view = new View('admin-groups');
        $data = [
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'actionUrl' => "$sn?&register",
            'saveLabel' => ucfirst($tx['action']['save']),
            'groups' => $groups,
        ];
        $selects = [];
        foreach ($groups as $i => $entry) {
            $selects[] = new HtmlString($this->pageSelectbox($entry->loginpage, $i));
        }
        $data['selects'] = $selects;
        $view->setData($data);
        $view->render();
    }

    private function pageSelectbox(string $loginpage, int $n): string
    {
        global $cl, $h, $u, $l;
    
        $o = '<select name="grouploginpage[' . $n . ']"><option value="">' . $this->lang['label_none'] . '</option>';
        for ($i = 0; $i < $cl; $i++) {
            $sel = $u[$i] == $loginpage ? ' selected="selected"' : '';
            $o .= '<option value="' . $u[$i] . '"' . $sel . '>'
                . str_repeat('&nbsp;', 3 * ($l[$i] - 1)) . $h[$i] . '</option>';
        }
        $o .= '</select>';
        return $o;
    }
}
