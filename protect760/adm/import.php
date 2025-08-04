<?php
// загрузка правил в базу
if(!defined('ANTIBOT')) die('access denied');

$bad_symbol = array('\'', '"', '\\');

$title = abTranslate('Import rules');

if (isset($_POST['import_submit'])) {

if(is_uploaded_file($_FILES['file']['tmp_name'])) {
$xml = simplexml_load_file($_FILES['file']['tmp_name']);
@unlink($_FILES['file']['tmp_name']);

echo '<ul>';
// цикл обработки:
foreach ($xml->item as $item) {
$line['comment'] = (isset($item->comment) AND !is_array($item->comment)) ? (string)$item->comment : '';
$line['comment'] = trim(strip_tags($line['comment']));
$line['data'] = (isset($item->data) AND !is_array($item->data)) ? (string)$item->data : '';
$line['data'] = trim(strip_tags($line['data']));
$line['disable'] = (isset($item->disable) AND !is_array($item->disable)) ? (string)$item->disable : '';
$line['disable'] = trim(strip_tags($line['disable']));
$line['expires'] = (isset($item->expires) AND !is_array($item->expires)) ? (string)$item->expires : '';
$line['expires'] = (int)trim(strip_tags($line['expires']));
$line['priority'] = (isset($item->priority) AND !is_array($item->priority)) ? (string)$item->priority : '';
$line['priority'] = trim(strip_tags($line['priority']));
$line['rule'] = (isset($item->rule) AND !is_array($item->rule)) ? (string)$item->rule : '';
$line['rule'] = trim(strip_tags($line['rule']));
$line['search'] = (isset($item->search) AND !is_array($item->search)) ? (string)$item->search : '';
$line['search'] = trim(strip_tags($line['search']));
$line['type'] = (isset($item->type) AND !is_array($item->type)) ? (string)$item->type : '';
$line['type'] = trim(strip_tags($line['type']));

if ($line['type'] == 'ipv4rules') {
$testip = explode('/', $line['search']);
if (filter_var($testip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) AND isset($testip[1]) AND is_numeric($testip[1])) {
// норм подсеть:
$AbIpRange = AbIpRange($line['search']);
$line['search'] = $antibot_db->escapeString($line['search']);
if ($line['comment'] == '') {$line['comment'] = $AbIpRange[0].' - '.$AbIpRange[1];}
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$line['priority']."', '".$line['search']."', '".AbIp2num($AbIpRange[0])."', '".AbIp2num($AbIpRange[1])."', '".$line['rule']."', '".$line['comment']."', '".$line['expires']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | ... </li>';

} elseif (filter_var($line['search'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
// норм отдельный ип:
$line['search'] = $antibot_db->escapeString($line['search']);
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO ipv4rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$line['priority']."', '".$line['search']."', '".AbIp2num($line['search'])."', '".AbIp2num($line['search'])."', '".$line['rule']."', '".$line['comment']."', '".$line['expires']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | ... </li>';

} else {
$content .= '<li class="text-danger">'.$line['type'].' | '.$line['search'].' | ... </li>';

}
} elseif ($line['type'] == 'ipv6rules') {
$testip = explode('/', $line['search']);
if (filter_var($testip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) AND isset($testip[1]) AND is_numeric($testip[1])) {
// норм подсеть:
$AbIpRange = AbIpRange($line['search']);
$line['search'] = abExpand($testip[0]).'/'.$testip[1];
$line['search'] = $antibot_db->escapeString($line['search']);
$AbIpRange[0] = abExpand($AbIpRange[0]);
if ($line['comment'] == '') {$line['comment'] = $AbIpRange[0].' - '.$AbIpRange[1];}
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$line['priority']."', '".$line['search']."', '".AbIp2num($AbIpRange[0])."', '".AbIp2num($AbIpRange[1])."', '".$line['rule']."', '".$line['comment']."', '".$line['expires']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | ... </li>';

} elseif (filter_var($line['search'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
//норм ип:
$line['search'] = $antibot_db->escapeString(abExpand($line['search']));
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO ipv6rules (priority, search, ip1, ip2, rule, comment, expires) VALUES ('".$line['priority']."', '".$line['search']."', '".AbIp2num($line['search'])."', '".AbIp2num($line['search'])."', '".$line['rule']."', '".$line['comment']."', '".$line['expires']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | ... </li>';

} else {
$content .= '<li class="text-danger">'.$line['type'].' | '.$line['search'].' | ... </li>';

}
} elseif ($line['type'] == 'ab_se') {
$line['search'] = isset($line['search']) ? trim(strip_tags($line['search'])) : abDie('search');
$line['search'] = str_ireplace($bad_symbol, '', $line['search']);
$line['data'] = isset($line['data']) ? trim(preg_replace("/[^A-Za-z0-9\ \-\.\:]/","", $line['data'])) : abDie('data');
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO ab_se (priority, search, data, rule, comment) VALUES ('".$line['priority']."', '".$line['search']."', '".$line['data']."', '".$line['rule']."', '".$line['comment']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$ab_se_rules = $antibot_db->query("SELECT rowid, * FROM ab_se ORDER BY priority ASC;"); 
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
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | '.$line['data'].' | ... </li>';

} elseif ($line['type'] == 'ab_path') {
$line['search'] = isset($line['search']) ? trim(strip_tags($line['search'])) : abDie('search');
$line['search'] = str_ireplace($bad_symbol, '', $line['search']);
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO ab_path (priority, search, rule, comment) VALUES ('".$line['priority']."', '".$line['search']."', '".$line['rule']."', '".$line['comment']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$ab_se_path = $antibot_db->query("SELECT rowid, * FROM ab_path ORDER BY priority ASC;"); 
$save = '<?php
';
while ($echo = $ab_se_path->fetchArray(SQLITE3_ASSOC)) {
$save .= '$ab_path[\''.$echo['search'].'\'] = \''.$echo['rule'].'\';
';
}
file_put_contents(__DIR__.'/../data/path.php', $save, LOCK_EX);
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | ... </li>';

} else {
// основные правила:
$line['search'] = $antibot_db->escapeString($line['type'].'='.$line['data']);
$line['data'] = $antibot_db->escapeString($line['data']);
$line['comment'] = $antibot_db->escapeString($line['comment']);
$add = @$antibot_db->exec("INSERT INTO rules (priority, type, data, search, rule, comment, expires) VALUES ('".$line['priority']."', '".$line['type']."', '".$line['data']."', '".$line['search']."', '".$line['rule']."', '".$line['comment']."', '".$line['expires']."');");
if ($antibot_db->lastErrorMsg() == 'not an error') {
$id = (int)$antibot_db->lastInsertRowID();
} else {
$id = 0;
}
$content .= '<li class="text-success">'.$line['type'].' | '.$line['search'].' | ... </li>';

}
}
// конец цикла
echo '</ul>';
}
}

