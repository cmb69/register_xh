<?php

use Register\Value\User;
use Register\Value\UserGroup;
use Register\Infra\View;

/**
 * @var View $this
 * @var string $defaultGroup
 * @var string $statusSelectActivated
 * @var array<int,UserGroup> $groups
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var array<int,User> $users
 * @var array<int,string> $groupStrings
 * @var array<int,string> $statusSelects
 */
?>

<h1><?=$this->text('mnu_user_admin')?></h1>
<div class="register_admin_main">
  <table>
    <tr id="register_user_template" style="display: none">
      <td><button title="<?=$this->text('user_delete')?>" onclick="register.removeRow(this); return false"><?=$this->text("label_delete")?></button></td>
      <td><input type="text" value="" name="name[]"></td>
      <td><input type="text" value="<?=$this->esc($defaultGroup)?>" name="accessgroups[]"></td>
      <td><?=$this->esc($statusSelectActivated)?></td>
    </tr>
    <tr style="display: none">
      <td></td>
      <td><input type="text" value="" name="username[]"></td>
      <td><input type="email" value="" name="email[]"></td>
      <td>
        <button onclick="register.changePassword(this.nextElementSibling); return false"><?=$this->text('change_password')?></button>
        <input type="hidden" value="" name="password[]">
        <input type="hidden" value="" name="oldpassword[]">
      </td>
    </tr>
  </table>
  <div>
    <button onclick="register.addRow()"><?=$this->text('user_add')?></button>
    <input id="register_toggle_details" type="checkbox" onclick="register.toggleDetails()" style="padding-left: 1em">
    <label for="register_toggle_details"><?=$this->text('details')?></label>
    <select id="register_group_selectbox" title="<?=$this->text('filter_group')?>">
      <option value=""><?=$this->text('all')?></option>
<?php foreach ($groups as $group):?>
      <option value="<?=$this->esc($group->getGroupname())?>"><?=$this->esc($group->getGroupname())?></option>
<?php endforeach?>
    </select>
  </div>
  <form id="register_user_form" method="post" action="<?=$this->esc($actionUrl)?>">
    <?=$this->esc($csrfTokenInput)?>
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
<?php foreach ($users as $i => $entry):?>
      <tr id="register_user_<?=$this->esc($i)?>">
        <td><button title="<?=$this->text('user_delete')?>" onclick="register.removeRow(this); return false"><?=$this->text("label_delete")?></button></td>
        <td><input type="text" value="<?=$this->esc($entry->getName())?>" name="name[<?=$this->esc($i)?>]"></td>
        <td><input type="text" value="<?=$this->esc($groupStrings[$i])?>" name="accessgroups[<?=$this->esc($i)?>]"></td>
        <td><?=$this->esc($statusSelects[$i])?></td>
      </tr>
      <tr class="register_second_row">
        <td><button type="button" onclick="register.mailTo(this)" title="<?=$this->text('email')?>"><?=$this->text("label_mail")?></i></button></td>
        <td><input type="text" value="<?=$this->esc($entry->getUsername())?>" name="username[<?=$this->esc($i)?>]"></td>
        <td><input type="email" value="<?=$this->esc($entry->getEmail())?>" name="email[<?=$this->esc($i)?>]"></td>
        <td>
          <button onclick="register.changePassword(this.nextElementSibling); return false"><?=$this->text('change_password')?></button>
          <input type="hidden" value="<?=$this->esc($entry->getPassword())?>" name="password[<?=$this->esc($i)?>]">
          <input type="hidden" value="<?=$this->esc($entry->getPassword())?>" name="oldpassword[<?=$this->esc($i)?>]">
        </td>
      </tr>
<?php endforeach?>
    </table>
    <input class="submit" type="submit" value="<?=$this->text('label_save')?>" name="send">
  </form>
</div>
<script type="text/javascript">register.init()</script>
