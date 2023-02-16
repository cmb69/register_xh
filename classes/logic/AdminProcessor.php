<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Value\UserGroup;

class AdminProcessor
{
    /**
     * @param list<string> $delete
     * @param list<string> $names
     * @param list<string> $loginPages
     * @return array{list<UserGroup>,bool,list<array{string}>}
     */
    public function processGroups(string $add, array $delete, array $names, array $loginPages): array
    {
        $groups = [];
        $save = true;
        $errors = [];
        foreach (array_keys($names) as $i) {
            if (!preg_match("/^[A-Za-z0-9_-]+$/", $names[$i])) {
                $errors[] = ['err_group_illegal'];
            }
            if (!isset($delete[$i]) || $delete[$i] == '') {
                $groups[] = new UserGroup($names[$i], $loginPages[$i]);
            } else {
                $save = false;
            }
        }
        if ($add != '') {
            $groups[] = new UserGroup("NewGroup", '');
            $save = false;
        }
        return [$groups, $save, $errors];
    }
}
