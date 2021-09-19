<?php

use Register\UserGroup;
use Register\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var array<int,UserGroup> $groups
 * @var array<int,string> $selects
 * @var string $saveLabel
 */
?>

<h1><?=$this->text('mnu_group_admin')?></h1>
<div class="register_admin_main">
    <form method="POST" action="<?=$this->escape($actionUrl)?>">
        <input type="hidden" value="savegroups" name="action">
        <input type="hidden" value="plugin_main" name="admin">
        <?=$this->escape($csrfTokenInput)?>
        <table>
            <tr>
                <th><?=$this->text('groupname')?></th>
                <th><?=$this->text('login')?></th>
                <th><button name="add[0]"><?=$this->text("label_add")?></button></th>
            </tr>
<?php foreach ($groups as $i => $entry):?>
            <tr>
                <td><input type="text" size="10" value="<?=$this->escape($entry->groupname)?>" name="groupname[<?=$this->escape($i)?>]"></td>
                <td><?=$this->escape($selects[$i])?></td>
                <td><button name="delete[<?=$this->escape($i)?>]" value="1"><?=$this->text("label_delete")?></i></td>
            </tr>
<?php endforeach?>
        </table>
        <input class="submit" type="submit" value="<?=$this->escape($saveLabel)?>" name="send">
    </form>
</div>
