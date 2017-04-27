<h1><?=$this->text('mnu_group_admin')?></h1>
<div class="register_admin_main">
    <form method="POST" action="<?=$this->actionUrl()?>">
        <input type="hidden" value="savegroups" name="action">
        <input type="hidden" value="plugin_main" name="admin">
        <table>
            <tr>
                <th><?=$this->text('groupname')?></th>
                <th><?=$this->text('login')?></th>
                <th><input type="image" src="<?=$this->addIcon()?>" style="width: 16px; height: 16px;" name="add[0]" alt="Add entry."></th>
            </tr>
<?php foreach ($this->groups as $i => $entry):?>
            <tr>
                <td><input type="text" size="10" value="<?=$this->escape($entry['groupname'])?>" name="groupname[<?=$this->escape($i)?>]"></td>
                <td><?=$this->escape($this->selects[$i])?></td>
                <td><input type="image" src="<?=$this->deleteIcon()?>" style="width: 16px; height: 16px;" name="delete[<?=$this->escape($i)?>]" alt="Delete Entry"></td>
            </tr>
<?php endforeach?>
        </table>
        <input class="submit" type="submit" value="<?=$this->saveLabel()?>" name="send">
    </form>
</div>
