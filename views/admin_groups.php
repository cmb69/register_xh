<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var list<string> $groups
 * @var list<list<array{selected:bool,indent:string,url:string,heading:string}>> $selects
 * @var string $saveLabel
 */
?>
<!-- register group administration -->
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
<?foreach ($groups as $i => $group):?>
      <tr>
        <td><input type="text" size="10" value="<?=$group?>" name="groupname[<?=($i)?>]"></td>
        <td>
          <select name="grouploginpage[<?=$i?>]">
            <option value=""><?=$this->text("label_none")?></option>
<?  foreach ($selects[$i] as $options):?>
            <option value="<?=$options["url"]?>" <?=$options["selected"]?>><?=$options["indent"]?><?=$options["heading"]?></option>
<?  endforeach?>
          </select>
        </td>
        <td><button name="delete[<?=$i?>]" value="1"><?=$this->text("label_delete")?></i></td>
      </tr>
<?endforeach?>
    </table>
    <button class="submit" value="save" name="send"><?=$this->text('label_save')?></button>
  </form>
</div>
