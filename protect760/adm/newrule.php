<?php
// создание нового правила
if(!defined('ANTIBOT')) die('access denied');

$bad_symbol = array('\'', '"', '\\');

$title = abTranslate('Create rule');

if (isset($_POST['newrule_submit'])) {
// если передан, то update, иначе insert:
$_POST['rowid'] = isset($_POST['rowid']) ? trim(preg_replace("/[^0-9]/","", $_POST['rowid'])) : '';

$_POST['priority'] = isset($_POST['priority']) ? (int)trim(preg_replace("/[^0-9]/","", $_POST['priority'])) : 100;
$_POST['type'] = isset($_POST['type']) ? trim(preg_replace("/[^a-z0-9_]/","", $_POST['type'])) : abDie('type');
$_POST['data'] = isset($_POST['data']) ? trim(strip_tags($_POST['data'])) : '';
$_POST['rule'] = isset($_POST['rule']) ? trim(preg_replace("/[^a-z]/","", $_POST['rule'])) : abDie('rule');
$_POST['comment'] = isset($_POST['comment']) ? trim(strip_tags($_POST['comment'])) : '';
$_POST['expires'] = isset($_POST['expires']) ? trim(strip_tags($_POST['expires'])) : '';

if (is_numeric($_POST['expires'])) {
if ($_POST['expires'] < 90000) {
$_POST['expires_save'] = $_POST['expires'] * 86400 + $ab_config['time'];
} else {
$_POST['expires_save'] = 9999999999;
}
} else {
$_POST['expires_save'] = 9999999999;
}

if ($_POST['type'] == 'ipv4') {
$testip = explode('/', $_POST['data']);
if (filter_var($testip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) AND isset($testip[1]) AND is_numeric($testip[1])) {
// норм ipv4 подсеть:
$AbIpRange = AbIpRange($_POST['data']);
$_POST['data'] = $antibot_db->escapeString($_POST['data']);
$_POST['search'] = $_POST['data'];
if ($_POST['comment'] == '') {$_POST['comment'] = $AbIpRange[0].' - '.$AbIpRange[1];}
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE ipv4rules SET priority='".$_POST['priority']."', search='".$_POST['data']."', ip1='".AbIp2num($AbIpRange[0])."', ip2='".AbIp2num($AbIpRange[1])."', rule='".$_POST['rule']."', comment='".$_POST['comment']."', expires='".$_POST['expires_save']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv4rules_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$_POST['priority']."', '".$_POST['data']."', '".AbIp2num($AbIpRange[0])."', '".AbIp2num($AbIpRange[1])."', '".$_POST['rule']."', '".$_POST['comment']."', '".$_POST['expires_save']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv4rules_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: ipv4rules.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM ipv4rules WHERE search = '".$_POST['data']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
// end ipv4 mask
} elseif (filter_var($_POST['data'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
// норм отдельный ipv4:
$_POST['data'] = $antibot_db->escapeString($_POST['data']);
$_POST['search'] = $_POST['data'];
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE ipv4rules SET priority='".$_POST['priority']."', search='".$_POST['data']."', ip1='".AbIp2num($_POST['data'])."', ip2='".AbIp2num($_POST['data'])."', rule='".$_POST['rule']."', comment='".$_POST['comment']."', expires='".$_POST['expires_save']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv4rules_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$_POST['priority']."', '".$_POST['data']."', '".AbIp2num($_POST['data'])."', '".AbIp2num($_POST['data'])."', '".$_POST['rule']."', '".$_POST['comment']."', '".$_POST['expires_save']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv4rules_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: ipv4rules.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM ipv4rules WHERE search = '".$_POST['data']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
// end ipv4
} else {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('It\'s not IPv4. <a href="#" onclick="history.back();">Go back</a>.').'
</div>';
}
} elseif ($_POST['type'] == 'ipv6') {
$testip = explode('/', $_POST['data']);
if (filter_var($testip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) AND isset($testip[1]) AND is_numeric($testip[1])) {
// норм подсеть:
$AbIpRange = AbIpRange($_POST['data']);
$_POST['data'] = abExpand($testip[0]).'/'.$testip[1];
$_POST['data'] = $antibot_db->escapeString($_POST['data']);
$_POST['search'] = $_POST['data'];
$AbIpRange[0] = abExpand($AbIpRange[0]);
if ($_POST['comment'] == '') {$_POST['comment'] = $AbIpRange[0].' - '.$AbIpRange[1];}
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE ipv6rules SET priority='".$_POST['priority']."', search='".$_POST['data']."', ip1='".AbIp2num($AbIpRange[0])."', ip2='".AbIp2num($AbIpRange[1])."', rule='".$_POST['rule']."', comment='".$_POST['comment']."', expires='".$_POST['expires_save']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv6rules_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$_POST['priority']."', '".$_POST['data']."', '".AbIp2num($AbIpRange[0])."', '".AbIp2num($AbIpRange[1])."', '".$_POST['rule']."', '".$_POST['comment']."', '".$_POST['expires_save']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv6rules_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: ipv6rules.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM ipv6rules WHERE search = '".$_POST['data']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
// end ipv6 mask
} elseif (filter_var($_POST['data'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
// норм ipv6:
$_POST['data'] = $antibot_db->escapeString(abExpand($_POST['data']));
$_POST['search'] = $_POST['data'];
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE ipv6rules SET priority='".$_POST['priority']."', search='".$_POST['data']."', ip1='".AbIp2num($_POST['data'])."', ip2='".AbIp2num($_POST['data'])."', rule='".$_POST['rule']."', comment='".$_POST['comment']."', expires='".$_POST['expires_save']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv6rules_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$_POST['priority']."', '".$_POST['data']."', '".AbIp2num($_POST['data'])."', '".AbIp2num($_POST['data'])."', '".$_POST['rule']."', '".$_POST['comment']."', '".$_POST['expires_save']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ipv6rules_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: ipv6rules.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM ipv6rules WHERE search = '".$_POST['data']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
// end ipv6
} else {
$content .= '<div class="alert alert-danger" role="alert">
'.abTranslate('It\'s not IPv6. <a href="#" onclick="history.back();">Go back</a>.').'
</div>';
}
} elseif ($_POST['type'] == 'ab_se') {
$_POST['search'] = isset($_POST['search']) ? trim(strip_tags($_POST['search'])) : abDie('search');
$_POST['search'] = str_ireplace($bad_symbol, '', $_POST['search']);
$_POST['data'] = isset($_POST['data']) ? trim(preg_replace("/[^A-Za-z0-9\ \-\.\:]/","", $_POST['data'])) : abDie('data');
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE ab_se SET priority='".$_POST['priority']."', search='".$_POST['search']."', data='".$_POST['data']."', rule='".$_POST['rule']."', comment='".$_POST['comment']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ab_se_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment) VALUES ('".$_POST['priority']."', '".$_POST['search']."', '".$_POST['data']."', '".$_POST['rule']."', '".$_POST['comment']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ab_se_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: ab_se.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM ab_se WHERE search = '".$_POST['search']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
$ab_se_rules = $antibot_db->query("SELECT rowid, * FROM ab_se WHERE disable = '0' ORDER BY priority ASC;"); 
$save = '<?php
';
while ($echo = $ab_se_rules->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_rule[\''.$echo['search'].'\'] = \''.$echo['rule'].'\';
';
if ($echo['data'] == '') {
$echo['data'] = '\'.\'';
} else {
$echo['data'] = '\''.str_replace(' ', '\', \'', $echo['data']).'\'';
}
$save .= '$ab_se[\''.$echo['search'].'\'] = array('.$echo['data'].');
';
}
file_put_contents(__DIR__.'/../data/se.php', $save, LOCK_EX);
// end ab_se
} elseif ($_POST['type'] == 'ab_path') {
$_POST['search'] = isset($_POST['search']) ? trim(strip_tags($_POST['search'])) : abDie('search');
$_POST['search'] = str_ireplace($bad_symbol, '', $_POST['search']);
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE ab_path SET priority='".$_POST['priority']."', search='".$_POST['search']."', rule='".$_POST['rule']."', comment='".$_POST['comment']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ab_path_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment) VALUES ('".$_POST['priority']."', '".$_POST['search']."', '".$_POST['rule']."', '".$_POST['comment']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#ab_path_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: ab_path.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM ab_path WHERE search = '".$_POST['search']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
$ab_se_path = $antibot_db->query("SELECT rowid, * FROM ab_path WHERE disable = '0' ORDER BY priority ASC;"); 
$save = '<?php
';
while ($echo = $ab_se_path->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_path[\''.$echo['search'].'\'] = \''.$echo['rule'].'\';
';
}
file_put_contents(__DIR__.'/../data/path.php', $save, LOCK_EX);
// end ab_path
} else {
// основные правила:
$_POST['search'] = $antibot_db->escapeString($_POST['type'].'='.$_POST['data']);
$_POST['data'] = $antibot_db->escapeString($_POST['data']);
$_POST['comment'] = $antibot_db->escapeString($_POST['comment']);
if ($_POST['rowid'] != '') {
// обновляем:
$sql = "UPDATE rules SET priority='".$_POST['priority']."', type='".$_POST['type']."', data='".$_POST['data']."', search='".$_POST['search']."', rule='".$_POST['rule']."', comment='".$_POST['comment']."', expires='".$_POST['expires_save']."' WHERE rowid='".$_POST['rowid']."';";
$update = $antibot_db->exec($sql);
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#rules_'.$_POST['rowid'].'";</script>';
} else {
// добавляем:
$add = @$antibot_db->exec("INSERT INTO rules (priority, type, data, search, rule, comment, expires) VALUES ('".$_POST['priority']."', '".$_POST['type']."', '".$_POST['data']."', '".$_POST['search']."', '".$_POST['rule']."', '".$_POST['comment']."', '".$_POST['expires_save']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
$content .= '<script>document.location.href="?'.$abw.$abp.'=rules#rules_'.$id.'";</script>';
} elseif ($antibot_db->lastErrorMsg() == 'UNIQUE constraint failed: rules.search') {
$rowid = $antibot_db->querySingle("SELECT rowid FROM rules WHERE search = '".$_POST['search']."';", true);
$content = ReSendUpdate($_POST, $rowid['rowid'], $abw, $abp, $_POST['search']);
}
}
// end rules
}
}

function ReSendUpdate($post, $rowid, $abw, $abp, $search) {
unset($post['newrule_submit']);
unset($post['rowid']);
$form = '<p>'.abTranslate('Record').' <strong>'.$search.'</strong> '.abTranslate('was found in the database. Overwrite with new data?').'</p>';
$form .= '<form action="?'.$abw.$abp.'=newrule" method="post">
<input name="rowid" type="hidden" value="'.$rowid.'">
';
foreach ($post as $k => $v) {
$form .= '<input name="'.$k.'" type="hidden" value="'.$v.'">
';
}
$form .= '<button type="submit" name="newrule_submit" class="btn btn-success" title="'.abTranslate('Save new data.').'"><i class="bi bi-plus-square"></i> '.abTranslate('Save new data').'</button>
</form>';
return $form;
}
