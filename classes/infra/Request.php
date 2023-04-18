<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\Mail;
use Register\Value\Passwords;
use Register\Value\Url;
use Register\Value\User;
use Register\Value\UserGroup;

class Request
{
    /** @codeCoverageIgnore */
    public static function current(): self
    {
        return new self();
    }

    /** @codeCoverageIgnore */
    public function username(): string
    {
        return $_SESSION["username"] ?? "";
    }

    public function registerAction(): string
    {
        $post = $this->post();
        if (isset($post["register_action"]) && is_string($post["register_action"])) {
            return $post["register_action"];
        }
        $action = $this->url()->param("register_action");
        if (is_string($action)) {
            return $action;
        }
        return "";
    }

    public function action(): string
    {
        $post = $this->post();
        if (isset($post["action"]) && is_string($post["action"])) {
            return $post["action"];
        }
        $action = $this->url()->param("action");
        if (is_string($action)) {
            return $action;
        }
        return "";
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
            "username" => $this->trimmedGetString("register_username"),
            "nonce" => $this->trimmedGetString("register_nonce"),
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

    public function postedPasswords(): Passwords
    {
        return new Passwords(
            $this->trimmedPostString("password1"),
            $this->trimmedPostString("password2")
        );
    }

    /** @return array{oldpassword:string,name:string,email:string} */
    public function changePrefsPost(): array
    {
        return [
            "oldpassword" => $this->trimmedPostString("oldpassword"),
            "name" => $this->trimmedPostString("name"),
            "email" => $this->trimmedPostString("email"),
        ];
    }

    public function postedPassword(): string
    {
        return $this->trimmedPostString("oldpassword");
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
        $user = $this->url()->param("user");
        return is_string($user) ? $user : "";
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

    public function postedConfirmation(): string
    {
        return $this->trimmedPostString("password2");
    }

    public function postedMail(): Mail
    {
        return new Mail($this->trimmedPostString("subject"), $this->trimmedPostString("message"));
    }

    public function selectedGroup(): string
    {
        $group = $this->url()->param("group");
        return is_string($group) ? $group : "";
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
        $param = $this->url()->param($param);
        return is_string($param) ? $param : "";
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
        $rest = $this->query();
        if ($rest !== "") {
            $rest = "?" . $rest;
        }
        return Url::from(CMSIMPLE_URL . $rest);
    }

    /** @codeCoverageIgnore */
    public function editMode(): bool
    {
        global $edit;
        return defined("XH_ADM") && XH_ADM && $edit;
    }

    public function function(): string
    {
        $function = $this->url()->param("function");
        if (is_string($function)) {
            return $function;
        }
        $post = $this->post();
        if (isset($post["function"]) && is_string($post["function"])) {
            return $post["function"];
        }
        return "";
    }

    /** @codeCoverageIgnore */
    protected function query(): string
    {
        return $_SERVER["QUERY_STRING"];
    }

    /** @codeCoverageIgnore */
    public function time(): int
    {
        return (int) $_SERVER["REQUEST_TIME"];
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

    public function cookie(string $name): ?string
    {
        return $this->cookies()[$name] ?? null;
    }

    /**
     * @return array<string,string>
     * @codeCoverageIgnore
     */
    protected function cookies(): array
    {
        return $_COOKIE;
    }
}
