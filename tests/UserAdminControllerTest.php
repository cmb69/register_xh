<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use XH\CSRFProtection as CsrfProtector;

use Register\Value\User;
use Register\Value\UserGroup;
use Register\Infra\DbService;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class UserAdminControllerTest extends TestCase
{
    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "Foo"));
    }

    public function testEditUsersActionRendersUsers()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(true);
        $dbService->method('readUsers')->willReturn([$this->makeJohn(), $this->makeJane()]);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", ""), new UserGroup("guest", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("update");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditUsersActionIncludesScripts()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(true);
        $dbService->method('readUsers')->willReturn([$this->makeJohn(), $this->makeJane()]);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("update");
        $this->request->method("pluginsFolder")->willReturn("./plugins/");
        $response = $sut($this->request);
        $expected = [
            "register_texts" => [
                "name" => "Full Name",
                "username" => "Username",
                "password" => "Password",
                "email" => "Email",
                "accessgroups" => "Access Groups",
                "status" => "Status",
                "prefsemailsubject" => "Account data changed for %s",
                "confirmLeave" => "Unsaved changes will be lost!",
                "newPassword" => "New Password:",
                "tooManyUsers" => "There are too many users!",
            ],
            "register_max_number_of_users" => 124,
        ];
        $this->assertEquals($expected, $response->meta());
        $this->assertEquals("./plugins/register/admin.min.js", $response->script());
    }

    public function testEditUsersActionFailsIfNoUsersFile()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasUsersFile')->willReturn(false);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("update");
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersFailsOnInvalidUserName()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("do_update");
        $this->request->method("userAdminSubmission")->willReturn([
            [""],
            ["test"],
            ["test"],
            ["Christoph Becker"],
            ["cmb@example.com"],
            ["users"],
            ["activated"],
            ["secret"],
        ]);
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersFailsOnDuplicateUserNameAndEmail()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("do_update");
        $this->request->method("userAdminSubmission")->willReturn([
            ["cmb", "cmb"],
            ["pw1", "pw2"],
            ["pw1", "pw2"],
            ["Christoph Becker", "Christoph Becker"],
            ["cmb@example.com", "cmb@example.com"],
            ["users", "users"],
            ["activated", "activated"],
            ["secret", "secret"],
        ]);
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersFailsToSave()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->once())->method('writeUsers')->willReturn(false);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("do_update");
        $this->request->method("userAdminSubmission")->willReturn([
            ["cmb"],
            ["pw"],
            ["pw"],
            ["Christoph Becker"],
            ["cmb@example.com"],
            ["users"],
            ["activated"],
            ["secret"],
        ]);
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSaveUsersSuccessfullySaves()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->once())->method('writeUsers')->willReturn(true);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("do_update");
        $this->request->method("userAdminSubmission")->willReturn([
            ["cmb"],
            ["pw"],
            ["pw"],
            ["Christoph Becker"],
            ["cmb@example.com"],
            ["users"],
            ["activated"],
            ["secret"],
        ]);
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testGeneratesRandomPasswordForNewUsers()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->once())->method('writeUsers')->willReturn(true);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("do_update");
        $this->request->method("userAdminSubmission")->willReturn([
            ["cmb"],
            [""],
            [""],
            ["Christoph Becker"],
            ["cmb@example.com"],
            ["users"],
            ["activated"],
            ["secret"],
        ]);
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    /** @see <https://github.com/cmb69/register_xh/issues/65> */
    public function testHashesChangedPasswordEvenOnFailure()
    {
        $dbService = $this->createStub(DbService::class);
        $dbService->expects($this->never())->method('writeUsers');
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = $this->makeUserAdminController($dbService);
        $this->request->method("userAdminAction")->willReturn("do_update");
        $this->request->method("userAdminSubmission")->willReturn([
            [""],
            ["test"],
            ["\$2y\$10\$XABLZkU6kZKAJczIuTEJTesI5TF065Uta8LKFeFTxcYaXK72V3cyC"],
            ["Christoph Becker"],
            ["cmb@example.com"],
            ["users"],
            ["activated"],
            ["secret"],
        ]);
        $response = $sut($this->request);
        Approvals::verifyHtml($response->output());
    }

    private function makeUserAdminController(DbService $dbService): UserAdminController
    {
        $random = $this->createStub(Random::class);
        $random->method("bytes")->willReturnCallback(function ($length) {
            return substr("0123456789ABCDEF", 0, $length);
        });
        $password = $this->createStub(Password::class);
        $password->method("hash")->willReturn('$2y$10$XABLZkU6kZKAJczIuTEJTesI5TF065Uta8LKFeFTxcYaXK72V3cyC');
        return new UserAdminController(
            $this->makeConf(),
            $this->makeLang(),
            $this->makeCsrfProtector(false),
            $dbService,
            new View("./views/", self::makeLang()),
            $random,
            $password
        );
    }

    /** @return array<string,string> */
    private function makeConf(): array
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        return $plugin_cf['register'];
    }

    /** @return array<string,string> */
    private function makeLang(): array
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        return $plugin_tx['register'];
    }

    private function makeCsrfProtector(bool $used): CsrfProtector
    {
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $csrfProtector->method("tokenInput")->willReturn("");
        if ($used) {
            $csrfProtector->expects($this->once())->method('check');
        }
        return $csrfProtector;
    }

    private function makeJohn(): User
    {
        return new User(
            "john",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["guest"],
            "John Doe",
            "john@example.com",
            "activated",
            "secret",
        );
    }

    private function makeJane(): User
    {
        return new User(
            "jane",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated",
            "secret",
        );
    }
}
