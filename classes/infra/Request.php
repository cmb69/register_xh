<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Request
{
    /** @codeCoverageIgnore */
    public function username(): string
    {
        return $_SESSION["username"] ?? "";
    }

    /** @codeCoverageIgnore */
    public function registerAction(): string
    {
        return $_POST["register_action"] ?? "";
    }

    /** @return array{oldpassword:string,name:string,password1:string,password2:string,email:string} */
    public function changePrefsPost(): array
    {
        return [
            "oldpassword" => $this->trimmedPostString("oldpassword"),
            "name" => $this->trimmedPostString("name"),
            "password1" => $this->trimmedPostString("password1"),
            "password2" => $this->trimmedPostString("password2"),
            "email" => $this->trimmedPostString("email"),
        ];
    }

    /** @return array{oldpassword:string,name:string,email:string} */
    public function unregisterPost(): array
    {
        return [
            "oldpassword" => $this->trimmedPostString("oldpassword"),
            "name" => $this->trimmedPostString("name"),
            "email" => $this->trimmedPostString("email"),
        ];
    }

    private function trimmedPostString(string $param): string
    {
        $post = $this->post();
        return (isset($post[$param]) && is_string($post[$param])) ? trim($post[$param]) : "";
    }

    public function groupAdminAction(): string
    {
        return $this->hasGroupAdminSubmission($this->post()) ? "do_update" : "update";
    }

    /** @return array{string,list<string>,list<string>} */
    public function groupAdminSubmission(): array
    {
        $post = $this->post();
        assert($this->hasGroupAdminSubmission($post));
        return [
            $post["action"],
            $post["groupname"],
            $post["grouploginpage"],
        ];
    }

    /**
     * @param array<string,string|array<string>> $post
     * @phpstan-assert-if-true array{action:string,groupname:list<string>,grouploginpage:list<string>} $post
     */
    private function hasGroupAdminSubmission(array $post): bool
    {
        $post = $this->post();
        if (isset($post["action"])
            && ($post["action"] === "add" || is_numeric($post["action"]) || $post["action"] === "save")
            && isset($post["groupname"]) && is_array($post["groupname"])
            && isset($post["grouploginpage"]) && is_array($post["grouploginpage"])
        ) {
            return true;
        }
        return false;
    }

    public function userAdminAction(): string
    {
        return $this->hasUserAdminSubmission($this->post()) ? "do_update" : "update";
    }

    /** @return array{list<string>,list<string>,list<string>,list<string>,list<string>,list<string>,list<string>,list<string>} */
    public function userAdminSubmission(): array
    {
        $post = $this->post();
        assert($this->hasUserAdminSubmission($post));
        return [
            $post["username"],
            $post["password"],
            $post["oldpassword"],
            $post["name"],
            $post["email"],
            $post["accessgroups"],
            $post["status"],
            $post["secrets"],
        ];
    }

    /**
     * @param array<string,string|array<string>> $post
     * @phpstan-assert-if-true array{username:list<string>,password:list<string>,oldpassword:list<string>,name:list<string>,email:list<string>,accessgroups:list<string>,status:list<string>,secrets:list<string>} $post
     */
    private function hasUserAdminSubmission(array $post): bool
    {
        $post = $this->post();
        if (isset($post["username"]) && is_array($post["username"])
            && isset($post["password"]) && is_array($post["password"])
            && isset($post["oldpassword"]) && is_array($post["oldpassword"])
            && isset($post["name"]) && is_array($post["name"])
            && isset($post["email"]) && is_array($post["email"])
            && isset($post["accessgroups"]) && is_array($post["accessgroups"])
            && isset($post["status"]) && is_array($post["status"])
            && isset($post["secrets"]) && is_array($post["secrets"])
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return array<string,string|array<string>>
     * @codeCoverageIgnore
     */
    protected function post(): array
    {
        return $_POST;
    }

    public function url(): Url
    {
        global $sn, $su;

        return new Url($sn, $su);
    }

    /** @codeCoverageIgnore */
    public function coreStyleFolder(): string
    {
        global $pth;
        return $pth["folder"]["corestyle"];
    }

    /** @codeCoverageIgnore */
    public function pluginsFolder(): string
    {
        global $pth;
        return $pth["folder"]["plugins"];
    }

    /** @codeCoverageIgnore */
    public function editMode(): bool
    {
        global $edit;
        return defined("XH_ADM") && XH_ADM && $edit;
    }

    /** @codeCoverageIgnore */
    public function function(): string
    {
        global $function;
        return $function;
    }

    /** @codeCoverageIgnore */
    public function time(): int
    {
        return $_SERVER["REQUEST_TIME"];
    }

    /** @codeCoverageIgnore */
    public function serverName(): string
    {
        return $_SERVER["SERVER_NAME"];
    }

    /** @codeCoverageIgnore */
    public function remoteAddress(): string
    {
        return $_SERVER["REMOTE_ADDR"];
    }

    /** @codeCoverageIgnore */
    public function cookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }
}
