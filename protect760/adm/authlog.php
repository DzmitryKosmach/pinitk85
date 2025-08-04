<?php
// лог авторизаций
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Authorization Log');

// номер страницы пагинации:
$n = isset($_GET['n']) ? preg_replace("/[^0-9]/","",trim($_GET['n'])) : 0;

$sql = "SELECT rowid, * FROM auth_log ORDER BY rowid DESC LIMIT ".$n.", 100;";
//echo '<br />'.$sql;
$list = $antibot_db->query($sql); 

$content .= '

<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th></th>
<th>date</th>
<th>country</th>
<th>ip</th>
<th>result</th>
</tr>
</thead>
<tbody>
';

while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
$content .= '<tr>
<td><div class="fflag fflag-'.$echo['country'].' ff-lg" title="'.$echo['country'].'"></div></td>
<td>'.date("d.m.Y H:i:s", $echo['date']).'</td>
<td>'.$echo['country'].'</td>
<td><a href="?'.$abw.$abp.'=hits&search='.$echo['ip'].'&table=ip&operator=equally">'.$echo['ip'].'</a></td>
<td>'.(($echo['result'] == 1) ? '<span class="text-success">'.abTranslate('Successful authorization').'</span>' : '<span class="text-danger">'.abTranslate('Authorisation Error').'</span>').'</td>
</tr>';
}

$content .= '</tbody>
</table>';
