<?php

/**
 * Copyright (c) 2013-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class ActivityRepository
{
    /** @var DbService */
    private $dbService;

    public function __construct(DbService $dbService)
    {
        $this->dbService = $dbService;
    }

    public function update(string $username, int $time): bool
    {
        $lock = $this->dbService->lock(true);
        $members = $this->read();
        if ($time === 0) {
            unset($members[$username]);
        } else {
            $members[$username] = $time;
        }
        $ok = $this->write($members);
        $this->dbService->unlock($lock);
        return $ok;
    }

    /** @return list<string> */
    public function find(int $since): array
    {
        $lock = $this->dbService->lock(false);
        $members = $this->read();
        $this->dbService->unlock($lock);
        $users = array_filter($members, function (int $time) use ($since) {
            return $time >= $since;
        });
        $users = array_map(null, array_keys($users));
        natcasesort($users);
        return array_values($users);
    }

    /** @return array<string,int> */
    private function read(): array
    {
        $filename = $this->dbService->dataFolder() . "active_users.dat";
        if (!is_file($filename) || !is_readable($filename)) {
            return [];
        }
        if (!($members = file_get_contents($filename))) {
            return [];
        }
        $members = unserialize($members);
        assert(is_array($members));
        return $members;
    }

    /** @param array<string,int> $users */
    private function write(array $users): bool
    {
        $filename = $this->dbService->dataFolder() . "active_users.dat";
        return file_put_contents($filename, serialize($users)) !== false;
    }
}
