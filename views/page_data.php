<?php

use Register\View;

/**
 * @var View $this
 * @var string $action
 * @var string $helpIcon
 * @var string $accessGroups
 */
?>

<form action="<?=$this->esc($action)?>" method="post" id="register">
  <p>
    <?=$this->esc($helpIcon)?>
    <label>
      <?=$this->text("accessgroups")?><br/>
      <input name="register_access" value="<?=$this->esc($accessGroups)?>">
    </label>
  </p>
  <input name="save_page_data" type="hidden">
  <p>
    <button><?=$this->text("label_submit")?></button>
  </p>
</form>
