<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class FakeDbService extends DbService
{
    private $options;

    public function options(array $options)
    {
        $this->options = $options;
    }

    public function writeGroups(array $groups): bool
    {
        if (isset($this->options["writeGroups"]) && $this->options["writeGroups"] === false) {
            return false;
        }
        return parent::writeGroups($groups);
    }

    public function writeUsers(array $users): bool
    {
        if (isset($this->options["writeUsers"]) && $this->options["writeUsers"] === false) {
            return false;
        }
        return parent::writeUsers($users);
    }
}
