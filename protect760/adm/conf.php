<?php
// форма редактирования конфига
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Edit Config');

if ($ab_config['timezone'] == '' AND date_default_timezone_get() != '') {
$ab_config['timezone'] = date_default_timezone_get();
}

include(__DIR__.'/../data/conf.php');
//@include(__DIR__.'/../data/disable.php');
//$ab_config['salt'] = $ab_config['subsalt'].$ab_config['salt'];

// альтернативные php обработчики:
$update_alternatives_info = '';
if (is_shell_exec_available()) {
$update_alternatives = @trim(shell_exec('update-alternatives --list php'));
if (preg_match('/(Red Hat|Fedora|CentOS|RHEL)/i', $update_alternatives)) {
$update_alternatives = @trim(shell_exec('alternatives --list | grep php'));
}
if ($update_alternatives != '') {
$update_alternatives = explode(PHP_EOL, $update_alternatives);
$update_alternatives_info = abTranslate('Alternative list of PHP handlers:').' <code>'.implode('</code> '.abTranslate('or').' <code>', $update_alternatives).'</code><br />';
}
}

$ab_config['periods_cleaning'] = array('lastday', 'lastweek', 'lastmonth', 'quarter', 'lastyear');
if (!in_array($ab_config['period_cleaning'], $ab_config['periods_cleaning'])) {$ab_config['period_cleaning'] = 'lastmonth';}

$content .= '
<form action="?'.$abw.$abp.'=confsave" method="post" autocomplete="off">
  <div class="form-group row">
    <label for="disable" class="col-sm-2 col-form-label">'.abTranslate('Script Status').'</label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="disable" id="disable1" value="1" '.(($ab_config['disable'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="disable1"><span class="text-danger">'.abTranslate('Disabled, Not in Use').'</span> <small class="text-muted">('.abTranslate('If you need to temporarily disable without deleting the connection code').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="disable" id="disable0" value="0" '.(($ab_config['disable'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="disable0"><span class="text-success">'.abTranslate('Enabled, Running').'</span></label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="disable" class="col-sm-2 col-form-label">'.abTranslate('Working Hours').'</label>
    <div class="col-sm-10">
';
for ($i = 0; $i < 24; $i++) {
$number = str_pad($i, 2, '0', STR_PAD_LEFT);  
$content .= '
<div class="form-check form-check-inline">
  <input name="wh[]" class="form-check-input" type="checkbox" id="wh'.$number.'" value="'.$number.'" '.(in_array($number, $ab_config['wh']) ? 'checked' : '').'>
  <label class="form-check-label" for="wh'.$number.'">'.$number.'</label>
</div>
';
}
$content .= '
<small class="text-muted">('.abTranslate('Enabling protection only during specified hours. Current hour number:').' '.str_pad(date('H', $ab_config['time']), 2, '0', STR_PAD_LEFT).').</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('PHP errors').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="phperror" id="phperror3" value="3" '.(($ab_config['phperror'] == '3') ? 'checked' : '').'>
  <label class="form-check-label" for="phperror3">'.abTranslate('Don\'t change the system error log settings').'.</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="phperror" id="phperror2" value="2" '.(($ab_config['phperror'] == '2') ? 'checked' : '').'>
  <label class="form-check-label" for="phperror2">'.abTranslate('Enable display in the browser, write to the log').' <small class="text-muted">('.abTranslate('For debugging').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="phperror" id="phperror1" value="1" '.(($ab_config['phperror'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="phperror1">'.abTranslate('Disable display in the browser, write to the log').' <small class="text-muted">('.abTranslate('Recommended').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="phperror" id="phperror0" value="0" '.(($ab_config['phperror'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="phperror0">'.abTranslate('Disable display in the browser, disable error log').'.</label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Check Service').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="check" id="check1" value="1" '.(($ab_config['check'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="check1">'.abTranslate('Cloud Check').' <small class="text-muted">('.abTranslate('Maximum website protection and convenience for visitors').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="check" id="check0" value="0" '.(($ab_config['check'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="check0">'.abTranslate('Local Check').' <small class="text-muted">('.abTranslate('It will automatically let through everyone who has JS support in their browser').').</small></label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Cloud Check').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="cloud_rus" id="cloud_url_main" value="0" '.(($ab_config['cloud_rus'] != 1) ? 'checked' : '').'>
  <label class="form-check-label" for="cloud_url_main">'.abTranslate('Server in Europe').'</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="cloud_rus" id="cloud_url_rus" value="1" '.(($ab_config['cloud_rus'] == 1) ? 'checked' : '').'>
  <label class="form-check-label" for="cloud_url_rus">'.abTranslate('Server in Russia').'</label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Cloud Unresponsive').'</label>
    <div class="col-sm-10">
<small class="text-muted">'.abTranslate('What to do if the Cloud Check did not respond in time?').'</small>
<div class="form-check">
  <input class="form-check-input" type="radio" name="unresponsive" id="unresponsive1" value="1" '.(($ab_config['unresponsive'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="unresponsive1">'.abTranslate('Stop').' <small class="text-muted">('.abTranslate('Display the login buttons, the visitor will have to pass a captcha').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="unresponsive" id="unresponsive0" value="0" '.(($ab_config['unresponsive'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="unresponsive0">'.abTranslate('Skip').' <small class="text-muted">('.abTranslate('Allow the visitor to pass automatically, applying only local rules').').</small></label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="webdir" class="col-sm-2 col-form-label">'.abTranslate('Web directory').'</label>
    <div class="col-sm-10">
      <input name="webdir" type="text" class="form-control" id="webdir" value="'.$ab_config['webdir'].'">
      <small class="form-text text-muted">'.abTranslate('AntiBot web directory, path from website root or with protocol and domain. Example: <code>/nobotex9/</code> or <code>https://yoursite.com/nobotex9/</code>').'</small>
    </div>
  </div>

  <div class="form-group row">
    <label for="ab_url" class="col-sm-2 col-form-label">'.abTranslate('Check URL').'</label>
    <div class="col-sm-10">
      <input name="ab_url" type="text" class="form-control" id="ab_url" value="'.$ab_config['ab_url'].'">
      <small class="form-text text-muted"><a href="https://'.$ab_config['main_url'].'/FAQ/check-ab-url.html" target="_blank" rel="noopener">'.abTranslate('Read the manual before editing this parameter.').' <i class="bi bi-box-arrow-up-right"></i></a></small>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label for="cookie" class="col-sm-2 col-form-label">'.abTranslate('Main Cookie Name').'</label>
    <div class="col-sm-10">
      <input name="cookie" type="text" class="form-control" id="cookie" value="'.$ab_config['cookie'].'">
      <small class="form-text text-muted">'.abTranslate('Name of the main Cookie. Allowed characters: A-Za-z').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Authorization Storage').'</label>
    <div class="col-sm-10">
<small class="text-muted">'.abTranslate('Authorization Storage Location After anti-bot check:').'</small>
<div class="form-check">
  <input class="form-check-input" type="radio" name="auth" id="auth_sqlite" value="sqlite" '.(($ab_config['auth'] == 'sqlite') ? 'checked' : '').'>
  <label class="form-check-label" for="auth_sqlite">'.abTranslate('In SQLite Database').' <small class="text-muted">('.abTranslate('Convenient for Using Multiple Interlinked Sites on a Single anti-bot Database').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="auth" id="auth_cookie" value="cookie" '.(($ab_config['auth'] == 'cookie') ? 'checked' : '').'>
  <label class="form-check-label" for="auth_cookie">'.abTranslate('In Cookies').' <small class="text-muted">('.abTranslate('Separate Cookies for Each Host').').</small></label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="js_error_msg" class="col-sm-2 col-form-label">'.abTranslate('JS Error Msg').'</label>
    <div class="col-sm-10">
      <input name="js_error_msg" type="text" class="form-control" id="js_error_msg" value="'.$ab_config['js_error_msg'].'">
      <small class="form-text text-muted">'.abTranslate('The message text that "Your request is blocked", when blocking due to JS parameters (for example, by TimeZone).').'</small>
    </div>
  </div>

<hr />
 
  <div class="form-group row">
    <label for="timezone" class="col-sm-2 col-form-label">'.abTranslate('Timezone').'</label>
    <div class="col-sm-10">
      <input name="timezone" type="text" class="form-control" id="timezone" value="'.$ab_config['timezone'].'">
      <small class="form-text text-muted"><a href="https://www.php.net/manual/'.$lang_code.'/timezones.php" target="_blank" rel="noopener">'.abTranslate('List of Supported Timezones').' <i class="bi bi-box-arrow-up-right"></i></a></small>
    </div>
  </div>

<hr />
  
  <div class="form-group row">
    <label for="email" class="col-sm-2 col-form-label">'.abTranslate('Email (Login)').'</label>
    <div class="col-sm-10">
      <input name="email" type="text" class="form-control" id="email" value="'.$ab_config['email'].'">
      <small class="form-text text-muted">'.abTranslate('Email registered on the website:').' <a href="https://'.$ab_config['main_url'].'/" target="_blank" rel="noopener">AntiBot.Cloud <i class="bi bi-box-arrow-up-right"></i></a></small>
    </div>
  </div>
  
  <div class="form-group row">
    <label for="pass" class="col-sm-2 col-form-label">'.abTranslate('Password').'</label>
    <div class="col-sm-10">
      <input name="pass" type="password" class="form-control" id="pass" value="'.$ab_config['pass'].'">
      <small class="form-text text-muted">'.abTranslate('The password is also from the nobotex.org website.').'</small>
    </div>
  </div>
<a name="secondpass"></a>
  <div class="form-group row">
    <label for="secondpass" class="col-sm-2 col-form-label">'.abTranslate('Second Password').'</label>
    <div class="col-sm-10">
      <input name="secondpass" type="text" class="form-control" id="secondpass" value="'.(($ab_config['secondpass'] != '') ? '**********' : '').'">
      <small class="form-text text-muted">'.abTranslate('Additional password for accessing the AntiBot admin panel. If you forget it, you can log in via FTP and clear the <code>secondpass</code> variable in the <code>data/conf.php</code> configuration file.').'</small>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Salt and Cookies').'</label>
    <div class="col-sm-10">
      <div class="form-check">
        <input name="newsalt" class="form-check-input" type="checkbox" id="newsalt">
        <label class="form-check-label" for="newsalt">'.abTranslate('Generate new salt').' <small class="text-muted">'.abTranslate('To reset cookies for all visitors, they will have to pass the anti-bot check again.').'</small></label>
      </div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="timesalt" id="timesalt_Y" value="Y" '.(($ab_config['timesalt'] == 'Y') ? 'checked' : '').'>
  <label class="form-check-label" for="timesalt_Y">'.abTranslate('without resetting cookies').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="timesalt" id="timesalt_Yz" value="Yz" '.(($ab_config['timesalt'] == 'Yz') ? 'checked' : '').'>
  <label class="form-check-label" for="timesalt_Yz">'.abTranslate('every new day').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="timesalt" id="timesalt_YW" value="YW" '.(($ab_config['timesalt'] == 'YW') ? 'checked' : '').'>
  <label class="form-check-label" for="timesalt_YW">'.abTranslate('every new week').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('No reset - cookies live for 10 days if the visitor\'s IP and User-Agent do not change.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label"><a href="https://'.$ab_config['main_url'].'/FAQ/samesite.html" target="_blank" rel="noopener">'.abTranslate('Cookie SameSite').' <i class="bi bi-box-arrow-up-right"></i></a></label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="samesite" id="samesite2" value="Lax" '.(($ab_config['samesite'] == 'Lax') ? 'checked' : '').'>
  <label class="form-check-label" for="samesite2">Lax <small class="text-muted">('.abTranslate('Recommended').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="samesite" id="samesite1" value="Strict" '.(($ab_config['samesite'] == 'Strict') ? 'checked' : '').'>
  <label class="form-check-label" for="samesite1">Strict</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="samesite" id="samesite0" value="None" '.(($ab_config['samesite'] == 'None') ? 'checked' : '').'>
  <label class="form-check-label" for="samesite0">None <small class="text-muted">('.abTranslate('Allows the passage of anti-bot checks in an iframe. The website must operate via the HTTPS protocol.').').</small></label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="is_bitrix" class="col-sm-2 col-form-label">'.abTranslate('Is it Bitrix CMS?').'</label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="is_bitrix" id="is_bitrix1" value="1" '.(($ab_config['is_bitrix'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="is_bitrix1">'.abTranslate('Website on Bitrix CMS and a looped redirect appears on the check page').'</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="is_bitrix" id="is_bitrix0" value="0" '.(($ab_config['is_bitrix'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="is_bitrix0">'.abTranslate('There is no specified problem or it is not Bitrix CMS').'</label>
</div>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="hits_per_user" class="col-sm-2 col-form-label">'.abTranslate('Views').'</label>
    <div class="col-sm-10">
      <input name="hits_per_user" type="text" class="form-control" id="hits_per_user" value="'.$ab_config['hits_per_user'].'">
      <small class="form-text text-muted">'.abTranslate('The number of page views by a visitor on the website after which to re-issue the anti-bot check.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="tpl_lang" class="col-sm-2 col-form-label">'.abTranslate('Check page lang').'</label>
    <div class="col-sm-10">
      <input name="tpl_lang" type="text" class="form-control" id="tpl_lang" value="'.$ab_config['tpl_lang'].'">
      <small class="form-text text-muted">'.abTranslate('The language of the check page (e.g. ru). If not specified, the language of the text is determined by the browser language. Languages from the directory lang/tpl/').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="input_button" class="col-sm-2 col-form-label">'.abTranslate('Login button').'</label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="input_button" id="input_button1" value="1" '.(($ab_config['input_button'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="input_button1">'.abTranslate('Disable buttons').' <small class="text-muted">('.abTranslate('If the visitor does not pass the automatic check, he will not get to the website at all.').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="input_button" id="input_button0" value="0" '.(($ab_config['input_button'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="input_button0">'.abTranslate('Do not disable buttons').'</label>
</div>
    </div>
  </div>

  <div class="form-group row">
    <label for="buttons" class="col-sm-2 col-form-label">'.abTranslate('Login button type').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="buttons" id="buttons5" value="5" '.(($ab_config['buttons'] == '5') ? 'checked' : '').'>
  <label class="form-check-label" for="buttons5">5) '.abTranslate('Random captcha output #').' 0, 1, 2. </label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="buttons" id="buttons4" value="4" '.(($ab_config['buttons'] == '4') ? 'checked' : '').'>
  <label class="form-check-label" for="buttons4">4) '.abTranslate('ReCAPTCHA v2').' (<a href="#re2">'.abTranslate('You need to specify the keys for ReCAPTCHA v2').'</a>)</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="buttons" id="buttons3" value="3" '.(($ab_config['buttons'] == '3') ? 'checked' : '').'>
  <label class="form-check-label" for="buttons3">3) '.abTranslate('ReCAPTCHA v2 + button "Go to website"').' (<a href="#re2">'.abTranslate('You need to specify the keys for ReCAPTCHA v2').'</a>)</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="buttons" id="buttons2" value="2" '.(($ab_config['buttons'] == '2') ? 'checked' : '').'>
  <label class="form-check-label" for="buttons2">2) '.abTranslate('Multiple buttons with similar IMAGE selection').'</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="buttons" id="buttons1" value="1" '.(($ab_config['buttons'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="buttons1">1) '.abTranslate('Multiple buttons with similar COLOR selection').'</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="buttons" id="buttons0" value="0" '.(($ab_config['buttons'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="buttons0">0) '.abTranslate('One big button "I\'m not a robot"').'</label>
</div>
    </div>
  </div>

  <div class="form-group row">
    <label for="time_ban" class="col-sm-2 col-form-label">'.abTranslate('IP blocking period').'</label>
    <div class="col-sm-10">
      <input name="time_ban" type="text" class="form-control" id="time_ban" value="'.$ab_config['time_ban'].'">
      <small class="form-text text-muted">'.abTranslate('The blocking period for IP addresses of visitors who clicked on the wrong button for the first time in a day. If it\'s zero, then it\'s blocked for 10 seconds. Format: hours.minutes (example: 12 - 12 hours, 0.10 - 10 minutes, 1.1 - 1 hour and 1 minute).').'</small>
    </div>
  </div>

  <div class="form-group row">
    <label for="time_ban_2" class="col-sm-2 col-form-label">'.abTranslate('IP blocking period').' 2</label>
    <div class="col-sm-10">
      <input name="time_ban_2" type="text" class="form-control" id="time_ban_2" value="'.$ab_config['time_ban_2'].'">
      <small class="form-text text-muted">'.abTranslate('Duration of IP block for the second and subsequent erroneous clicks within the last 24 hours. The format is similar to the blocking period for the first click.').'</small>
    </div>
  </div>

<hr id="re2" />

  <div class="form-group row">
    <label for="re_check" class="col-sm-2 col-form-label"><a href="https://www.google.com/recaptcha/admin/" target="_blank" rel="noopener">ReCAPTCHA v2 <i class="bi bi-box-arrow-up-right"></i></a></label>
    <div class="col-sm-10">
'.abTranslate('Specify if "Login Button Type" was selected with ReCAPTCHA v2.').'
    </div>
  </div>

  <div class="form-group row">
    <label for="recaptcha_key2" class="col-sm-2 col-form-label">'.abTranslate('Public key').'</label>
    <div class="col-sm-10">
      <input name="recaptcha_key2" type="text" class="form-control" id="recaptcha_key2" value="'.$ab_config['recaptcha_key2'].'">
      <small class="form-text text-muted">'.abTranslate('Public reCAPTCHA v2 website key.').' <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">'.abTranslate('Create keys for free.').' <i class="bi bi-box-arrow-up-right"></i></a></small>
    </div>
  </div>

  <div class="form-group row">
    <label for="recaptcha_secret2" class="col-sm-2 col-form-label">'.abTranslate('Private key').'</label>
    <div class="col-sm-10">
      <input name="recaptcha_secret2" type="text" class="form-control" id="recaptcha_secret2" value="'.$ab_config['recaptcha_secret2'].'">
      <small class="form-text text-muted">'.abTranslate('Private reCAPTCHA v2 website key.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="re_check" class="col-sm-2 col-form-label"><a href="https://www.google.com/recaptcha/admin/" target="_blank" rel="noopener">ReCAPTCHA v3 <i class="bi bi-box-arrow-up-right"></i></a></label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="re_check" id="re_check1" value="1" '.(($ab_config['re_check'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="re_check1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('Invisible collection of RE score visitor rating, this is not the selection of "boats"').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="re_check" id="re_check0" value="0" '.(($ab_config['re_check'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="re_check0">'.abTranslate('Disable').'</label>
</div>
    </div>
  </div>

  <div class="form-group row">
    <label for="recaptcha_key" class="col-sm-2 col-form-label">'.abTranslate('Public key').'</label>
    <div class="col-sm-10">
      <input name="recaptcha_key" type="text" class="form-control" id="recaptcha_key" value="'.$ab_config['recaptcha_key'].'">
      <small class="form-text text-muted">'.abTranslate('Public reCAPTCHA v3 website key.').' <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">'.abTranslate('Create keys for free.').' <i class="bi bi-box-arrow-up-right"></i></a></small>
    </div>
  </div>

  <div class="form-group row">
    <label for="recaptcha_secret" class="col-sm-2 col-form-label">'.abTranslate('Private key').'</label>
    <div class="col-sm-10">
      <input name="recaptcha_secret" type="text" class="form-control" id="recaptcha_secret" value="'.$ab_config['recaptcha_secret'].'">
      <small class="form-text text-muted">'.abTranslate('Private reCAPTCHA v3 website key.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="utm_referrer" class="col-sm-2 col-form-label"><a href="https://'.$ab_config['main_url'].'/FAQ/utm-referrer.html" target="_blank" rel="noopener">UTM Referrer <i class="bi bi-box-arrow-up-right"></i></a></label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="utm_referrer" id="utm_referrer1" value="1" '.(($ab_config['utm_referrer'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="utm_referrer1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('To pass the referrer to Yandex.Metrica through substitution in the URL of an additional GET variable').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="utm_referrer" id="utm_referrer0" value="0" '.(($ab_config['utm_referrer'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="utm_referrer0">'.abTranslate('Disable').'</label>
</div>
    </div>
  </div>

  <div class="form-group row">
    <label for="utm_noindex" class="col-sm-2 col-form-label">'.abTranslate('UTM indexing').'</label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="utm_noindex" id="utm_noindex1" value="1" '.(($ab_config['utm_noindex'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="utm_noindex1">'.abTranslate('Disable indexing').' <small class="text-muted">('.abTranslate('URLs with added utm_referrer will be prohibited from being indexed by search engines').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="utm_noindex" id="utm_noindex0" value="0" '.(($ab_config['utm_noindex'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="utm_noindex0">'.abTranslate('Do not prohibit').' <small class="text-muted">('.abTranslate('If the website has a rel="canonical" meta tag, then it will solve the problem of duplicates').').</small></label>
</div>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label for="check_get_ref" class="col-sm-2 col-form-label">'.abTranslate('GET referrer').'</label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="check_get_ref" id="check_get_ref1" value="1" '.(($ab_config['check_get_ref'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="check_get_ref1">'.abTranslate('Check').' <small class="text-muted">('.abTranslate('Check GET variable names in REFERRER URL against stop list').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="check_get_ref" id="check_get_ref0" value="0" '.(($ab_config['check_get_ref'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="check_get_ref0">'.abTranslate('Do not check').'</label>
</div>
    </div>
  </div>

  <div class="form-group row">
    <label for="bad_get_ref" class="col-sm-2 col-form-label">'.abTranslate('Stop name list').'</label>
    <div class="col-sm-10">
      <input name="bad_get_ref" type="text" class="form-control" id="bad_get_ref" value="'.$ab_config['bad_get_ref'].'">
      <small class="form-text text-muted">'.abTranslate('If these GET variables (with any content or empty) are present in the referrer, the verification will not allow automatic access, and the visitor will have to click on the buttons. This is to prevent referrer spam with fake search queries. Specify them separated by a space.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="noarchive" class="col-sm-2 col-form-label"><a href="https://'.$ab_config['main_url'].'/FAQ/noarchive.html" target="_blank" rel="noopener">'.abTranslate('NoArchive Header').' <i class="bi bi-box-arrow-up-right"></i></a></label>
    <div class="col-sm-10">
  <div class="form-check">
  <input class="form-check-input" type="radio" name="noarchive" id="noarchive1" value="1" '.(($ab_config['noarchive'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="noarchive1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('Send <strong>X-Robots-Tag: noarchive</strong> header to prevent search engines from saving a "Cached"').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="noarchive" id="noarchive0" value="0" '.(($ab_config['noarchive'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="noarchive0">'.abTranslate('Disable').'</label>
</div>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label for="secret_allow_get" class="col-sm-2 col-form-label">'.abTranslate('Secret allow get').'</label>
    <div class="col-sm-10">
      <input name="secret_allow_get" type="text" class="form-control" id="secret_allow_get" value="'.$ab_config['secret_allow_get'].'">
      <small class="form-text text-muted">'.abTranslate('The name of the secret GET variable. If it is present in the URL, the visitor will be allowed without check (the AntiBot will not be activated, and these visits will not be added to the logs). In addition, a cookie with this name will be set, and the AntiBot will not be activated if this cookie is present. This is useful in cases where you are testing behavioral algorithms on your website, so as not to interfere with your own bots.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label for="ptrcache_time" class="col-sm-2 col-form-label">'.abTranslate('PTR cache (days)').'</label>
    <div class="col-sm-10">
      <input name="ptrcache_time" type="text" class="form-control" id="ptrcache_time" value="'.$ab_config['ptrcache_time'].'">
      <small class="form-text text-muted">'.abTranslate('Expiration time for PTR cache. In days. Should be set to run daily Cron task from "Log storage".').'</small>
    </div>
  </div>

<hr />


<div class="form-group row">
 <label for="antibot_log_tests" class="col-sm-2 col-form-label">'.abTranslate('Main Logs').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_tests" id="antibot_log_tests1" value="1" '.(($ab_config['antibot_log_tests'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_tests1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('The main log of visitors who have landed on the check page. The log stores records with statuses:').' STOP, AUTO, CLICK, MISS).</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_tests" id="antibot_log_tests0" value="0" '.(($ab_config['antibot_log_tests'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_tests0">'.abTranslate('Disable').'</label>
</div>
    </div>
</div>

<div class="form-group row">
 <label for="antibot_log_local" class="col-sm-2 col-form-label">LOCAL '.abTranslate('log').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_local" id="antibot_log_local1" value="1" '.(($ab_config['antibot_log_local'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_local1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('The log of visitors who have successfully passed the anti-bot check and have allowed cookies').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_local" id="antibot_log_local0" value="0" '.(($ab_config['antibot_log_local'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_local0">'.abTranslate('Disable').' <small class="text-muted">('.abTranslate('Recommended').').</small></label>
</div>
    </div>
</div>

<div class="form-group row">
 <label for="antibot_log_allow" class="col-sm-2 col-form-label">ALLOW '.abTranslate('log').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_allow" id="antibot_log_allow1" value="1" '.(($ab_config['antibot_log_allow'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_allow1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('The log of visitors who have passed without check according to the allowing rules').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_allow" id="antibot_log_allow0" value="0" '.(($ab_config['antibot_log_allow'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_allow0">'.abTranslate('Disable').'</label>
</div>
    </div>
</div>

<div class="form-group row">
 <label for="antibot_log_fake" class="col-sm-2 col-form-label">FAKE '.abTranslate('log').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_fake" id="antibot_log_fake1" value="1" '.(($ab_config['antibot_log_fake'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_fake1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('The log of visitors who have not passed the IP➜PTR➜IP check. These are mostly fake bots disguised as search engine bots').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_fake" id="antibot_log_fake0" value="0" '.(($ab_config['antibot_log_fake'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_fake0">'.abTranslate('Disable').'</label>
</div>
    </div>
</div>

<div class="form-group row">
 <label for="antibot_log_goodip" class="col-sm-2 col-form-label">GOODIP '.abTranslate('log').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_goodip" id="antibot_log_goodip1" value="1" '.(($ab_config['antibot_log_goodip'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_goodip1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('The log of requests with IPs from the allowed list, which includes search engine bots').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_goodip" id="antibot_log_goodip0" value="0" '.(($ab_config['antibot_log_goodip'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_goodip0">'.abTranslate('Disable').' <small class="text-muted">('.abTranslate('It is recommended as it may cause increased server load').').</small></label>
</div>
    </div>
</div>

<div class="form-group row">
 <label for="antibot_log_block" class="col-sm-2 col-form-label">BLOCK '.abTranslate('log').'</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_block" id="antibot_log_block1" value="1" '.(($ab_config['antibot_log_block'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_block1">'.abTranslate('Enable').' <small class="text-muted">('.abTranslate('The log of visitors who have been blocked according to the blocking rules').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="antibot_log_block" id="antibot_log_block0" value="0" '.(($ab_config['antibot_log_block'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="antibot_log_block0">'.abTranslate('Disable').'</label>
</div>
    </div>
</div>

<hr />

<div class="form-group row">
 <label for="del_ref_query_string" class="col-sm-2 col-form-label">REF Query String</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="del_ref_query_string" id="del_ref_query_string1" value="1" '.(($ab_config['del_ref_query_string'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="del_ref_query_string1">'.abTranslate('Delete').' <small class="text-muted">('.abTranslate('Delete the part of the Referrer URL after the <code>?</code> when saving to the Log').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="del_ref_query_string" id="del_ref_query_string0" value="0" '.(($ab_config['del_ref_query_string'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="del_ref_query_string0">'.abTranslate('Leave the original Referer URL').'</label>
</div>
    </div>
</div>

<div class="form-group row">
 <label for="del_page_query_string" class="col-sm-2 col-form-label">PAGE Query String</label>
    <div class="col-sm-10">
<div class="form-check">
  <input class="form-check-input" type="radio" name="del_page_query_string" id="del_page_query_string1" value="1" '.(($ab_config['del_page_query_string'] == '1') ? 'checked' : '').'>
  <label class="form-check-label" for="del_page_query_string1">'.abTranslate('Delete').' <small class="text-muted">('.abTranslate('Delete the part of the Page URL after the <code>?</code> when saving to the Log').').</small></label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="del_page_query_string" id="del_page_query_string0" value="0" '.(($ab_config['del_page_query_string'] == '0') ? 'checked' : '').'>
  <label class="form-check-label" for="del_page_query_string0">'.abTranslate('Leave the original Page URL').'</label>
</div>
<small class="text-muted">'.abTranslate('Pages and Referrers will be saved as https://site.com/search instead of https://site.com/search?q=xxxxx').'</small>
    </div>
</div>

<hr />

  <div class="form-group row">
    <label for="header_test_code" class="col-sm-2 col-form-label">'.abTranslate('Header Check Page').'</label>
    <div class="col-sm-10">';
foreach ($ab_config['error_headers'] as $k => $v) {
$content .= '
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="header_test_code" id="header_test_code_'.$k.'" value="'.$k.'" '.(($ab_config['header_test_code'] == $k) ? 'checked' : '').'>
  <label class="form-check-label" for="header_test_code_'.$k.'" title="'.$v.'">'.$k.'</label>
</div>
';
}
$content .= '<small class="form-text text-muted">'.abTranslate('Server response code for the check page.').'</small>
    </div>
  </div>

  <div class="form-group row">
    <label for="header_error_code" class="col-sm-2 col-form-label">'.abTranslate('Header Block Page').'</label>
    <div class="col-sm-10">';
foreach ($ab_config['error_headers'] as $k => $v) {
$content .= '
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="header_error_code" id="header_error_code_'.$k.'" value="'.$k.'" '.(($ab_config['header_error_code'] == $k) ? 'checked' : '').'>
  <label class="form-check-label" for="header_error_code_'.$k.'" title="'.$v.'">'.$k.'</label>
</div>
';
}
$content .= '
      <small class="form-text text-muted">'.abTranslate('Server response code for the blocking page.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Log storage').'</label>
    <div class="col-sm-10">
  <a name="cron"></a>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="period_cleaning" id="period_cleaning_lastday" value="lastday" '.(($ab_config['period_cleaning'] == 'lastday') ? 'checked' : '').'>
  <label class="form-check-label" for="period_cleaning_lastday">'.abTranslate('Last Day').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="period_cleaning" id="period_cleaning_lastweek" value="lastweek" '.(($ab_config['period_cleaning'] == 'lastweek') ? 'checked' : '').'>
  <label class="form-check-label" for="period_cleaning_lastweek">'.abTranslate('Last Week').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="period_cleaning" id="period_cleaning_lastmonth" value="lastmonth" '.(($ab_config['period_cleaning'] == 'lastmonth') ? 'checked' : '').'>
  <label class="form-check-label" for="period_cleaning_lastmonth">'.abTranslate('Last Month').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="period_cleaning" id="period_cleaning_quarter" value="quarter" '.(($ab_config['period_cleaning'] == 'quarter') ? 'checked' : '').'>
  <label class="form-check-label" for="period_cleaning_quarter">'.abTranslate('Last Quarter').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="period_cleaning" id="period_cleaning_lastyear" value="lastyear" '.(($ab_config['period_cleaning'] == 'lastyear') ? 'checked' : '').'>
  <label class="form-check-label" for="period_cleaning_lastyear">'.abTranslate('Last Year').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('Older entries will be deleted automatically.').'</small>
    </div>
  </div>

<!-- -->
  <div class="form-group row">
    <label for="php_handler" class="col-sm-2 col-form-label">'.abTranslate('PHP Handler').'</label>
    <div class="col-sm-10">
      <input name="php_handler" type="text" class="form-control" id="php_handler" value="'.$ab_config['php_handler'].'">
      <small class="form-text text-muted">'.abTranslate('PHP handler for deleting old logs via cron.').' '.abTranslate('By default (if nothing is specified):').' <code>'.PHP_BINDIR.'/php</code><br />'.$update_alternatives_info.' '.abTranslate('If automatic deletion is not working at all, here\'s an example of a CRON job:').'<br />
<code>0 1 * * * '.PHP_BINDIR . '/php -q '.dirname(dirname(__FILE__)).'/code/clear_old_hits.php > /dev/null 2>&1</code>
</small>
    </div>
  </div>
 
  <hr />
  <div class="form-group row">
    <label class="col-sm-2 col-form-label">LOCAL + '.abTranslate('empty referrer').'</label>
    <div class="col-sm-10">
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="local_null_ref_stop" id="local_null_ref_stop_1" value="1" '.(($ab_config['local_null_ref_stop'] == 1) ? 'checked' : '').'>
  <label class="form-check-label" for="local_null_ref_stop_1" style="color:black;">'.abTranslate('Re-check').' (DARK)</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="local_null_ref_stop" id="local_null_ref_stop_0" value="0" '.(($ab_config['local_null_ref_stop'] != 1) ? 'checked' : '').'>
  <label class="form-check-label" for="local_null_ref_stop_0">'.abTranslate('Do not re-check').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('Re-check the visitor using CAPTCHA in situations where they have valid cookies (previously passed verification) but accessed with an empty referrer.').'</small>
    </div>
  </div>

<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('BLOCK Iframe').'</label>
    <div class="col-sm-10">
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="iframe_stop" id="iframe_stop_1" value="1" '.(($ab_config['iframe_stop'] == 1) ? 'checked' : '').'>
  <label class="form-check-label" for="iframe_stop_1" style="color:red;">BLOCK</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="iframe_stop" id="iframe_stop_0" value="0" '.(($ab_config['iframe_stop'] != 1) ? 'checked' : '').'>
  <label class="form-check-label" for="iframe_stop_0">'.abTranslate('Do not check').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('Disallow access when the website is opened in an iframe. It is checked only on the check page and the blocking page.').'</small>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('BLOCK Hosting or Bad IP').'</label>
    <div class="col-sm-10">
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="hosting_block" id="hosting_block_1" value="1" '.(($ab_config['hosting_block'] == 1) ? 'checked' : '').'>
  <label class="form-check-label" for="hosting_block_1" style="color:red;">BLOCK</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="hosting_block" id="hosting_block_0" value="0" '.(($ab_config['hosting_block'] != 1) ? 'checked' : '').'>
  <label class="form-check-label" for="hosting_block_0">'.abTranslate('Do not check').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('Block IP addresses that belong not to an internet provider, but to hosting, meaning they are server IPs (hosting, proxy, VPN), or they are IP subnets from which only malicious bots (such as Biterika, etc.) originate.').'</small>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('BLOCK Fake Referer').'</label>
    <div class="col-sm-10">
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="block_fake_ref" id="block_fake_ref_1" value="1" '.(($ab_config['block_fake_ref'] == 1) ? 'checked' : '').'>
  <label class="form-check-label" for="block_fake_ref_1" style="color:red;">BLOCK</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="block_fake_ref" id="block_fake_ref_0" value="0" '.(($ab_config['block_fake_ref'] != 1) ? 'checked' : '').'>
  <label class="form-check-label" for="block_fake_ref_0">'.abTranslate('Do not check').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('Check and block fake referrers that do not contain scheme or path. This rule triggers before the "Last Rule" check.').'<br /><a href="'.abTranslate('https://www.php.net/manual/en/function.parse-url.php').'" target="_blank" rel="noopener">'.abTranslate('What are scheme and path in a URL?').' <i class="bi bi-box-arrow-up-right"></i></a></small>
    </div>
  </div>
  
<hr />

  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Last Rule').'</label>
    <div class="col-sm-10">
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="last_rule" id="last_rule_allow" value="allow" '.(($ab_config['last_rule'] == 'allow') ? 'checked' : '').'>
  <label class="form-check-label" for="last_rule_allow" style="color:green;">ALLOW</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="last_rule" id="last_rule_block" value="block" '.(($ab_config['last_rule'] == 'block') ? 'checked' : '').'>
  <label class="form-check-label" for="last_rule_block" style="color:red;">BLOCK</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="last_rule" id="last_rule_dark" value="dark" '.(($ab_config['last_rule'] == 'dark' OR $ab_config['last_rule'] == 'gray') ? 'checked' : '').'>
  <label class="form-check-label" for="last_rule_dark" style="color:black;">DARK</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="last_rule" id="last_rule_0" value="" '.(($ab_config['last_rule'] == '') ? 'checked' : '').'>
  <label class="form-check-label" for="last_rule_0">'.abTranslate('Do not use').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('The condition that will be triggered last in the "Rules" section, provided that the visitor did not fall under any of the ALLOW, BLOCK, or DARK rules.').'</small>
    </div>
  </div>

<hr />
  <div class="form-group row">
    <label class="col-sm-2 col-form-label">'.abTranslate('Privacy Policy').'</label>
    <div class="col-sm-10">
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="privacy_policy" id="privacy_policy_on" value="on" '.(($ab_config['privacy_policy'] == 'on') ? 'checked' : '').'>
  <label class="form-check-label" for="privacy_policy_on">'.abTranslate('Show').'</label>
</div>
<div class="form-check form-check-inline">
  <input class="form-check-input" type="radio" name="privacy_policy" id="privacy_policy_off" value="off" '.(($ab_config['privacy_policy'] == 'off') ? 'checked' : '').'>
  <label class="form-check-label" for="privacy_policy_off">'.abTranslate('Hide').'</label>
</div>
<small class="form-text text-muted">'.abTranslate('Display the privacy policy and personal data processing policy on the <a href="index.php" target="_blank">index.php</a> page or hide it.').'</small>
    </div>
  </div>
<hr />
  <div class="form-group row">
    <div class="col-sm-10">
      <button name="save_conf" type="submit" class="btn btn-primary">'.abTranslate('Save Settings').'</button>
    </div>
  </div>
</form>
';
