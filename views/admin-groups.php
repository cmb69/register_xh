<?php

use Register\Value\UserGroup;
use Register\Infra\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var array<int,UserGroup> $groups
 * @var list<list<array{selected:bool,indent:string,url:string,heading:string}>> $selects
 * @var string $saveLabel
 */
?>

<h1><?=$this->text('mnu_group_admin')?></h1>
<div class="register_admin_main">
  <form method="post" action="<?=$this->esc($actionUrl)?>">
    <?=$this->esc($csrfTokenInput)?>
    <table>
      <tr>
        <th><?=$this->text('groupname')?></th>
        <th><?=$this->text('login')?></th>
        <th><button name="add" value="add"><?=$this->text("label_add")?></button></th>
      </tr>
<?php foreach ($groups as $i => $entry):?>
      <tr>
        <td><input type="text" size="10" value="<?=$this->esc($entry->getGroupname())?>" name="groupname[<?=$this->esc($i)?>]"></td>
        <td>
          <select name="grouploginpage[<?=$this->esc($i)?>]">
            <option value=""><?=$this->text("label_none")?></option>
<?php   foreach ($selects[$i] as $options):?>
            <option value="<?=$this->esc($options["url"])?>" <?=$this->esc($options["selected"])?>><?=$this->esc($options["indent"])?><?=$this->raw($options["heading"])?></option>
<?php   endforeach?>
          </select>
        </td>
        <td><button name="delete[<?=$this->esc($i)?>]" value="1"><?=$this->text("label_delete")?></i></td>
      </tr>
<?php endforeach?>
    </table>
    <input class="submit" type="submit" value="<?=$this->text('label_save')?>" name="send">
  </form>
</div>
