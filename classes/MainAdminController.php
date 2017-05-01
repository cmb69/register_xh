<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class MainAdminController extends Controller
{
    public function editUsersAction()
    {
        $fn = Register_dataFolder() . 'users.csv';
        if (is_file($fn)) {
            (new DbService(Register_dataFolder()))->lock(LOCK_SH);
            $users  = (new DbService(Register_dataFolder()))->readUsers();
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);
            $this->prepareUsersForm($users)->render();
            echo XH_message('info', count($users) . ' ' . $this->lang['entries_in_csv'] . $fn);
        } else {
            echo XH_message('fail', $this->lang['err_csv_missing'] . ' (' . $fn . ')');
        }
    }

    public function saveUsersAction()
    {
        $errors = [];
        if (is_file(Register_dataFolder() . 'groups.csv')) {
            $groups = (new DbService(Register_dataFolder()))->readGroups();
        } else {
            $errors[] = $this->lang['err_csv_missing'] . ' (' . Register_dataFolder() . 'groups.csv' . ')';
        }

        // put all available group Ids in an array for easier handling
        $groupIds = array();
        foreach ($groups as $entry) {
            $groupIds[] = $entry->groupname;
        }

        $delete      = isset($_POST['delete'])       ? $_POST['delete']       : '';
        $add         = isset($_POST['add'])          ? $_POST['add']          : '';
        $username    = isset($_POST['username'])     ? $_POST['username']     : '';
        $password    = isset($_POST['password'])     ? $_POST['password']     : '';
        $oldpassword = isset($_POST['oldpassword'])  ? $_POST['oldpassword']  : '';
        $name        = isset($_POST['name'])         ? $_POST['name']         : '';
        $email       = isset($_POST['email'])        ? $_POST['email']        : '';
        $groupString = isset($_POST['accessgroups']) ? $_POST['accessgroups'] : '';
        $status      = isset($_POST['status'])       ? $_POST['status']       : '';

        $deleted = false;
        $added   = false;

        $newusers = array();
        foreach (array_keys($username) as $j) {
            if (!isset($delete[$j]) || $delete[$j] == '') {
                $userGroups = explode(",", $groupString[$j]);
                // Error Checking
                $entryErrors = [];
                if ($password[$j] == $oldpassword[$j]) {
                    $entryErrors = array_merge(
                        $entryErrors,
                        registerCheckEntry($name[$j], $username[$j], "dummy", "dummy", $email[$j])
                    );
                } else {
                    $entryErrors = array_merge(
                        $entryErrors,
                        registerCheckEntry($name[$j], $username[$j], $password[$j], $password[$j], $email[$j])
                    );
                }
                $entryErrors = array_merge(
                    $entryErrors,
                    registerCheckColons($name[$j], $username[$j], $password[$j], $email[$j])
                );
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
                    $view->username = $username[$j];
                    $view->errors = $entryErrors;
                    $errors[] = new HtmlString($view);
                }
        
                if (empty($entryErrors) && $password[$j] != $oldpassword[$j]) {
                    $password[$j] = $this->hasher->hashPassword($password[$j]);
                }
                $entry = (object) array(
                    'username'     => $username[$j],
                    'password'     => $password[$j],
                    'accessgroups' => $userGroups,
                    'name'         => $name[$j],
                    'email'        => $email[$j],
                    'status'       => $status[$j]
                );
                $newusers[] = $entry;
            } else {
                $deleted = true;
            }
        }
        if ($add <> '') {
            $entry = (object) array(
                'username'     => "NewUser",
                'password'     => "",
                'accessgroups' => array($this->config['group_default']),
                'name'         => "Name Lastname",
                'email'        => "user@domain.com",
                'status'       => "activated"
            );
            $newusers[] = $entry;
            $added = true;
        }

        // In case that nothing got deleted or added, store back (save got pressed)
        if (!$deleted && !$added && empty($errors)) {
            (new DbService(Register_dataFolder()))->lock(LOCK_EX);
            if (!(new DbService(Register_dataFolder()))->writeUsers($newusers)) {
                $errors[] = $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'users.csv' . ')';
            }
            (new DbService(Register_dataFolder()))->lock(LOCK_UN);

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

    private function renderErrorView(array $errors)
    {
        $view = new View('error');
        $view->errors = $errors;
        $view->render();
    }

    /**
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
        $view->saveLabel = ucfirst($tx['action']['save']);
        $view->defaultGroup = $this->config['group_default'];
        $view->statusSelectActivated = new HtmlString($this->statusSelectbox('activated'));
        $view->groups = $this->findGroups();
        $view->actionUrl = "$sn?&register";
        $view->users = $users;
        $groupStrings = $statusSelects = [];
        foreach ($users as $i => $entry) {
            $groupStrings[] = implode(",", $entry->accessgroups);
            $statusSelects[] = new HtmlString($this->statusSelectbox($entry->status, $i));
        }
        $view->groupStrings = $groupStrings;
        $view->statusSelects = $statusSelects;
        return $view;
    }

    /**
     * @return array
     */
    private function findGroups()
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

    public function editGroupsAction()
    {
        $filename = Register_dataFolder() . 'groups.csv';
        if (is_file($filename)) {
            $groups = (new DbService(Register_dataFolder()))->readGroups();
            echo $this->prepareGroupsForm($groups);
            echo XH_message('info', count($groups) . ' ' . $this->lang['entries_in_csv'] . $filename);
        } else {
            echo XH_message('fail', $this->lang['err_csv_missing'] . ' (' . $filename . ')');
        }
    }

    public function saveGroupsAction()
    {
        $errors = [];

        $delete      = isset($_POST['delete'])       ? $_POST['delete']       : '';
        $add         = isset($_POST['add'])          ? $_POST['add']          : '';
        $groupname   = isset($_POST['groupname'])    ? $_POST['groupname']    : '';

        $deleted = false;
        $added   = false;

        $newgroups = array();
        foreach (array_keys($groupname) as $j) {
            if (!preg_match("/^[A-Za-z0-9_-]+$/", $groupname[$j])) {
                $errors[] = $this->lang['err_group_illegal'];
            }

            if (!isset($delete[$j]) || $delete[$j] == '') {
                $entry = (object) ['groupname' => $groupname[$j], 'loginpage' => $_POST['grouploginpage'][$j]];
                $newgroups[] = $entry;
            } else {
                $deleted = true;
            }
        }
        if ($add <> '') {
            $entry = (object) ['groupname' => "NewGroup", 'loginpage' => ''];
            $newgroups[] = $entry;
            $added = true;
        }

        // In case that nothing got deleted or added, store back (save got pressed)
        if (!$deleted && !$added && empty($errors)) {
            if (!(new DbService(Register_dataFolder()))->writeGroups($newgroups)) {
                $errors .= $this->lang['err_cannot_write_csv'] . ' (' . Register_dataFolder() . 'groups.csv' . ')';
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

        echo $this->prepareGroupsForm($newgroups);
    }

    /**
     * @return View
     */
    private function prepareGroupsForm(array $groups)
    {
        global $tx, $sn;
    
        $view = new View('admin-groups');
        $view->actionUrl = "$sn?&register";
        $view->saveLabel = ucfirst($tx['action']['save']);
        $view->groups = $groups;
        $selects = [];
        foreach ($groups as $i => $entry) {
            $selects[] = new HtmlString($this->pageSelectbox($entry->loginpage, $i));
        }
        $view->selects = $selects;
        return $view;
    }

    
    private function pageSelectbox($loginpage, $n)
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
