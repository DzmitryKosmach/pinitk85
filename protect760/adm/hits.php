<?php
// лог обращений
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Access Log');

$hits_tables = array('ip', 'ip_short', 'ptr', 'useragent', 'uid', 'country', 'city', 'referer', 'page', 'lang', 'recaptcha', 'js_w', 'js_h', 'js_cw', 'js_ch', 'js_co', 'js_pi', 'refhost', 'asnum', 'asname', 'result', 'http_accept', 'method', 'ym_uid', 'ga_uid', 'timezone'); // список допустимых таблиц

// номер страницы пагинации:
$n = isset($_GET['n']) ? preg_replace("/[^0-9]/","",trim($_GET['n'])) : 0;

$search = isset($_GET['search']) ? trim(strip_tags($_GET['search'])) : '';
$search = $antibot_db->escapeString($search);
$table = isset($_GET['table']) ? preg_replace("/[^a-z\_]/","",trim($_GET['table'])) : 'ip';
if (!in_array($table, $hits_tables)) {$table = 'ip';}
$status = isset($_GET['status']) ? preg_replace("/[^0-9]/","",trim($_GET['status'])) : '';
$operator = isset($_GET['operator']) ? preg_replace("/[^a-z]/","",trim($_GET['operator'])) : '';
$delete = isset($_GET['delete']) ? preg_replace("/[^0-9]/","",trim($_GET['delete'])) : '0';
$csv = isset($_GET['csv']) ? preg_replace("/[^0-9]/","",trim($_GET['csv'])) : '0';
// 2022-11-26T14:02
$date1 = isset($_GET['date1']) ? preg_replace("/[^a-zA-Z0-9\-\:]/","",trim($_GET['date1'])) : date("Y-m-d", $ab_config['time']).'T00:00';
$date2 = isset($_GET['date2']) ? preg_replace("/[^a-zA-Z0-9\-\:]/","",trim($_GET['date2'])) : date("Y-m-d", $ab_config['time']).'T23:59';

if ($date1 == '') {$date1 = date("Y-m-d", $ab_config['time']).'T00:00';}
if ($date2 == '') {$date2 = date("Y-m-d", $ab_config['time']).'T23:59';}
 
$datelimit = 'date >= \''.(int)strtotime($date1).'\' AND date < \''.(int)strtotime($date2).'\'';

if ($operator == 'equally' AND $search != '') {
if ($search == 'null') {
$q = " AND ".$table." = ''";
} else {
$q = " AND ".$table." = '".$search."'";
}
} elseif ($operator == 'contains' AND $search != '') {
$q = " AND ".$table." LIKE '%".$search."%'";
} else {
$q = ' ';
}

$query = trim($datelimit.(($status != '') ? " AND passed='".$status."'" : '').$q);

if ($delete == 1 AND $ab_config['demo'] != 1) {
$sql = "DELETE FROM hits WHERE ".$query.";";
$list = $antibot_db->query($sql); 
$content .= '<div class="alert alert-success" role="alert">'.abTranslate('Selection successfully deleted.').'</div>';
}

if ($csv == 1) {
$sql = "SELECT rowid, * FROM hits WHERE ".$query." ORDER BY date DESC;"; // rowid
$list = $antibot_db->query($sql); 
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.md5($sql).'.csv');
$table_header = 0;
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
if ($table_header == 0) {
$line = implode('<t>', array_keys($echo))."\n";
$line = str_replace('|', '', $line);
$line = str_replace('<t>', '|', $line);
echo $line;
$table_header = 1;
}
$echo['date'] = date("d.m.Y H:i:s", $echo['date']);
$line = implode('<t>', $echo)."\n";
$line = str_replace('|', '', $line);
$line = str_replace('<t>', '|', $line);
echo $line;
unset($echo);
}
abDie();
}

$sql = "SELECT rowid, * FROM hits WHERE ".$query." ORDER BY date DESC LIMIT ".$n.", 100;"; // rowid
//echo '<br />'.$sql;
$list = $antibot_db->query($sql); 

// кол-во результатов выборки:
$search_count1 = $antibot_db->querySingle("SELECT count(rowid) FROM hits WHERE ".$query.";");
$search_count1 = (string)$search_count1;
// уник ипов:
$search_count2 = $antibot_db->querySingle("SELECT count(DISTINCT ip) FROM hits WHERE ".$query.";");
$search_count2 = (string)$search_count2;

$search_count = abTranslate('Found matches:').' '.number_format($search_count1).'. '.abTranslate('Unique IP:').' '.number_format($search_count2);

$content .= '<p>
<form class="form-inline" action="?'.$abw.$abp.'=hits" method="get">';
foreach ($abp_get as $k => $v) {
$content .= '<input name="'.$k.'" type="hidden" value="'.$v.'">';
}
$content .= '<input name="'.$abp.'" type="hidden" value="hits">
<input placeholder="search text" class="form-control mx-sm-3 form-control-sm" name="search" type="text" value="'.(($search != '') ? $search : '').'">
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
<select class="form-control mx-sm-3 form-control-sm" name="table">
<option value="ip" '.(($table == 'ip') ? 'selected' : '').'>'.abTranslate('table:').' IP</option>
<option value="ip_short" '.(($table == 'ip_short') ? 'selected' : '').'>Short IP</option>
<option value="ptr" '.(($table == 'ptr') ? 'selected' : '').'>PTR</option>
<option value="useragent" '.(($table == 'useragent') ? 'selected' : '').'>UserAgent</option>
<option value="uid" '.(($table == 'uid') ? 'selected' : '').'>uid</option>
<option value="country" '.(($table == 'country') ? 'selected' : '').'>country</option>
<option value="city" '.(($table == 'city') ? 'selected' : '').'>city</option>
<option value="referer" '.(($table == 'referer') ? 'selected' : '').'>referer</option>
<option value="page" '.(($table == 'page') ? 'selected' : '').'>page</option>
<option value="lang" '.(($table == 'lang') ? 'selected' : '').'>lang</option>
<option value="recaptcha" '.(($table == 'recaptcha') ? 'selected' : '').'>recaptcha</option>
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
<select class="form-control mx-sm-3 form-control-sm" name="operator">
<option value="equally" '.(($operator == 'equally') ? 'selected' : '').'>'.abTranslate('Strictly equal').'</option>
<option value="contains" '.(($operator == 'contains') ? 'selected' : '').'>'.abTranslate('Contains').'</option>
</select>
<input type="datetime-local" name="date1" value="'.$date1.'" class="form-control mx-sm-3 form-control-sm">-<input type="datetime-local" name="date2" value="'.$date2.'" class="form-control mx-sm-3 form-control-sm"> 
<button style="cursor:pointer;" class="btn btn-sm btn-primary" type="submit" name="submit"><i class="bi bi-search" title="'.abTranslate('Search').'"></i></button>
</form></p>
<p><span class="float-left">'.$search_count.' <small><span class="text-secondary">'.abTranslate('To search for empty values, enter "<code>null</code>" into the form.').'</span></small></span>';
if ($delete != 1) {
$content .= ' <span class="float-right"><a href="'.$ab_config['uri'].'&csv=1" class="badge badge-success">'.abTranslate('Download this selection as .CSV').'</a> <a href="'.$ab_config['uri'].'&delete=1" class="badge badge-danger" onclick="return check()">'.abTranslate('Delete this selection').'</a></span>';
}
$content .= '</p>
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>'.abTranslate('Status').'</th>
<th>IP (PTR) & User Agent & Accept Language</th>
<th>Referer & Page & UID</th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
if ($echo['passed'] == 0) {$passed = '<span style="color:red;">STOP</span>';} 
elseif ($echo['passed'] == 1) {$passed = '<span style="color:green;">AUTO</span>';} 
elseif ($echo['passed'] == 2) {$passed = '<span style="color:teal;">CLICK</span>';} 
elseif ($echo['passed'] == 3) {$passed = '<span style="color:black;">LOCAL</span>';}
elseif ($echo['passed'] == 4) {$passed = '<span style="color:green;">ALLOW</span>';}
elseif ($echo['passed'] == 5) {$passed = '<span style="color:blue;">GOODIP</span>';}
elseif ($echo['passed'] == 6) {$passed = '<span style="color:red;">BLOCK</span>';}
elseif ($echo['passed'] == 7) {$passed = '<span style="color:red;">FAKE</span>';}
elseif ($echo['passed'] == 8) {$passed = '<span style="color:red;">MISS</span>';}
if ($echo['adblock'] == 1) {$echo['adblock'] = '<img src="static/abp.png" style="height: 18px;" title="AdBlock detected"/>';} else {$echo['adblock'] = '';}
if ($echo['hosting'] == 1) {$echo['hosting'] = '<img src="static/h.png" style="height: 18px;" title="Hosting or Bad IP"/>';} else {$echo['hosting'] = '';}
$content .= '<tr>
<td><small>'.date("d.m.Y", $echo['date']).'&nbsp;'.date("H:i:s", $echo['date']).'</small><br />
<strong>'.$passed.'</strong> <div class="fflag fflag-'.$echo['country'].' ff-md img-fluid border" title="'.$echo['country'].'"></div> '.$echo['hosting'].' '.$echo['adblock'].' '.(($echo['js_w'] == '' AND $echo['passed'] == 0) ? '<img src="static/nojs.png" style="height: 18px;" title="'.abTranslate('Without JavaScript').'"/>' : '').'
<br /><small>
'.(($echo['js_w'] != '') ? '<span class="text-secondary" title="Monitor size">M:</span> '.$echo['js_w'].'×'.$echo['js_h'].'<br />' : '').'
'.(($echo['js_cw'] != '') ? '<span class="text-secondary" title="Browser size">B:</span> '.$echo['js_cw'].'×'.$echo['js_ch'].'<br />' : '').'
<!--'.$echo['js_co'].' '.$echo['js_pi'].'<br />-->
'.(($echo['recaptcha'] == 0 OR $echo['recaptcha'] == '') ? '' : '<span class="text-secondary">RE score:</span> '.$echo['recaptcha'].'<br />').'
'.(($echo['generation'] != 0) ? '<span class="text-secondary" title="Script Execution Time">IN:</span> '.$echo['generation'].' sec<br />' : '').'
'.(($echo['generation2'] != 0) ? '<span class="text-secondary">AB:</span> '.$echo['generation2'].' sec<br />' : '').'
<span class="text-secondary">Hit #</span> '.$echo['hit'].'<br />
</small>
</td>
<td style="word-break: break-all;"><strong>'.$echo['country'].'</strong> <a href="?'.$abw.$abp.'=hits&search='.$echo['ip'].'&table=ip&operator=equally&date1='.$date1.'&date2='.$date2.'" title="'.abTranslate('selection by:').' IP">'.$echo['ip'].'</a>'.(($echo['ipv4'] != '') ? ', <span class="text-secondary" title="Monitor size">ipv4:</span> '.$echo['ipv4'] : '').' ('.$echo['ptr'].') <!--<a href="#" target="_blank" rel="noopener">whois</a>--><br />
<small>'.htmlspecialchars($echo['useragent'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'<br />
<strong>'.mb_strimwidth($echo['lang'], 0, 2, '', 'utf-8').'</strong> ('.htmlspecialchars($echo['lang'], ENT_QUOTES | ENT_HTML5, 'UTF-8').')<br />
<span class="text-secondary">'.htmlspecialchars($echo['http_accept'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'</span><br />
<span class="text-secondary">City:</span> '.$echo['city'].', <span class="text-secondary">AS Num:</span> '.$echo['asnum'].', <span class="text-secondary">AS Name:</span> '.$echo['asname'].', <span class="text-secondary">Time Zone:</span> '.$echo['timezone'].'<br />
</small>
<span style="color:'.(($echo['passed'] == 4) ? 'green' : 'red').';">'.$echo['result'].'</span></td>
<td style="word-break: break-all;"><small>
Ref: '.(($echo['refhost'] != '') ? '<img src="https://www.google.com/s2/favicons?domain='.$echo['refhost'].'" />' : '').' <a href="'.htmlspecialchars($echo['referer'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'" target="_blank" rel="noopener noreferrer" title="Referer">'.mb_strimwidth(htmlspecialchars($echo['referer'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, 100, '...', 'utf-8').'</a><br />
P: '.$echo['method'].' <a href="'.htmlspecialchars($echo['page'], ENT_QUOTES | ENT_HTML5, 'UTF-8').'" target="_blank" rel="noopener" title="Page">'.mb_strimwidth(htmlspecialchars($echo['page'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 0, 100, '...', 'utf-8').'</a><br />
uid: <a href="?'.$abw.$abp.'=hits&search='.$echo['uid'].'&table=uid&operator=equally&date1='.$date1.'&date2='.$date2.'" title="'.abTranslate('selection by:').' UID">'.$echo['uid'].'</a> <br />
'.(($echo['ym_uid'] != '') ? 'YM ClientID: '.$echo['ym_uid'].'<br />' : '').'
'.(($echo['ga_uid'] != '') ? 'GA ClientID: '.$echo['ga_uid'].'<br />' : '').'
cid: '.$echo['cid'].'
</small></td>
</tr>';
$i++;
}
$content .= '</tbody>
</table>
</div>';
if ($i == 100) {
$content .= '<center><a href="?'.$abw.$abp.'=hits&n='.($n+100).'&status='.$status.'&search='.urlencode($search).'&table='.$table.'&operator='.$operator.'&date1='.$date1.'&date2='.$date2.'" class="btn btn-info btn-block">'.abTranslate('Show more').'</a></center>
';
}

$content .= '<p><center>';
$x = $n - 1000;
if ($x < 0) {$x = 0;}
while ($x < $n) {
$content .= ' <a href="?'.$abw.$abp.'=hits&n='.$x.'&status='.$status.'&search='.urlencode($search).'&table='.$table.'&operator='.$operator.'&date1='.$date1.'&date2='.$date2.'">'.$x.'</a> | ';
$x = $x+100;
}

if ($n == 0 AND $i < 100) {
$content .= '';
} else {
$content .= $n;
}
$x = $n + 100;
$i = 0;
while ($x < $search_count1) {
if ($i == 10) break; $i++;
$content .= ' | <a href="?'.$abw.$abp.'=hits&n='.$x.'&status='.$status.'&search='.urlencode($search).'&table='.$table.'&operator='.$operator.'&date1='.$date1.'&date2='.$date2.'">'.$x.'</a> ';
$x = $x+100;
}
$content .= '</center></p>';

$content .= '
<p>
<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="'.$abp.'" type="hidden" value="hits">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Delete all records').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastday">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Older than a day').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastweek">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Older than a week').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastmonth">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Older than a month').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="quarter">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Older than a quarter').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastyear">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Older than a year').'">
</form>
</p>
';
