<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /** @dataProvider registerUserPosts */
    public function testRegisterUserPost(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $result = $sut->registerUserPost();
        $this->assertEquals($expected, $result);
    }

    public function registerUserPosts(): array
    {
        return [
            [[], ["name" => "", "username" => "", "password1" => "", "password2" => "", "email" => ""]],
        ];
    }

    /** @dataProvider activationParams */
    public function testActivationParams(array $get, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("get")->willReturn($get);
        $result = $sut->activationParams();
        $this->assertEquals($expected, $result);
    }

    public function activationParams(): array
    {
        return [
            [[], ["username" => "", "nonce" => ""]],
        ];
    }

    /** @dataProvider forgotPasswordPosts */
    public function testForgotPasswordPost(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $result = $sut->forgotPasswordPost();
        $this->assertEquals($expected, $result);
    }

    public function forgotPasswordPosts(): array
    {
        return [
            [[], ["email" => ""]],
        ];
    }

    /** @dataProvider resetPasswordParams */
    public function testResetPasswordParams(array $get, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("get")->willReturn($get);
        $result = $sut->resetPasswordParams();
        $this->assertEquals($expected, $result);
    }

    public function resetPasswordParams(): array
    {
        return [
            [[], ["username" => "", "time" => "", "mac" => ""]],
        ];
    }

    /** @dataProvider changePasswordPosts */
    public function testChangePasswordPost(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $result = $sut->changePasswordPost();
        $this->assertEquals($expected, $result);
    }

    public function changePasswordPosts(): array
    {
        return [
            [[], ["password1" => "", "password2" => ""]],
        ];
    }

    /** @dataProvider changePrefsPosts */
    public function testChangePrefsPost(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $result = $sut->changePrefsPost();
        $this->assertEquals($expected, $result);
    }

    public function changePrefsPosts(): array
    {
        return [
            [[], ["oldpassword" => "", "name" => "", "password1" => "", "password2" => "", "email" => ""]],
        ];
    }

    /** @dataProvider unregisterPosts */
    public function testUnregisterPost(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $result = $sut->unregisterPost();
        $this->assertEquals($expected, $result);
    }

    public function unregisterPosts(): array
    {
        return [
            [[], ["oldpassword" => "", "name" => "", "email" => ""]],
        ];
    }

    /**
     * @param array<string,string|array<string>> $post
     * @dataProvider groupAdminActions
     */
    public function testGroupAdminAction(array $post, string $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $action = $sut->groupAdminAction();
        $this->assertEquals($expected, $action);
    }

    public function groupAdminActions(): array
    {
        return [
            [[], "update"],
        ];
    }

    /**
     * @param array<string,string|array<string>> $post
     * @param array{string,list<string>,list<string>} $expected
     * @dataProvider groupAdminSubmissions
     */
    public function testGroupAdminSubmission(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $submission = $sut->groupAdminSubmission();
        $this->assertEquals($expected, $submission);
    }

    public function groupAdminSubmissions(): array
    {
        return [
            [
                ["action" => "save", "groupname" => [], "grouploginpage" => []],
                ["save", [], []]
            ], [
                ["action" => "add", "groupname" => [], "grouploginpage" => []],
                ["add", [], []]
            ], [
                ["action" => "1", "groupname" => [], "grouploginpage" => []],
                ["1", [], []]
            ],
        ];
    }

    /**
     * @param array<string,string|array<string>> $post
     * @dataProvider userAdminActions
     */
    public function testUserAdminAction(array $post, string $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $action = $sut->userAdminAction();
        $this->assertEquals($expected, $action);
    }

    public function userAdminActions(): array
    {
        return [
            [[], "update"],
        ];
    }

    /**
     * @param array<string,string|array<string>> $post
     * @param array{list<string>,list<string>,list<string>,list<string>,list<string>,list<string>,list<string>,list<string>} $expected
     * @dataProvider userAdminSubmissions
     */
    public function testUserAdminSubmission(array $post, array $expected): void
    {
        $sut = $this->sut();
        $sut->method("post")->willReturn($post);
        $submission = $sut->userAdminSubmission();
        $this->assertEquals($expected, $submission);
    }

    public function userAdminSubmissions(): array
    {
        return [
            [[
                "username" => [],
                "password" => [],
                "oldpassword" => [],
                "name" => [],
                "email" => [],
                "accessgroups" => [],
                "status" => [],
                "secrets" => [],
            ], [
                [],
                [],
                [],
                [],
                [],
                [],
                [],
                [],
            ]]
        ];
    }

    private function sut(): Request
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(["get", "post"])
            ->getMock();
    }
}
