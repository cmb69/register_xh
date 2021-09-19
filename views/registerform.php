<?php

use Register\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $name
 * @var string $username
 * @var string $password1
 * @var string $password2
 * @var string $email
 */
?>

<form method="post" action="<?=$this->esc($actionUrl)?>" target="_self">
    <div class="regi_register">
        <table>
            <tr>
                <td>
                    <input type="hidden" name="action" value="register_user">
                    <?=$this->text('name')?>
                </td>
                <td colspan="2"><input class="text" name="name" type="text" size="35" value="<?=$this->esc($name)?>"></td>
            </tr>
            <tr>
                <td><?=$this->text('username')?></td>
                <td colspan="2"><input class="text" name="username" type="text" size="10" value="<?=$this->esc($username)?>"></td>
            </tr>
            <tr>
                <td><?=$this->text('password')?></td>
                <td colspan="2"><input class="text" name="password1" type="password" size="10" value="<?=$this->esc($password1)?>"></td>
            </tr>
            <tr>
                <td><?=$this->text('password2')?></td>
                <td colspan="2"><input class="text" name="password2" type="password" size="10" value="<?=$this->esc($password2)?>"></td>
            </tr>
            <tr>
                <td><?=$this->text('email')?></td>
                <td colspan="2"><input class="text" name="email" type="text" size="35" value="<?=$this->esc($email)?>"></td>
            </tr>
            <tr>
                <td colspan="3"><input class="submit" type="submit" value="<?=$this->text('register')?>"></td>
            </tr>
        </table>
        <p><?=$this->text('register_form2')?></p>
    </div>
</form>
