<?php
// список правил
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Rules of blocking and allowing');

// экспорт данных:
$export = isset($_GET['export']) ? preg_replace("/[^a-z0-9\_]/","",trim($_GET['export'])) : '';
$tables = array('ipv4rules' => 1, 'ipv6rules' => 2, 'ab_se' => 3, 'ab_path' => 4, 'rules' => 5);
if ($export != '' AND isset($tables[$export])) {
$list = $antibot_db->query("SELECT rowid, * FROM ".$export.";");
header('Content-Type: text/xml; charset=UTF-8');
header('Content-Disposition: attachment; filename='.$ab_config['host'].'_'.$tables[$export].'_'.$export.'_'.date("YmdHis", $ab_config['time']).'.xml');
echo '<rss>
';
if ($export == 'ipv4rules' OR $export == 'ipv6rules') {
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
echo '<item>
<type>'.$export.'</type>
<priority>'.$echo['priority'].'</priority>
<rule>'.$echo['rule'].'</rule>
<search>'.$echo['search'].'</search>
<comment><![CDATA['.$echo['comment'].']]></comment>
<expires>'.$echo['expires'].'</expires>
<disable>'.$echo['disable'].'</disable>
</item>
';
}
} elseif ($export == 'rules') {
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
echo '<item>
<priority>'.$echo['priority'].'</priority>
<rule>'.$echo['rule'].'</rule>
<type>'.$echo['type'].'</type>
<data><![CDATA['.$echo['data'].']]></data>
<comment><![CDATA['.$echo['comment'].']]></comment>
<expires>'.$echo['expires'].'</expires>
<disable>'.$echo['disable'].'</disable>
</item>
';
}
} elseif ($export == 'ab_se') {
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
echo '<item>
<type>ab_se</type>
<priority>'.$echo['priority'].'</priority>
<rule>'.$echo['rule'].'</rule>
<search><![CDATA['.$echo['search'].']]></search>
<data><![CDATA['.$echo['data'].']]></data>
<comment><![CDATA['.$echo['comment'].']]></comment>
<disable>'.$echo['disable'].'</disable>
</item>
';
}
} elseif ($export == 'ab_path') {
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
echo '<item>
<type>ab_path</type>
<priority>'.$echo['priority'].'</priority>
<rule>'.$echo['rule'].'</rule>
<search><![CDATA['.$echo['search'].']]></search>
<comment><![CDATA['.$echo['comment'].']]></comment>
<disable>'.$echo['disable'].'</disable>
</item>
';
}
}
echo '</rss>';
abDie();
}

$del = $antibot_db->exec("DELETE FROM ipv4rules WHERE expires < ".$ab_config['time'].";");
$del = $antibot_db->exec("DELETE FROM ipv6rules WHERE expires < ".$ab_config['time'].";");
$del = $antibot_db->exec("DELETE FROM rules WHERE expires < ".$ab_config['time'].";");

$rules = $antibot_db->query("SELECT rowid, * FROM rules ORDER BY priority ASC;"); 
$ipv4rules = $antibot_db->query("SELECT rowid, * FROM ipv4rules ORDER BY priority ASC;"); 
$ipv6rules = $antibot_db->query("SELECT rowid, * FROM ipv6rules ORDER BY priority ASC;"); 
$ab_se_rules = $antibot_db->query("SELECT rowid, * FROM ab_se ORDER BY priority ASC;"); 
$ab_se_path = $antibot_db->query("SELECT rowid, * FROM ab_path ORDER BY priority ASC;"); 

$content .= '
<div class="alert alert-danger" role="alert">
<form id="form-id" action="?'.$abw.$abp.'=resetcookie" method="post" style="display: inline-block;">
<input name="resetcookie_submit" type="hidden" value="rules">
'.abTranslate('After adding blocking rules that may affect visitors with pallowing cookies, it is recommended to').' <span style="cursor:pointer;" class="badge badge-danger" onclick="document.getElementById(\'form-id\').submit();"> '.abTranslate('Reset Cookies').' </span>
</form>
</div>

<p>'.abTranslate('The processing order of rules is determined by the order of tables and priorities.').' '.abTranslate('The check is performed until the first <span style="color:green;">ALLOW</span>, the first <span style="color:red;">BLOCK</span>, or the first <span style="color:black;">DARK</span> rule is encountered. If such a rule is encountered, the cycle stops with the following result:').'</p>
<ul>
<li><span style="color:green;">ALLOW</span> - '.abTranslate('Allows access to the website without showing the anti-bot check (there is no visible presence of AntiBot).').'</li>
<li><span style="color:red;">BLOCK</span> - '.abTranslate('Fully blocks access to the website (shows a page from the error.txt template).').'</li>
<li><span style="color:black;">DARK</span> - '.abTranslate('Shows the check page with buttons that allow accessing the website by clicking on them.').'</li>
</ul>
<p>'.abTranslate('If <span style="color:gray;">GRAY</span> is encountered, the cycle of checks continues through all the rules, but at the end on the JS check page (template tpl.txt), there will be no automatic passing and the visitor will have to click login buttons.').'</p>
<p>'.abTranslate('After checking all these rules, additional checks from the <strong>Config
</strong> might be applied: "<strong>BLOCK Iframe</strong>", "<strong>BLOCK Hosting or Bad IP</strong>", "<strong>BLOCK Fake Referer</strong>" and "<strong>Last Rule</strong>".').'</p>
<p>
<ol class="list-group">
<li class="list-group-item">1) <a href="#ipv4rules_0">'.abTranslate('Table of IPv4 rules.').'</a> <a href="#add_ipv4rules" class="badge badge-success">'.abTranslate('Add').'</a></li>
<li class="list-group-item">2) <a href="#ipv6rules_0">'.abTranslate('Table of IPv6 rules.').'</a> <a href="#add_ipv6rules" class="badge badge-success">'.abTranslate('Add').'</a></li>
<li class="list-group-item">3) <a href="#ab_se_0">'.abTranslate('Search by User-Agent part.').'</a> <a href="#add_ab_se" class="badge badge-success">'.abTranslate('Add').'</a></li>
<li class="list-group-item">4) <a href="#ab_path_0">'.abTranslate('Search by URL part.').'</a> <a href="#add_ab_path" class="badge badge-success">'.abTranslate('Add').'</a></li>
<li class="list-group-item">5) <a href="#rules_0">'.abTranslate('General rules for different parameters.').'</a> <a href="#add_rules" class="badge badge-success">'.abTranslate('Add').'</a></li>
</ol>
</p>

<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr><th colspan="8"><a name="ipv4rules_0"></a> '.abTranslate('1) First, IPv4 or IPv6 are checked in priority order. In the log of successful requests (allow), they have the status <strong style="color:blue;">GOODIP</strong>. IP addresses of search engines added to the rules of Table # 3 are automatically added to these rules.').'</th></tr>
<tr>
<th>priority</th>
<th>rule</th>
<th>search IPv4</th>
<th>comment</th>
<th>expires</th>
<th></th>
<th></th>
<th></th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $ipv4rules->fetchArray(SQLITE3_ASSOC)) {
$i++;
$days_left = floor(($echo['expires'] - $ab_config['time']) / 86400);
if ($echo['rule'] == 'allow') {$style = 'style="color:green;"';} elseif ($echo['rule'] == 'block') {$style = 'style="color:red;"';} elseif ($echo['rule'] == 'dark') {$style = 'style="color:black;"';} else {$style = 'style="color:gray;"';}
$content .= '<tr '.(($echo['disable'] == 1) ? 'title="'.abTranslate('Rule is turned off (not applied).').'" class="text-muted table-secondary"' : '').'>
<td><a name="ipv4rules_'.$echo['rowid'].'"></a><form action="?'.$abw.$abp.'=priority" method="post" class="form-inline">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ipv4rules">
<input class="form-control form-control-sm col-md-10" name="priority" type="text" value="'.$echo['priority'].'">
<button type="submit" name="priority_submit" class="btn btn-sm btn-primary col-md-2"><i class="bi bi-arrow-repeat"></i></button>
</form></td>
<td '.$style.'>'.mb_strtoupper($echo['rule'], 'UTF-8').'</td>
<td>'.((filter_var($echo['search'], FILTER_VALIDATE_IP) !== false) ? '<a href="?'.$abw.$abp.'=hits&search='.$echo['search'].'&table=ip&operator=equally">'.$echo['search'].'</a>' : $echo['search']).'</td>
<td><small>'.$echo['comment'].'</small></td>
<td><small>'.((is_numeric($echo['expires'])) ? date("d.m.Y H:i:s", $echo['expires']) : '').'<br />
'.abTranslate('days left:').' '.$days_left.'
</small></td>
<td>
<form action="?'.$abw.$abp.'=disablerule" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ipv4rules">
<input name="disable" type="hidden" value="'.(($echo['disable'] == 1) ? '0' : '1').'">
<button type="submit" name="disablerule_submit" class="btn btn-'.(($echo['disable'] == 1) ? 'secondary' : 'primary').' btn-sm" title="'.abTranslate((($echo['disable'] == 1) ? 'Switch ON' : 'Switch OFF')).'">
<i class="bi bi-toggle-'.(($echo['disable'] == 1) ? 'off' : 'on').'"></i>
</button>
</form>
</td>
<td><a href="javascript:void(0)" class="btn btn-success btn-sm" title="'.abTranslate('Edit').'" onclick=\'Insert_Form_1("'.$echo['priority'].'", "'.htmlspecialchars($echo['search']).'", "'.htmlspecialchars($echo['comment']).'", "'.$days_left.'")\'><i class="bi bi-plus-square"></i></a></td>
<td>
<form action="?'.$abw.$abp.'=remove" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ipv4rules">
<button type="submit" name="remove_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
<i class="bi bi-trash"></i>
</button>
</form></td>
</tr>';
}
$content .= '
<script>
function Insert_Form_1(priority, data, comment, expires) {
document.getElementById("priority1").value=priority;
document.getElementById("data1").value=data;
document.getElementById("comment1").value=comment;
document.getElementById("expires1").value=expires;
window.location.href="#add_ipv4rules";
}
</script>
<form action="?'.$abw.$abp.'=newrule" method="post">
<tr>
<td><a name="add_ipv4rules"></a><input type="hidden" name="type" value="ipv4">
<input name="priority" type="text" class="form-control form-control-sm" id="priority1" value="100">
<small class="text-muted">'.abTranslate('The priority (order) of rule checking and application.').'</small></td>
<td>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="allow1" value="allow" required>
  <label class="form-check-label" for="allow1" style="color:green;">ALLOW</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="block1" value="block">
  <label class="form-check-label" for="block1" style="color:red;">BLOCK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="dark1" value="dark">
  <label class="form-check-label" for="dark1" style="color:black;">DARK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="gray1" value="gray">
  <label class="form-check-label" for="gray1" style="color:gray;">GRAY</label>
</div></td>
<td>
	<input name="data" type="text" class="form-control form-control-sm" id="data1" value="">
	<small class="text-muted">'.abTranslate('IPv4 like 123.123.123.123 or subnet 123.123.123.0/24').'</small></td>
<td>
      <input name="comment" type="text" class="form-control form-control-sm" id="comment1" value="">
      <small class="text-muted">'.abTranslate('Description, so as not to forget why it was created.').'</small>
</td>
<td>
      <input name="expires" type="text" class="form-control form-control-sm" id="expires1" value="">
      <small class="text-muted">'.abTranslate('The rule\'s validity period in days, after which it will be deleted. Leave it blank for permanent validity.').'</small>
</td>
<td colspan="3"><button type="submit" name="newrule_submit" class="btn btn-success btn-sm btn-block" title="'.abTranslate('Add').'"><i class="bi bi-plus-square"></i> '.abTranslate('Add').'</button></td>
</tr>
</form>
<tr><td colspan="7" class="text-muted">
<div class="container">
<div class="row">
<div class="col-2">'.abTranslate('Total rules:').' '.$i.'</div>
<div class="col-4"><a href="?'.$abw.$abp.'=rules&export=ipv4rules">'.abTranslate('Download Table Rules #').' 1</a></div>
<div class="col-6">
<form action="?'.$abw.$abp.'=import" method="post" enctype="multipart/form-data" class="form-inline">
<input type="file" name="file" accept="text/*" class="btn-sm">
 <button type="submit" name="import_submit" class="btn btn-outline-primary btn-sm">'.abTranslate('Upload from XML to Table').'</button>
</form>
</div>
</div>
</div>
</td>
<td><form action="?'.$abw.$abp.'=removeallrules" method="post" onsubmit="return check()">
<input name="table" type="hidden" value="ipv4rules">
<button type="submit" name="removeallrules_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete all rules').'">
<i class="bi bi-trash"></i>
</button>
</form></td></tr></tbody>
</table>
</div>
<br />
';

// --- tab 2 ---
$content .= '
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr><th colspan="8"><a name="ipv6rules_0"></a> '.abTranslate('2) IPv4 or IPv6 is checked first in priority order. In the log of successful (allow) requests, they have the status <strong style="color:blue;">GOODIP</strong>. IP addresses of search engines added in the rules of table # 3 are automatically added to these rules.').'</th></tr>
<tr>
<th>priority</th>
<th>rule</th>
<th>search IPv6</th>
<th>comment</th>
<th>expires</th>
<th></th>
<th></th>
<th></th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $ipv6rules->fetchArray(SQLITE3_ASSOC)) {
$i++;
$days_left = floor(($echo['expires'] - $ab_config['time']) / 86400);
if ($echo['rule'] == 'allow') {$style = 'style="color:green;"';} elseif ($echo['rule'] == 'block') {$style = 'style="color:red;"';} elseif ($echo['rule'] == 'dark') {$style = 'style="color:black;"';} else {$style = 'style="color:gray;"';}
$content .= '<tr '.(($echo['disable'] == 1) ? 'title="'.abTranslate('Rule is turned off (not applied).').'" class="text-muted table-secondary"' : '').'>
<td><a name="ipv6rules_'.$echo['rowid'].'"></a><form action="?'.$abw.$abp.'=priority" method="post" class="form-inline">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ipv6rules">
<input class="form-control form-control-sm col-md-10" name="priority" type="text" value="'.$echo['priority'].'">
<button type="submit" name="priority_submit" class="btn btn-sm btn-primary col-md-2"><i class="bi bi-arrow-repeat"></i></button>
</form></td>
<td '.$style.'>'.mb_strtoupper($echo['rule'], 'UTF-8').'</td>
<td>'.((filter_var($echo['search'], FILTER_VALIDATE_IP) !== false) ? '<a href="?'.$abw.$abp.'=hits&search='.$echo['search'].'&table=ip&operator=equally">'.$echo['search'].'</a>' : $echo['search']).'</td>
<td><small>'.$echo['comment'].'</small></td>
<td><small>'.((is_numeric($echo['expires'])) ? date("d.m.Y H:i:s", $echo['expires']) : '').'
<br />
'.abTranslate('days left:').' '.$days_left.'
</small>
</td>
<td><form action="?'.$abw.$abp.'=disablerule" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ipv6rules">
<input name="disable" type="hidden" value="'.(($echo['disable'] == 1) ? '0' : '1').'">
<button type="submit" name="disablerule_submit" class="btn btn-'.(($echo['disable'] == 1) ? 'secondary' : 'primary').' btn-sm" title="'.abTranslate((($echo['disable'] == 1) ? 'Switch ON' : 'Switch OFF')).'">
<i class="bi bi-toggle-'.(($echo['disable'] == 1) ? 'off' : 'on').'"></i>
</button>
</form> 
</td>
<td><a href="javascript:void(0)" class="btn btn-success btn-sm" title="'.abTranslate('Edit').'" onclick=\'Insert_Form_2("'.$echo['priority'].'", "'.htmlspecialchars($echo['search']).'", "'.htmlspecialchars($echo['comment']).'", "'.$days_left.'")\'><i class="bi bi-plus-square"></i></a></td>
<td>
<form action="?'.$abw.$abp.'=remove" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ipv6rules">
<button type="submit" name="remove_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
<i class="bi bi-trash"></i>
</button>
</form></td>
</tr>';
}
$content .= '
<script>
function Insert_Form_2(priority, data, comment, expires) {
document.getElementById("priority2").value=priority;
document.getElementById("data2").value=data;
document.getElementById("comment2").value=comment;
document.getElementById("expires2").value=expires;
window.location.href="#add_ipv6rules";
}
</script>
<form action="?'.$abw.$abp.'=newrule" method="post">
<tr>
<td><a name="add_ipv6rules"></a><input type="hidden" name="type" value="ipv6">
<input name="priority" type="text" class="form-control form-control-sm" id="priority2" value="100">
<small class="text-muted">'.abTranslate('The priority (order) of rule checking and application.').'</small></td>
<td>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="allow2" value="allow" required>
  <label class="form-check-label" for="allow2" style="color:green;">ALLOW</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="block2" value="block">
  <label class="form-check-label" for="block2" style="color:red;">BLOCK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="dark2" value="dark">
  <label class="form-check-label" for="dark2" style="color:black;">DARK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="gray2" value="gray">
  <label class="form-check-label" for="gray2" style="color:gray;">GRAY</label>
</div></td>
<td>
	<input name="data" type="text" class="form-control form-control-sm" id="data2" value="">
	<small class="text-muted">'.abTranslate('IPv6 like 2402:9400::c8 or subnet 2402:9400:1000:5::/64').'</small></td>
<td>
      <input name="comment" type="text" class="form-control form-control-sm" id="comment2" value="">
      <small class="text-muted">'.abTranslate('Description, so as not to forget why it was created.').'</small>
</td>
<td>
      <input name="expires" type="text" class="form-control form-control-sm" id="expires2" value="">
      <small class="text-muted">'.abTranslate('The rule will be valid for (in DAYS), after that it will be deleted. Leave blank for permanent action.').'</small>
</td>
<td colspan="3"><button type="submit" name="newrule_submit" class="btn btn-success btn-sm btn-block" title="'.abTranslate('Add').'"><i class="bi bi-plus-square"></i> '.abTranslate('Add').'</button></td>

</tr>
</form>
<tr><td colspan="7" class="text-muted">
<div class="container">
<div class="row">
<div class="col-2">'.abTranslate('Total rules:').' '.$i.'</div>
<div class="col-4"><a href="?'.$abw.$abp.'=rules&export=ipv6rules">'.abTranslate('Download Table Rules #').' 2</a></div>
<div class="col-6">
<form action="?'.$abw.$abp.'=import" method="post" enctype="multipart/form-data" class="form-inline">
<input type="file" name="file" accept="text/*" class="btn-sm">
 <button type="submit" name="import_submit" class="btn btn-outline-primary btn-sm">'.abTranslate('Upload from XML to Table').'</button>
</form>
</div>
</div>
</div>
</td>
<td><form action="?'.$abw.$abp.'=removeallrules" method="post" onsubmit="return check()">
<input name="table" type="hidden" value="ipv6rules">
<button type="submit" name="removeallrules_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete all rules').'">
<i class="bi bi-trash"></i>
</button>
</form></td></tr></tbody>
</table>
</div>
<br />
';

// --- tab 3 ---
$content .= '
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr><th colspan="9"><a name="ab_se_0"></a> '.abTranslate('3) Search by part of User-Agent with PTR check (strict check by chain: IP ➜ PTR ➜ IP), these rules are primarily designed for automatic detection of search engine bots and adding them to the allow IP list. It is also possible to block by partial match in User-Agent for unwanted bots (but it is better to do this in .htaccess or Nginx).').' <a href="https://'.$ab_config['main_url'].'/FAQ/good-bots.html" target="_blank" rel="noopener">'.abTranslate('List of popular bots and their settings.').'</a> '.abTranslate('Successfully passed (allow) requests have the status <strong style="color:blue;">GOODIP</strong> in the request log.').'</th></tr>
<tr>
<th>priority</th>
<th>rule</th>
<th>user-agent (part)</th>
<th>ptr</th>
<th>comment</th>
<th></th>
<th></th>
<th></th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $ab_se_rules->fetchArray(SQLITE3_ASSOC)) {
$i++;
if ($echo['rule'] == 'allow') {$style = 'style="color:green;"';} elseif ($echo['rule'] == 'block') {$style = 'style="color:red;"';} elseif ($echo['rule'] == 'dark') {$style = 'style="color:black;"';} else {$style = 'style="color:gray;"';}
$content .= '<tr '.(($echo['disable'] == 1) ? 'title="'.abTranslate('Rule is turned off (not applied).').'" class="text-muted table-secondary"' : '').'>
<td><a name="ab_se_'.$echo['rowid'].'"></a><form action="?'.$abw.$abp.'=priority" method="post" class="form-inline">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ab_se">
<input class="form-control form-control-sm col-md-10" name="priority" type="text" value="'.$echo['priority'].'">
<button type="submit" name="priority_submit" class="btn btn-sm btn-primary col-md-2"><i class="bi bi-arrow-repeat"></i></button>
</form></td>
<td '.$style.'>'.mb_strtoupper($echo['rule'], 'UTF-8').'</td>
<td>'.$echo['search'].'</td>
<td>'.$echo['data'].'</td>
<td><small>'.$echo['comment'].'</small></td>
<td><form action="?'.$abw.$abp.'=disablerule" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ab_se">
<input name="disable" type="hidden" value="'.(($echo['disable'] == 1) ? '0' : '1').'">
<button type="submit" name="disablerule_submit" class="btn btn-'.(($echo['disable'] == 1) ? 'secondary' : 'primary').' btn-sm" title="'.abTranslate((($echo['disable'] == 1) ? 'Switch ON' : 'Switch OFF')).'">
<i class="bi bi-toggle-'.(($echo['disable'] == 1) ? 'off' : 'on').'"></i>
</button>
</form>
</td>
<td><a href="javascript:void(0)" class="btn btn-success btn-sm" title="'.abTranslate('Edit').'" onclick=\'Insert_Form_3("'.$echo['priority'].'", "'.htmlspecialchars($echo['search']).'", "'.htmlspecialchars($echo['data']).'", "'.htmlspecialchars($echo['comment']).'")\'><i class="bi bi-plus-square"></i></a></td>
<td>
<form action="?'.$abw.$abp.'=remove" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ab_se">
<button type="submit" name="remove_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
<i class="bi bi-trash"></i>
</button>
</form></td>
</tr>';
}
$content .= '
<script>
function Insert_Form_3(priority, search, data, comment) {
document.getElementById("priority3").value=priority;
document.getElementById("search3").value=search;
document.getElementById("data3").value=data;
document.getElementById("comment3").value=comment;
window.location.href="#add_ab_se";
}
</script>
<form action="?'.$abw.$abp.'=newrule" method="post">
<tr>
<td><a name="add_ab_se"></a><input type="hidden" name="type" value="ab_se">
<input name="priority" type="text" class="form-control form-control-sm" id="priority3" value="100">
<small class="text-muted">'.abTranslate('The priority (order) of rule checking and application.').'</small></td>
<td>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="allow3" value="allow" required>
  <label class="form-check-label" for="allow3" style="color:green;">ALLOW</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="block3" value="block">
  <label class="form-check-label" for="block3" style="color:red;">BLOCK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="dark3" value="dark">
  <label class="form-check-label" for="dark3" style="color:black;">DARK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="gray3" value="gray">
  <label class="form-check-label" for="gray3" style="color:gray;">GRAY</label>
</div></td>
<td>
	<input name="search" type="text" class="form-control form-control-sm" id="search3" value="" required>
	<small class="text-muted">'.abTranslate('Search User-Agent substring for detecting search engine bots.').'</small></td>
<td>
	<input name="data" type="text" class="form-control form-control-sm" id="data3" value="">
	<small class="text-muted">'.abTranslate('PTR values separated by space, if PTR check is not needed then leave it empty.').'</small></td>
<td>
      <input name="comment" type="text" class="form-control form-control-sm" id="comment3" value="">
      <small class="text-muted">'.abTranslate('Description, so as not to forget why it was created.').'</small>
</td>
<td colspan="3"><button type="submit" name="newrule_submit" class="btn btn-success btn-sm btn-block" title="'.abTranslate('Add').'"><i class="bi bi-plus-square"></i> '.abTranslate('Add').'</button></td>
</tr>
</form>
<tr><td colspan="7" class="text-muted">
<div class="container">
<div class="row">
<div class="col-2">'.abTranslate('Total rules:').' '.$i.'</div>
<div class="col-4"><a href="?'.$abw.$abp.'=rules&export=ab_se">'.abTranslate('Download Table Rules #').' 3</a></div>
<div class="col-6">
<form action="?'.$abw.$abp.'=import" method="post" enctype="multipart/form-data" class="form-inline">
<input type="file" name="file" accept="text/*" class="btn-sm">
 <button type="submit" name="import_submit" class="btn btn-outline-primary btn-sm">'.abTranslate('Upload from XML to Table').'</button>
</form>
</div>
</div>
</div>
</td><td><form action="?'.$abw.$abp.'=removeallrules" method="post" onsubmit="return check()">
<input name="table" type="hidden" value="ab_se">
<button type="submit" name="removeallrules_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete all rules').'">
<i class="bi bi-trash"></i>
</button>
</form></td></tr></tbody>
</table>
</div>
<br />
';

// --- tab 4 ---
$content .= '
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr><th colspan="8"><a name="ab_path_0"></a> '.abTranslate('4) Search by part of the URL. These rules are primarily designed to allow access without check (allow) to directories and sections.').' '.abTranslate('The successfully passed (allow) requests in the log have a status of <strong style="color:green;">ALLOW</strong>. No permissive cookies are set, access is only granted within the specified URL.').'</th></tr>
<tr>
<th>priority</th>
<th>rule</th>
<th>url part</th>
<th>comment</th>
<th></th>
<th></th>
<th></th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $ab_se_path->fetchArray(SQLITE3_ASSOC)) {
$i++;
if ($echo['rule'] == 'allow') {$style = 'style="color:green;"';} elseif ($echo['rule'] == 'block') {$style = 'style="color:red;"';} elseif ($echo['rule'] == 'dark') {$style = 'style="color:black;"';} else {$style = 'style="color:gray;"';}
$content .= '<tr '.(($echo['disable'] == 1) ? 'title="'.abTranslate('Rule is turned off (not applied).').'" class="text-muted table-secondary"' : '').'>
<td><a name="ab_path_'.$echo['rowid'].'"></a><form action="?'.$abw.$abp.'=priority" method="post" class="form-inline">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ab_path">
<input class="form-control form-control-sm col-md-10" name="priority" type="text" value="'.$echo['priority'].'">
<button type="submit" name="priority_submit" class="btn btn-sm btn-primary col-md-2"><i class="bi bi-arrow-repeat"></i></button>
</form></td>
<td '.$style.'>'.mb_strtoupper($echo['rule'], 'UTF-8').'</td>
<td style="word-break: break-all;">'.$echo['search'].'</td>
<td><small>'.$echo['comment'].'</small></td>
<td><form action="?'.$abw.$abp.'=disablerule" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ab_path">
<input name="disable" type="hidden" value="'.(($echo['disable'] == 1) ? '0' : '1').'">
<button type="submit" name="disablerule_submit" class="btn btn-'.(($echo['disable'] == 1) ? 'secondary' : 'primary').' btn-sm" title="'.abTranslate((($echo['disable'] == 1) ? 'Switch ON' : 'Switch OFF')).'">
<i class="bi bi-toggle-'.(($echo['disable'] == 1) ? 'off' : 'on').'"></i>
</button>
</form>
</td>
<td><a href="javascript:void(0)" class="btn btn-success btn-sm" title="'.abTranslate('Edit').'" onclick=\'Insert_Form_4("'.$echo['priority'].'", "'.htmlspecialchars($echo['search']).'", "'.htmlspecialchars($echo['comment']).'")\'><i class="bi bi-plus-square"></i></a></td>
<td>
<form action="?'.$abw.$abp.'=remove" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="ab_path">
<button type="submit" name="remove_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
<i class="bi bi-trash"></i>
</button>
</form></td>
</tr>';
}
$content .= '
<script>
function Insert_Form_4(priority, search, comment) {
document.getElementById("priority4").value=priority;
document.getElementById("search4").value=search;
document.getElementById("comment4").value=comment;
window.location.href="#add_ab_path";
}
</script>
<form action="?'.$abw.$abp.'=newrule" method="post">
<tr>
<td><a name="add_ab_path"></a><input type="hidden" name="type" value="ab_path">
<input name="priority" type="text" class="form-control form-control-sm" id="priority4" value="100">
<small class="text-muted">'.abTranslate('The priority (order) of rule checking and application.').'</small></td>
<td>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="allow4" value="allow" required>
  <label class="form-check-label" for="allow4" style="color:green;">ALLOW</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="block4" value="block">
  <label class="form-check-label" for="block4" style="color:red;">BLOCK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="dark4" value="dark">
  <label class="form-check-label" for="dark4" style="color:black;">DARK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="gray4" value="gray">
  <label class="form-check-label" for="gray4" style="color:gray;">GRAY</label>
</div></td>
<td>
	<input name="search" type="text" class="form-control form-control-sm" id="search4" value="">
	<small class="text-muted">'.abTranslate('Part of URL to search, for example: /wp-content/uploads/').'</small></td>
<td>
      <input name="comment" type="text" class="form-control form-control-sm" id="comment4" value="">
      <small class="text-muted">'.abTranslate('Description, so as not to forget why it was created.').'</small>
</td>
<td colspan="3"><button type="submit" name="newrule_submit" class="btn btn-success btn-sm btn-block" title="'.abTranslate('Add').'"><i class="bi bi-plus-square"></i> '.abTranslate('Add').'</button></td>
</tr>
</form>
<tr><td colspan="6" class="text-muted">
<div class="container">
<div class="row">
<div class="col-2">'.abTranslate('Total rules:').' '.$i.'</div>
<div class="col-4"><a href="?'.$abw.$abp.'=rules&export=ab_path">'.abTranslate('Download Table Rules #').' 4</a></div>
<div class="col-6">
<form action="?'.$abw.$abp.'=import" method="post" enctype="multipart/form-data" class="form-inline">
<input type="file" name="file" accept="text/*" class="btn-sm">
 <button type="submit" name="import_submit" class="btn btn-outline-primary btn-sm">'.abTranslate('Upload from XML to Table').'</button>
</form>
</div>
</div>
</div>
</td><td><form action="?'.$abw.$abp.'=removeallrules" method="post" onsubmit="return check()">
<input name="table" type="hidden" value="ab_path">
<button type="submit" name="removeallrules_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete all rules').'">
<i class="bi bi-trash"></i>
</button>
</form></td></tr></tbody>
</table>
</div>
<br />
';

// --- tab 5 ---
$content .= '
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr><th colspan="9"><a name="rules_0"></a> '.abTranslate('5) Common rules database for different parameters. A visitor who has passed through the ALLOW rule receives permissive cookies as having passed the check successfully and will then appear in the log with the status LOCAL.').'</th></tr>
<tr>
<th>priority</th>
<th>rule</th>
<th>type</th>
<th>data</th>
<th>comment</th>
<th>expires</th>
<th></th>
<th></th>
<th></th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $rules->fetchArray(SQLITE3_ASSOC)) {
$i++;
$days_left = floor(($echo['expires'] - $ab_config['time']) / 86400);
if ($echo['rule'] == 'allow') {$style = 'style="color:green;"';} elseif ($echo['rule'] == 'block') {$style = 'style="color:red;"';} elseif ($echo['rule'] == 'dark') {$style = 'style="color:black;"';} else {$style = 'style="color:gray;"';}
$content .= '<tr '.(($echo['disable'] == 1) ? 'title="'.abTranslate('Rule is turned off (not applied).').'" class="text-muted table-secondary"' : '').'>
<td><a name="rules_'.$echo['rowid'].'"></a><form action="?'.$abw.$abp.'=priority" method="post" class="form-inline">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="rules">
<input class="form-control form-control-sm col-md-10" name="priority" type="text" value="'.$echo['priority'].'">
<button type="submit" name="priority_submit" class="btn btn-sm btn-primary col-md-2"><i class="bi bi-arrow-repeat"></i></button>
</form></td>
<td '.$style.'>'.mb_strtoupper($echo['rule'], 'UTF-8').'</td>
<td>'.$echo['type'].'</td>
<td style="word-break: break-all;">'.$echo['data'].'</td>
<td><small>'.$echo['comment'].'</small></td>
<td><small>'.((is_numeric($echo['expires'])) ? date("d.m.Y H:i:s", $echo['expires']) : '').'
<br />
'.abTranslate('days left:').' '.$days_left.'
</small></td>
<td><form action="?'.$abw.$abp.'=disablerule" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="rules">
<input name="disable" type="hidden" value="'.(($echo['disable'] == 1) ? '0' : '1').'">
<button type="submit" name="disablerule_submit" class="btn btn-'.(($echo['disable'] == 1) ? 'secondary' : 'primary').' btn-sm" title="'.abTranslate((($echo['disable'] == 1) ? 'Switch ON' : 'Switch OFF')).'">
<i class="bi bi-toggle-'.(($echo['disable'] == 1) ? 'off' : 'on').'"></i>
</button>
</form>
</td>
<td><a href="javascript:void(0)" class="btn btn-success btn-sm" title="'.abTranslate('Edit').'" onclick=\'Insert_Form_5("'.$echo['priority'].'", "'.$echo['type'].'", "'.htmlspecialchars($echo['data']).'", "'.htmlspecialchars($echo['comment']).'", "'.$days_left.'")\'><i class="bi bi-plus-square"></i></a></td>
<td>
<form action="?'.$abw.$abp.'=remove" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<input name="table" type="hidden" value="rules">
<button type="submit" name="remove_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
<i class="bi bi-trash"></i>
</button>
</form></td>
</tr>';
}
$content .= '
<script>
function Insert_Form_5(priority, type, data, comment, expires) {
document.getElementById("type5").value=type;
document.getElementById("priority5").value=priority;
document.getElementById("data5").value=data;
document.getElementById("comment5").value=comment;
document.getElementById("expires5").value=expires;
window.location.href="#add_rules";
}
</script>
<form action="?'.$abw.$abp.'=newrule" method="post">
<tr>
<td><a name="add_rules"></a><input name="priority" type="text" class="form-control form-control-sm" id="priority5" value="100">
<small class="text-muted">'.abTranslate('The priority (order) of rule checking and application.').'</small></td>
<td>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="allow5" value="allow" required>
  <label class="form-check-label" for="allow5" style="color:green;">ALLOW</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="block5" value="block">
  <label class="form-check-label" for="block5" style="color:red;">BLOCK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="dark5" value="dark">
  <label class="form-check-label" for="dark5" style="color:black;">DARK</label>
</div>
<div class="form-check">
  <input class="form-check-input" type="radio" name="rule" id="gray5" value="gray">
  <label class="form-check-label" for="gray5" style="color:gray;">GRAY</label>
</div></td>
<td>
<select class="form-control form-control-sm" id="type5" name="type" required>
<option value="">'.abTranslate('Select type').'</option>
<option value="useragent">useragent</option>
<option value="country">'.abTranslate('Country').'</option>
<option value="city">'.abTranslate('City').'</option>
<option value="lang">'.abTranslate('Browser language').'</option>
<option value="referer">'.abTranslate('Referrer').'</option>
<option value="ptr">PTR</option>
<option value="asname">asname</option>
<option value="asnum">asnum</option>
<option value="uri">URI</option>
<option value="scriptname">Script Name</option>
<option value="httpaccept">HTTP_ACCEPT</option>
<option value="ym_uid">YM ClientID</option>
<option value="ga_uid">GA ClientID</option>
<option value="timezone">timezone</option>
</select> 
<small class="text-muted">'.abTranslate('Description of data types below.').'</small>
</td>
<td>
	<input name="data" type="text" class="form-control form-control-sm" id="data5" value="">
	<small class="text-muted">'.abTranslate('Data (user-agent, country, etc.) to search for, can be an empty value.').'</small></td>
<td>
      <input name="comment" type="text" class="form-control form-control-sm" id="comment5" value="">
      <small class="text-muted">'.abTranslate('Description, so as not to forget why it was created.').'</small>
</td>
<td>
      <input name="expires" type="text" class="form-control form-control-sm" id="expires5" value="">
      <small class="text-muted">'.abTranslate('The rule\'s validity period in days, after which it will be deleted. Leave it blank for permanent validity.').'</small>
</td>
<td colspan="3"><button type="submit" name="newrule_submit" class="btn btn-success btn-sm btn-block" title="'.abTranslate('Add').'"><i class="bi bi-plus-square"></i> '.abTranslate('Add').'</button></td>
</tr>
</form>
<tr><td colspan="9">
<ul>
<li><strong>useragent</strong> ('.abTranslate('search by exact match').') <small class="text-muted">'.abTranslate('Example:').' Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)</small></li>
<li><strong>'.abTranslate('Country').'</strong> ('.abTranslate('2-letter code in uppercase, codes according to ISO 3166-1 alpha-2:').' <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" target="_blank" rel="noopener">wiki <i class="bi bi-box-arrow-up-right"></i></a>) <small class="text-muted">'.abTranslate('Example:').' RU '.abTranslate('or').' US</small></li>
<li><strong>'.abTranslate('City').'</strong> ('.abTranslate('search by exact match').', '.abTranslate('in Latin, as in the Access Log').') <small class="text-muted">'.abTranslate('Example:').' Yerevan</small></li>
<li><strong>'.abTranslate('Browser language').'</strong> ('.abTranslate('2-letter language code in lowercase, codes according to ISO 639-1:').' <a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes" target="_blank" rel="noopener">wiki <i class="bi bi-box-arrow-up-right"></i></a>) <small class="text-muted">'.abTranslate('Example:').' ru '.abTranslate('or').' en</small></li>
<li><strong>'.abTranslate('Referrer').'</strong> ('.abTranslate('host only, search by exact match').') <small class="text-muted">'.abTranslate('Example:').' iframe-toloka.com</small></li>
<li><strong>PTR</strong> ('.abTranslate('only 2nd or 3rd level domains, exact host match').') <small class="text-muted">'.abTranslate('Example:').' amazonaws.com '.abTranslate('or').' compute.amazonaws.com</small></li>
<li><strong>asname</strong> ('.abTranslate('owner name of the IP range, taken from the AntiBot log').') <small class="text-muted">'.abTranslate('Example:').' Biterika</small></li>
<li><strong>asnum</strong> ('.abTranslate('IP range number, taken from AntiBot log').') <small class="text-muted">'.abTranslate('Example:').' 8075</small></li>
<li><strong>URI</strong> ('.abTranslate('full page URL without domain, starting from / at the root of the website').') <small class="text-muted">'.abTranslate('Example:').' /page1.html '.abTranslate('or').' /index.php?p=123</small></li>
<li><strong>Script Name</strong> ('.abTranslate('script name from the root of the website starting with / and without GET variables').') <small class="text-muted">'.abTranslate('Example:').' /api/send.php</small></li>
<li><strong>HTTP_ACCEPT</strong> ('.abTranslate('search by exact match').') <small class="text-muted">'.abTranslate('Example:').' text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8</small></li>
<li><strong>YM ClientID</strong> (ClientID из Яндекс.Метрика) <small class="text-muted">'.abTranslate('Example:').' 1657649870875013486</small></li>
<li><strong>GA ClientID</strong> (ClientID from Google Analytics) <small class="text-muted">'.abTranslate('Example:').' GA1.2.757952487.1668090332</small></li>
<li><strong>timezone</strong> ('.abTranslate('Browser Time Zone from JS').', <a href="https://'.$ab_config['main_url'].'/FAQ/timezone.html" target="_blank" rel="noopener">'.abTranslate('more').' <i class="bi bi-box-arrow-up-right"></i></a>) <small class="text-muted">'.abTranslate('Example:').' Europe/Moscow '.abTranslate('or').' UTC '.abTranslate('or').' Etc/GMT-3</small></li>
</ul>
</td></tr>
<tr><td colspan="8" class="text-muted">
<div class="container">
<div class="row">
<div class="col-2">'.abTranslate('Total rules:').' '.$i.'</div>
<div class="col-4"><a href="?'.$abw.$abp.'=rules&export=rules">'.abTranslate('Download Table Rules #').' 5</a></div>
<div class="col-6">
<form action="?'.$abw.$abp.'=import" method="post" enctype="multipart/form-data" class="form-inline">
<input type="file" name="file" accept="text/*" class="btn-sm">
 <button type="submit" name="import_submit" class="btn btn-outline-primary btn-sm">'.abTranslate('Upload from XML to Table').'</button>
</form>
</div>
</div>
</div>
</td><td><form action="?'.$abw.$abp.'=removeallrules" method="post" onsubmit="return check()">
<input name="table" type="hidden" value="rules">
<button type="submit" name="removeallrules_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete all rules').'">
<i class="bi bi-trash"></i>
</button>
</form></td></tr>
</tbody>
</table>
</div>
';
