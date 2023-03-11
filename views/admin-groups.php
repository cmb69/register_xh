<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var list<string> $groups
 * @var list<list<array{selected:bool,indent:string,url:string,heading:string}>> $selects
 * @var string $saveLabel
 */
?>

<h1><?=$this->text('mnu_group_admin')?></h1>
<div class="register_admin_main">
  <form method="post" action="<?=$actionUrl?>">
    <?=$csrfTokenInput?>
    <table>
      <tr>
        <th><?=$this->text('groupname')?></th>
        <th><?=$this->text('login')?></th>
        <th><button name="add" value="add"><?=$this->text("label_add")?></button></th>
      </tr>
<?php foreach ($groups as $i => $group):?>
      <tr>
        <td><input type="text" size="10" value="<?=$group?>" name="groupname[<?=($i)?>]"></td>
        <td>
          <select name="grouploginpage[<?=$i?>]">
            <option value=""><?=$this->text("label_none")?></option>
<?php   foreach ($selects[$i] as $options):?>
            <option value="<?=$options["url"]?>" <?=$options["selected"]?>><?=$options["indent"]?><?=$options["heading"]?></option>
<?php   endforeach?>
          </select>
        </td>
        <td><button name="delete[<?=$i?>]" value="1"><?=$this->text("label_delete")?></i></td>
      </tr>
<?php endforeach?>
    </table>
    <input class="submit" type="submit" value="<?=$this->text('label_save')?>" name="send">
  </form>
</div>
