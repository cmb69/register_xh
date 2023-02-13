<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var array<int,array{state:string,label:string,stateLabel:string}> $checks
 */
?>

<h1>Register_XH <?=$this->esc($version)?></h1>
<div class="register_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?php foreach ($checks as $check):?>
  <p class="xh_<?=$this->esc($check["state"])?>"><?=$this->text('syscheck_message', $check["label"], $check["stateLabel"])?></p>
<?php endforeach?>
</div>
