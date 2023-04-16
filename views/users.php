<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var list<array{checked:string,username:string,fullname:string,email:string,groups:string,status_label:string}> $users
 * @var string $username
 * @var string $name
 * @var string $email
 * @var list<array{string,string}> $groups
 * @var string $status
 */
?>
<!-- register users -->
<form method="get">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="selected" value="register">
  <input type="hidden" name="admin" value="users">
  <div class="register_table_wrapper">
    <table>
      <thead>
        <tr>
          <th><?=$this->text('username')?></th>
          <th><?=$this->text('name')?></th>
          <th><?=$this->text('email')?></th>
          <th><?=$this->text('accessgroups')?></th>
          <th><?=$this->text('status')?></th>
        </tr>
        <tr>
          <td><input type="search" name="username" value="<?=$username?>" placeholder="<?=$this->text('label_filter')?>"></td>
          <td><input type="search" name="name" value="<?=$name?>" placeholder="<?=$this->text('label_filter')?>"></td>
          <td><input type="search" name="email" value="<?=$email?>" placeholder="<?=$this->text('label_filter')?>"></td>
          <td>
            <select name="group" onchange="this.form.submit()">
<?foreach ($groups as [$group, $selected]):?>
              <option <?=$selected?>><?=$group?></option>
<?endforeach?>
            </select>
          </td>
          <td><input type="search" name="status" value="<?=$status?>" placeholder="<?=$this->text('label_filter')?>"></td>
        </tr>
      </thead>
      <tbody>
<?foreach ($users as $user):?>
        <tr>
          <td>
            <label><input type="radio" name="user" value="<?=$user['username']?>" <?=$user['checked']?>> <?=$user['username']?></label>
          </td>
          <td><?=$user['fullname']?></td>
          <td><?=$user['email']?></td>
          <td><?=$user['groups']?></td>
          <td><?=$this->text($user['status_label'])?></td>
        </tr>
<?endforeach?>
      </tbody>
    </table>
  </div>
  <p class="register_buttons">
    <button><?=$this->text('label_refresh')?></button>
    <button name="action" value="create"><?=$this->text('label_new')?></button>
    <button name="action" value="update"><?=$this->text('label_edit')?></button>
    <button name="action" value="delete"><?=$this->text('label_delete')?></button>
    <button name="action" value="change_password"><?=$this->text('change_password')?></button>
    <button name="action" value="mail"><?=$this->text('label_mail')?></button>
  </p>
</form>
