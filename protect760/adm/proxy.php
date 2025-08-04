<?php
// список ip прокси
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Configuring IP address.');

$ab_proxy_list = $antibot_db->query("SELECT rowid, * FROM ab_proxy;"); 

$content .= '
<p><div class="fflag fflag-'.$ab_config['country'].' ff-md" title="'.$ab_config['country'].'"></div> <strong>'.$ab_config['ip'].'</strong> - '.abTranslate('If this is not the IP address of your computer, then additional configuration is needed to determine the IP.').' <a href="https://'.$ab_config['main_url'].'/FAQ/ddos-guard-net.html" target="_blank" rel="noopener">'.abTranslate('Example of configuration.').' <i class="bi bi-box-arrow-up-right"></i></a></p>
';

//$_SERVER['HTTP_X_FORWARDED_FOR'] = '123.123.123.123';
//$_SERVER['HTTP_X_REAL_IP'] = '23.23.23.3';
$f = 0;
//$content .= '<div class="col-sm-6">';
foreach($_SERVER as $v => $k) {
if (filter_var($k, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) AND $v != 'REMOTE_ADDR' AND $v != 'SERVER_ADDR') {
$content .= '<p>
<form action="?'.$abw.$abp.'=newproxy" method="post">
<input name="k" type="hidden" id="k" value="'.$_SERVER['REMOTE_ADDR'].'/32">
<input name="v" type="hidden" id="v" value="'.$v.'">
<button type="submit" name="newproxy_submit" class="btn btn-success btn-block">'.$v.' ➜ '.$k.' - '.abTranslate('This is my IP, add this rule.').'</button>
</form></p>
';
$f = 1;
}
}
//$content .= '</div>';
if ($f == 0) {
$content .= '<div class="alert alert-danger">'.abTranslate('No IPs found.').'</div>';
}
$content .= '<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>ip/mask</th>
<th>header</th>
<th></th>
</tr>
</thead>
<tbody>
';
while ($echo = $ab_proxy_list->fetchArray(SQLITE3_ASSOC)) {
$content .= '<tr>
<td>'.$echo['k'].'</td>
<td>'.$echo['v'].'</td>
<td><form action="?'.$abw.$abp.'=removeproxy" method="post">
<input name="id" type="hidden" value="'.$echo['rowid'].'">
<button type="submit" name="removeproxy_submit" class="btn btn-danger btn-sm" title="'.abTranslate('Delete').'">
<i class="bi bi-trash"></i>
</button>
</form></td>
</tr>';
}

$content .= '
<tr>
<td colspan="3"><p><strong>'.abTranslate('For advanced users:').'</strong> '.abTranslate('You need to find your real IPv4 address in the $_SERVER array by taking the variable name like HTTP_X_REAL_IP, which passes the real IP, and add it to the IP form from $_SERVER[\'REMOTE_ADDR\'] with the subnet of the required size, as well as the name of this variable.').'</p></td>
</tr>
<form action="?'.$abw.$abp.'=newproxy" method="post">
<tr>
<td>
<input name="k" type="text" class="form-control form-control-sm" id="k" value="'.$_SERVER['REMOTE_ADDR'].'/32">
<small class="text-muted">'.abTranslate('IPv4 address from $_SERVER[\'REMOTE_ADDR\'] and subnet mask.').'</small>
</td>
<td>
<input name="v" type="text" class="form-control form-control-sm" id="v" value="">
<small class="text-muted">'.abTranslate('Server header type HTTP_X_REAL_IP that passes your IP.').'</small>
</td>
<td><button type="submit" name="newproxy_submit" class="btn btn-success btn-sm" title="'.abTranslate('Add').'"><i class="bi bi-plus-square"></i></button>
</td>
</tr>
</form>
</tbody>
</table>
';
