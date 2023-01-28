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
  <form method="POST" action="<?=$this->esc($actionUrl)?>">
    <input type="hidden" value="savegroups" name="action">
    <input type="hidden" value="plugin_main" name="admin">
    <?=$this->esc($csrfTokenInput)?>
    <table>
      <tr>
        <th><?=$this->text('groupname')?></th>
        <th><?=$this->text('login')?></th>
        <th><button name="add[0]"><?=$this->text("label_add")?></button></th>
      </tr>
<?php foreach ($groups as $i => $entry):?>
      <tr>
        <td><input type="text" size="10" value="<?=$this->esc($entry->getGroupname())?>" name="groupname[<?=$this->esc($i)?>]"></td>
        <td><?=$this->esc($selects[$i])?></td>
        <td><button name="delete[<?=$this->esc($i)?>]" value="1"><?=$this->text("label_delete")?></i></td>
      </tr>
<?php endforeach?>
    </table>
    <input class="submit" type="submit" value="<?=$this->text('label_save')?>" name="send">
  </form>
</div>
