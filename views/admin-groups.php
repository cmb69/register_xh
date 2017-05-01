<h1><?=$this->text('mnu_group_admin')?></h1>
<div class="register_admin_main">
    <form method="POST" action="<?=$this->actionUrl()?>">
        <input type="hidden" value="savegroups" name="action">
        <input type="hidden" value="plugin_main" name="admin">
        <?=$this->csrfTokenInput()?>
        <table>
            <tr>
                <th><?=$this->text('groupname')?></th>
                <th><?=$this->text('login')?></th>
                <th><button name="add[0]"><i class="fa fa-plus"></i></button></th>
            </tr>
<?php foreach ($this->groups as $i => $entry):?>
            <tr>
                <td><input type="text" size="10" value="<?=$this->escape($entry->groupname)?>" name="groupname[<?=$this->escape($i)?>]"></td>
                <td><?=$this->escape($this->selects[$i])?></td>
                <td><button name="delete[<?=$this->escape($i)?>]" value="1"><i class="fa fa-minus"></i></td>
            </tr>
<?php endforeach?>
        </table>
        <input class="submit" type="submit" value="<?=$this->saveLabel()?>" name="send">
    </form>
</div>
