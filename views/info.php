<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $version
 * @var array<int,array{state:string,label:string,stateLabel:string}> $checks
 */
?>
<!-- register plugin info -->
<h1>Register_XH <?=$version?></h1>
<div class="register_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="xh_<?=$check["state"]?>"><?=$this->text('syscheck_message', $check["label"], $check["stateLabel"])?></p>
<?endforeach?>
</div>
