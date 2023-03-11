<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $defaultGroup
 * @var string $statusSelectActivated
 * @var list<string> $groups
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var list<array{username:string,password:string,name:string,email:string,secret:string}> $users
 * @var array<int,string> $groupStrings
 * @var array<int,string> $statusSelects
 */
?>
<!-- register user administration -->
<h1><?=$this->text('mnu_user_admin')?></h1>
<div class="register_admin_main">
  <table>
    <tr id="register_user_template" style="display: none">
      <td><button title="<?=$this->text('user_delete')?>" onclick="register.removeRow(this); return false"><?=$this->text("label_delete")?></button></td>
      <td><input type="text" value="" name="name[]"></td>
      <td><input type="text" value="<?=$defaultGroup?>" name="accessgroups[]"></td>
      <td><?=$statusSelectActivated?></td>
    </tr>
    <tr style="display: none">
      <td></td>
      <td><input type="text" value="" name="username[]"></td>
      <td><input type="email" value="" name="email[]"></td>
      <td>
        <button onclick="register.changePassword(this.nextElementSibling); return false"><?=$this->text('change_password')?></button>
        <input type="hidden" value="" name="password[]">
        <input type="hidden" value="" name="oldpassword[]">
        <input type="hidden" value="" name="secrets[]">
      </td>
    </tr>
  </table>
  <div>
    <button onclick="register.addRow()"><?=$this->text('user_add')?></button>
    <input id="register_toggle_details" type="checkbox" onclick="register.toggleDetails()" style="padding-left: 1em">
    <label for="register_toggle_details"><?=$this->text('details')?></label>
    <select id="register_group_selectbox" title="<?=$this->text('filter_group')?>">
      <option value=""><?=$this->text('all')?></option>
<?foreach ($groups as $group):?>
      <option value="<?=$group?>"><?=$group?></option>
<?endforeach?>
    </select>
  </div>
  <form id="register_user_form" method="post" action="<?=$actionUrl?>">
    <?=$csrfTokenInput?>
    <table id="register_user_table">
      <tr>
        <th></th>
        <th class="register_sort" onclick="register.sort(this, 'name')" style="cursor: pointer"><?=$this->text('name')?></th>
        <th class="register_sort" onclick="register.sort(this, 'accessgroups')" style="cursor: pointer"><?=$this->text('accessgroups')?></th>
        <th class="register_sort" onclick="register.sort(this, 'status')" style="cursor: pointer"><?=$this->text('status')?></th>
      </tr>
      <tr class="register_second_row">
        <th></th>
        <th class="register_sort" onclick="register.sort(this, 'username')" style="cursor: pointer"><?=$this->text('username')?></th>
        <th class="register_sort" onclick="register.sort(this, 'email')" style="cursor: pointer"><?=$this->text('email')?></th>
        <th><?=$this->text('password')?></th>
      </tr>
<?foreach ($users as $i => $user):?>
      <tr id="register_user_<?=$i?>">
        <td><button title="<?=$this->text('user_delete')?>" onclick="register.removeRow(this); return false"><?=$this->text("label_delete")?></button></td>
        <td><input type="text" value="<?=$user['name']?>" name="name[<?=$i?>]"></td>
        <td><input type="text" value="<?=$groupStrings[$i]?>" name="accessgroups[<?=$i?>]"></td>
        <td><?=$statusSelects[$i]?></td>
      </tr>
      <tr class="register_second_row">
        <td><button type="button" onclick="register.mailTo(this)" title="<?=$this->text('email')?>"><?=$this->text("label_mail")?></i></button></td>
        <td><input type="text" value="<?=$user['username']?>" name="username[<?=$i?>]"></td>
        <td><input type="email" value="<?=$user['email']?>" name="email[<?=$i?>]"></td>
        <td>
          <button onclick="register.changePassword(this.nextElementSibling); return false"><?=$this->text('change_password')?></button>
          <input type="hidden" value="<?=$user['password']?>" name="password[<?=$i?>]">
          <input type="hidden" value="<?=$user['password']?>" name="oldpassword[<?=$i?>]">
          <input type="hidden" value="<?=$user['secret']?>" name="secrets[<?=$i?>]">
        </td>
      </tr>
<?endforeach?>
    </table>
    <button class="submit" value="save" name="send"><?=$this->text('label_save')?></button>
  </form>
</div>
<script type="text/javascript">register.init()</script>
