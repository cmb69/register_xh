<h1><?=$this->text('mnu_user_admin')?></h1>
<div class="register_admin_main">
    <table>
        <tr id="register_user_template" style="display: none">
            <td><button title="<?=$this->text('user_delete')?>" onclick="register.removeRow(this); return false"><i class="fa fa-minus"></i></button></td>
            <td><input type="text" value="" name="name[]"></td>
            <td><input type="text" value="<?=$this->defaultGroup()?>" name="accessgroups[]"></td>
            <td><?=$this->statusSelectActivated()?></td>
        </tr>
        <tr style="display: none">
            <td></td>
            <td><input type="text" value="" name="username[]"></td>
            <td><input type="text" value="" name="email[]"></td>
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
<?php foreach ($this->groups as $group):?>
            <option value="<?=$this->escape($group['groupname'])?>"><?=$this->escape($group['groupname'])?></option>
<?php endforeach?>
        </select>
    </div>
    <form id="register_user_form" method="post" action="<?=$this->actionUrl()?>">
        <input type="hidden" value="saveusers" name="action">
        <input type="hidden" value="plugin_main" name="admin">
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
<?php foreach ($this->users as $i => $entry):?>
            <tr id="register_user_<?=$this->escape($i)?>">
                <td><button title="<?=$this->text('user_delete')?>" onclick="register.removeRow(this); return false"><i class="fa fa-minus"></i></button></td>
                <td><input type="text" value="<?=$this->escape($entry['name'])?>" name="name[<?=$this->escape($i)?>]"></td>
                <td><input type="text" value="<?=$this->escape($this->groupStrings[$i])?>" name="accessgroups[<?=$this->escape($i)?>]"></td>
                <td><?=$this->escape($this->statusSelects[$i])?></td>
            </tr>
            <tr class="register_second_row">
                <td><button type="button" onclick="register.mailTo(this)" title="<?=$this->text('email')?>"><i class="fa fa-envelope"></i></button></td>
                <td><input type="text" value="<?=$this->escape($entry['username'])?>" name="username[<?=$this->escape($i)?>]"></td>
                <td><input type="text" value="<?=$this->escape($entry['email'])?>" name="email[<?=$this->escape($i)?>]"></td>
                <td>
                    <button onclick="register.changePassword(this.nextElementSibling); return false"><?=$this->text('change_password')?></button>
                    <input type="hidden" value="<?=$this->escape($entry['password'])?>" name="password[<?=$this->escape($i)?>]">
                    <input type="hidden" value="<?=$this->escape($entry['password'])?>" name="oldpassword[<?=$this->escape($i)?>]">
                </td>
            </tr>
<?php endforeach?>
        </table>
        <input class="submit" type="submit" value="<?=$this->saveLabel()?>" name="send">
    </form>
</div>
<script type="text/javascript">register.init()</script>
