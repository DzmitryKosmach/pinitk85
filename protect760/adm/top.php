<?php
// топ статистика
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Top queries');

$hits_tables = array('ip', 'ip_short', 'ptr', 'useragent', 'uid', 'cid', 'country', 'city', 'referer', 'page', 'lang', 'recaptcha', 'js_w', 'js_h', 'js_cw', 'js_ch', 'js_co', 'js_pi', 'refhost', 'asnum', 'asname', 'result', 'http_accept', 'method', 'ym_uid', 'ga_uid', 'timezone'); // список допустимых таблиц

$table = isset($_GET['table']) ? preg_replace("/[^a-z\_]/","",trim($_GET['table'])) : 'ip';
if (!in_array($table, $hits_tables)) {$table = 'ip';}
$status = isset($_GET['status']) ? preg_replace("/[^0-9]/","",trim($_GET['status'])) : '';
// 2022-11-26T14:02
$date1 = isset($_GET['date1']) ? preg_replace("/[^a-zA-Z0-9\-\:]/","",trim($_GET['date1'])) : date("Y-m-d", $ab_config['time']).'T00:00';
$date2 = isset($_GET['date2']) ? preg_replace("/[^a-zA-Z0-9\-\:]/","",trim($_GET['date2'])) : date("Y-m-d\TH:i", $ab_config['time']+60);

if ($date1 == '') {$date1 = date("Y-m-d", $ab_config['time']).'T00:00';}
if ($date2 == '') {$date2 = date("Y-m-d", $ab_config['time']).'T23:59';}

$datelimit = 'date >= \''.(int)strtotime($date1).'\' AND date < \''.(int)strtotime($date2).'\'';

$query = $datelimit.(($status != '') ? " AND passed='".$status."'" : '');

$sql = "SELECT count(DISTINCT ip) as uniqueip, count(ROWID) as counter, ".(($table == 'ptr') ? "country, " : '').(($table == 'ip_short') ? "country, " : '').(($table == 'ip') ? "ptr, country, " : '')." ".$table." FROM hits WHERE ".$query." GROUP BY ".$table." ORDER BY COUNT(ROWID) DESC LIMIT 200;";

//echo '<br />'.$sql;
$list = $antibot_db->query($sql); 

$content .= '
<form class="form-inline" action="?'.$abw.$abp.'=top" method="get">';
foreach ($abp_get as $k => $v) {
$content .= '<input name="'.$k.'" type="hidden" value="'.$v.'">';
}
$content .= '<input name="'.$abp.'" type="hidden" value="top">
'.abTranslate('status:').'
<select class="form-control mx-sm-3 form-control-sm" name="status">
<option value="">'.abTranslate('Any status').'</option>
<option value="0" '.(($status == '0') ? 'selected' : '').'>stop</option>
<option value="1" '.(($status == '1') ? 'selected' : '').'>auto</option>
<option value="2" '.(($status == '2') ? 'selected' : '').'>click</option>
<option value="3" '.(($status == '3') ? 'selected' : '').'>local</option>
<option value="4" '.(($status == '4') ? 'selected' : '').'>allow</option>
<option value="5" '.(($status == '5') ? 'selected' : '').'>goodip</option>
<option value="6" '.(($status == '6') ? 'selected' : '').'>block</option>
<option value="7" '.(($status == '7') ? 'selected' : '').'>fake</option>
<option value="8" '.(($status == '8') ? 'selected' : '').'>miss</option>
</select> 
'.abTranslate('table:').'
<select class="form-control mx-sm-3 form-control-sm" name="table">
<option value="ip" '.(($table == 'ip') ? 'selected' : '').'>IP</option>
<option value="ip_short" '.(($table == 'ip_short') ? 'selected' : '').'>Short IP</option>
<option value="ptr" '.(($table == 'ptr') ? 'selected' : '').'>PTR</option>
<option value="useragent" '.(($table == 'useragent') ? 'selected' : '').'>useragent</option>
<option value="uid" '.(($table == 'uid') ? 'selected' : '').'>uid</option>
<option value="cid" '.(($table == 'cid') ? 'selected' : '').'>cid</option>
<option value="country" '.(($table == 'country') ? 'selected' : '').'>country</option>
<option value="city" '.(($table == 'city') ? 'selected' : '').'>city</option>
<option value="referer" '.(($table == 'referer') ? 'selected' : '').'>referer</option>
<option value="page" '.(($table == 'page') ? 'selected' : '').'>page</option>
<option value="lang" '.(($table == 'lang') ? 'selected' : '').'>lang</option>
<option value="recaptcha" '.(($table == 'recaptcha') ? 'selected' : '').'>recaptcha score</option>
<option value="js_w" '.(($table == 'js_w') ? 'selected' : '').'>monitor width</option>
<option value="js_h" '.(($table == 'js_h') ? 'selected' : '').'>monitor height</option>
<option value="js_cw" '.(($table == 'js_cw') ? 'selected' : '').'>window width</option>
<option value="js_ch" '.(($table == 'js_ch') ? 'selected' : '').'>window height</option>
<option value="js_co" '.(($table == 'js_co') ? 'selected' : '').'>colordepth</option>
<option value="js_pi" '.(($table == 'js_pi') ? 'selected' : '').'>pixeldepth</option>
<option value="refhost" '.(($table == 'refhost') ? 'selected' : '').'>refhost</option>
<option value="asnum" '.(($table == 'asnum') ? 'selected' : '').'>asnum</option>
<option value="asname" '.(($table == 'asname') ? 'selected' : '').'>asname</option>
<option value="result" '.(($table == 'result') ? 'selected' : '').'>result</option>
<option value="http_accept" '.(($table == 'http_accept') ? 'selected' : '').'>http_accept</option>
<option value="method" '.(($table == 'method') ? 'selected' : '').'>http method</option>
<option value="ym_uid" '.(($table == 'ym_uid') ? 'selected' : '').'>YM ClientID</option>
<option value="ga_uid" '.(($table == 'ga_uid') ? 'selected' : '').'>GA ClientID</option>
<option value="timezone" '.(($table == 'timezone') ? 'selected' : '').'>Time Zone</option>
</select>

<input type="datetime-local" name="date1" value="'.$date1.'" class="form-control mx-sm-3 form-control-sm"> 
'.abTranslate('to').' <input type="datetime-local" name="date2" value="'.$date2.'" class="form-control mx-sm-3 form-control-sm"> 

<input style="cursor:pointer;" class="btn btn-sm btn-primary" type="submit" name="submit" value="'.abTranslate('Search').'">
</form>
<br />
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>#</th>
<th>'.$table.'</th>';
$colspan = 2;
if ($table == 'ptr' OR $table == 'ip' OR $table == 'ip_short') {
$content .= '<th>country</th>';
$colspan = 3;
}
$content .= '<th>queries</th>
<th>unique&nbsp;ip</th>
<th>log</th>
</tr>
</thead>
<tbody>
';
$i = 1;
$sum = 0;
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
$content .= '<tr>
<td>'.$i.'</td>
<td style="word-break: break-all;">
'.(($table == 'refhost') ? (($echo['refhost'] != '') ? '<img src="https://www.google.com/s2/favicons?domain='.$echo['refhost'].'" />' : '') : '').' 
'.(($table == 'country') ? '<div class="fflag fflag-'.$echo['country'].' ff-lg" title="'.$echo['country'].'"></div>' : '').' 
'.$echo[$table].'</td>';
if ($table == 'ptr' OR $table == 'ip' OR $table == 'ip_short') {
$content .= '<td><div class="fflag fflag-'.$echo['country'].' ff-lg" title="'.$echo['country'].'"></div> '.$echo['country'].'</td>';
}
if ($echo[$table] == '') {$echo[$table] = 'null';}
$content .= '<td>'.$echo['counter'].'</td>
<td>'.$echo['uniqueip'].'</td>
<td><a href="?'.$abw.$abp.'=hits&search='.urlencode($echo[$table]).'&table='.$table.'&status='.$status.'&date1='.$date1.'&date2='.$date2.'&operator=equally" title="'.abTranslate('selection by:').' '.$table.'" target="_blank">view</a></td>
</tr>';
$i++;
$sum = $sum + $echo['counter'];
}

$content .= '<tr>
<td colspan="'.$colspan.'" style="text-align: right;">Sum:</td>
<td>'.$sum.'</td>
<td></td>
</tr></tbody>
</table>
';
