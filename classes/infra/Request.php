<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\Mail;
use Register\Value\User;
use Register\Value\UserGroup;

class Request
{
    /** @codeCoverageIgnore */
    public static function current(): self
    {
        return new self;
    }

    /** @codeCoverageIgnore */
    public function username(): string
    {
        return $_SESSION["username"] ?? "";
    }

    /** @codeCoverageIgnore */
    public function registerAction(): string
    {
        return $_POST["register_action"] ?? $_GET["register_action"] ?? "";
    }

    public function action(): string
    {
        return $_POST["action"] ?? $_GET["action"] ?? "";
    }

    /** @return array{username:string,password:string,remember:string} */
    public function registerLoginPost(): array
    {
        return [
            "username" => $this->trimmedPostString("username"),
            "password" => $this->trimmedPostString("password"),
            "remember" => $this->trimmedPostString("remember"),
        ];
    }

    /** @return array{name:string,username:string,password1:string,password2:string,email:string} */
    public function registerUserPost(): array
    {
        return [
            "name" => $this->trimmedPostString("name"),
            "username" => $this->trimmedPostString("username"),
            "password1" => $this->trimmedPostString("password1"),
            "password2" => $this->trimmedPostString("password2"),
            "email" => $this->trimmedPostString("email"),
        ];
    }

    /** @return array{username:string,nonce:string} */
    public function activationParams(): array
    {
        return [
            "username" => $this->trimmedGetString("username"),
            "nonce" => $this->trimmedGetString("nonce"),
        ];
    }

    /** @return array{email:string} */
    public function forgotPasswordPost(): array
    {
        return [
            "email" => $this->trimmedPostString("email"),
        ];
    }

    /** @return array{username:string,time:string,mac:string} */
    public function resetPasswordParams(): array
    {
        return [
            "username" => $this->trimmedGetString("username"),
            "time" => $this->trimmedGetString("time"),
            "mac" => $this->trimmedGetString("mac"),
        ];
    }

    /** @return array{password1:string,password2:string} */
    public function changePasswordPost(): array
    {
        return [
            "password1" => $this->trimmedPostString("password1"),
            "password2" => $this->trimmedPostString("password2"),
        ];
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

    /** @return array{oldpassword:string} */
    public function unregisterPost(): array
    {
        return [
            "oldpassword" => $this->trimmedPostString("oldpassword"),
        ];
    }

    /** @return array{username:string,name:string,group:string,email:string,status:string} */
    public function userFilters(): array
    {
        return [
            "username" => $this->trimmedGetString("username"),
            "name" => $this->trimmedGetString("name"),
            "group" => $this->trimmedGetString("group"),
            "email" => $this->trimmedGetString("email"),
            "status" => $this->trimmedGetString("status"),
        ];
    }

    public function selectedUser(): string
    {
        return isset($_GET["user"]) && is_string($_GET["user"]) ? $_GET["user"] : "";
    }

    /** @return array{name:string,email:string,groups:list<string>,status:string} */
    public function userPost(): array
    {
        return [
            "name" => $this->trimmedPostString("name"),
            "email" => $this->trimmedPostString("email"),
            "groups" => $this->trimmedPostArray("groups"),
            "status" => $this->trimmedPostString("status"),
        ];
    }

    public function postedUser(): User
    {
        return new User(
            $this->trimmedPostString("username") ?: $this->selectedUser(),
            $this->trimmedPostString("password1"),
            $this->trimmedPostArray("groups"),
            $this->trimmedPostString("name"),
            $this->trimmedPostString("email"),
            $this->trimmedPostString("status"),
            ""
        );
    }

    public function postedPassword(): string
    {
        return $this->trimmedPostString("password2");
    }

    public function postedMail(): Mail
    {
        return new Mail($this->trimmedPostString("subject"), $this->trimmedPostString("message"));
    }

    public function selectedGroup(): string
    {
        return isset($_GET["group"]) && is_string($_GET["group"]) ? $_GET["group"] : "";
    }

    public function postedGroup(): UserGroup
    {
        return new UserGroup(
            $this->trimmedPostString("groupname") ?: $this->selectedGroup(),
            $this->trimmedPostString("loginpage")
        );
    }

    private function trimmedPostString(string $param): string
    {
        $post = $this->post();
        return (isset($post[$param]) && is_string($post[$param])) ? trim($post[$param]) : "";
    }

    /** @return list<string> */
    private function trimmedPostArray(string $param): array
    {
        $post = $this->post();
        return (isset($post[$param]) && is_array($post[$param]))
            ? array_values(array_map("trim", ($post[$param])))
            : [];
    }

    private function trimmedGetString(string $param): string
    {
        $get = $this->get();
        return (isset($get[$param]) && is_string($get[$param])) ? trim($get[$param]) : "";
    }

    /**
     * @return array<string,string|array<string>>
     * @codeCoverageIgnore
     */
    protected function get(): array
    {
        return $_GET;
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
