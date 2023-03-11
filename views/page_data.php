<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $action
 * @var string $iconFilename
 * @var string $iconAlt
 * @var string $accessGroups
 */
?>

<form action="<?=$action?>" method="post" id="register">
  <p>
    <div class="pl_tooltip">
      <img src="<?=$iconFilename?>" alt="<?=$iconAlt?>">
      <div><?=$this->text("hint_accessgroups")?></div>
    </div>
    <label>
      <?=$this->text("accessgroups")?><br/>
      <input name="register_access" value="<?=$accessGroups?>">
    </label>
  </p>
  <input name="save_page_data" type="hidden">
  <p>
    <button><?=$this->text("label_submit")?></button>
  </p>
</form>
