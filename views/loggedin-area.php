<div class="regi_loggedin_loggedinarea">
    <div class="regi_loggedin_user"><?=$this->text('loggedin_welcometext')?> <?=$this->fullName()?>!</div>
    <div class="regi_loggedin_loggedin"><?=$this->text('loggedin')?></div>
    <div class="regi_loggedin_settings">
<?php if ($this->hasUserPrefs):?>
        <a href="<?=$this->userPrefUrl()?>" title="<?=$this->text('user_prefs')?>">
            <?=$this->text('user_prefs')?>
        </a>
<?php endif?>
    </div>
    <div class="regi_loggedin_logout">
        <a href="<?=$this->logoutUrl()?>" title="<?=$this->text('logout')?>">
            <?=$this->text('logout')?>
        </a>
    </div>
    <div style="clear: both;"></div>
</div>
