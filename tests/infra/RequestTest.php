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
     * @param array{string,list<string>,list<string>,list<string>} $expected
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
            [[
                "add" => "",
                "delete" => [],
                "groupname" => [],
                "grouploginpage" => [],
            ], [
                "",
                [],
                [],
                []
            ]],
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
            ->onlyMethods(["post"])
            ->getMock();
    }
}
