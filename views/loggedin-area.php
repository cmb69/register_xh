<?php if ($this->isHorizontal):?>
<div class="regi_loggedin_loggedinarea_hor">
    <div class="regi_loggedin_user_hor"><?=$this->text('loggedin_welcometext')?> <?=$this->fullName()?>!</div>
<?php else:?>
<div class="regi_loggedin_loggedinarea_ver">
    <div class="regi_loggedin_user_ver"><?=$this->text('loggedin_welcometext')?> <?=$this->fullName()?>!</div>
<?php endif?>
<?php if ($this->isHorizontal):?>
    <div class="regi_loggedin_loggedin_hor"><?=$this->text('loggedin')?></div>
<?php else:?>
    <div class="regi_loggedin_loggedin_ver"><?=$this->text('loggedin')?></div>
<?php endif?>
<?php if ($this->isHorizontal):?>
    <div class="regi_loggedin_settings_hor">
<?php else:?>
    <div class="regi_loggedin_settings_ver">
<?php endif?>
<?php if ($this->hasUserPrefs):?>
        <a href="<?=$this->userPrefUrl()?>">
            <img class="regi_settingsimage" src="<?=$this->userPrefIcon?>" alt="<?=$this->text('user_prefs')?>"><?=$this->text('user_prefs')?>
        </a>
<?php endif?>
    </div>
<?php if ($this->isHorizontal):?>
    <div class="regi_loggedin_logout_hor">
<?php else:?>
    <div class="regi_loggedin_logout_ver">
<?php endif?>
        <a href="<?=$this->logoutUrl()?>">
            <img class="regi_logoutimage" src="<?=$this->logoutIcon()?>" alt="<?=$this->text('logout')?>"><?=$this->text('logout')?>
        </a>
    </div>
    <div style="clear: both;"></div>
</div>
