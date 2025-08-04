<?php
// счетчики статистики
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Statistics');

// перенос статистики из файлов в базу:
$cron_update_time = (int) trim(@file_get_contents(__DIR__.'/../data/counters_update')) + 0;
if ($ab_config['time'] - $cron_update_time > 599) {
file_put_contents(__DIR__.'/../data/counters_update', $ab_config['time'], LOCK_EX);
require_once(__DIR__.'/../code/cron.php');
$cron_update_time = $ab_config['time'];
}

// подсчет адблоков за сегодня:
$today = date('Ymd', $ab_config['time']); // сегодняшний день
$date1 = (int)strtotime(date("Y-m-d", $ab_config['time']).'T00:00');
$date2 = (int)strtotime(date("Y-m-d", $ab_config['time']).'T23:59');
$sql = "SELECT (SELECT COUNT(DISTINCT ip) FROM hits WHERE passed IN (1, 2) AND date > ".$date1." AND date < ".$date2.") AS total_unique_ips, (SELECT COUNT(DISTINCT ip) FROM hits WHERE passed IN (1, 2) AND adblock = 1 AND date > ".$date1." AND date < ".$date2.") AS adblock_unique_ips";
$result = $antibot_db->query($sql);
$row = $result->fetchArray(SQLITE3_ASSOC);
//echo $row['total_unique_ips']; // - всего уник IP
//echo $row['adblock_unique_ips']; // - уник IP с блокировщиками
if ($row['total_unique_ips'] != 0) {
$percentage = ($row['adblock_unique_ips'] / $row['total_unique_ips']) * 100;
$percentage = round($percentage, 2);
} else {
$percentage = 0;
}

$days_of_week['ru'] = array(
1 => '<span style="color:gray;">Пн</span>',
2 => '<span style="color:gray;">Вт</span>',
3 => '<span style="color:gray;">Ср</span>',
4 => '<span style="color:gray;">Чт</span>',
5 => '<span style="color:gray;">Пт</span>',
6 => '<span style="color:red;">Сб</span>',
7 => '<span style="color:red;">Вс</span>'
);

$days_of_week['en'] = array(
1 => '<span style="color:gray;">Mo</span>',
2 => '<span style="color:gray;">Tu</span>',
3 => '<span style="color:gray;">We</span>',
4 => '<span style="color:gray;">Th</span>',
5 => '<span style="color:gray;">Fr</span>',
6 => '<span style="color:red;">Sa</span>',
7 => '<span style="color:red;">Su</span>'
);

$list = $antibot_db->query("SELECT * FROM counters ORDER BY date DESC LIMIT 30;"); 

$content .= '<p>'.abTranslate('Time left until the next statistics update:').' '.(599 - ($ab_config['time'] - $cron_update_time)).' '.abTranslate('sec.').'</p>
<div class="table-responsive">
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr style="text-align: center;">
<th><small>'.abTranslate('Date').'</small></th>
<th style="color:red;"><small>STOP</small></th>
<th style="color:green;"><small>AUTO</small></th>
<th style="color:green;"><small>CLICK</small></th>
<th style="color:red;"><small>MISS</small></th>
<th style="color:green;"><small>ALLOW</small></th>
<th style="color:blue;"><small>GOODIP</small></th>
<th style="color:red;"><small>BLOCK</small></th>
<th style="color:red;"><small>FAKE</small></th>
<th><small>LOCAL</small></th>
<th class="jscounter"><small>'.abTranslate('Unique').'</small></th>
<th class="jscounter"><small>'.abTranslate('Hits').'</small></th>
<th style="color:blue;"><small>'.abTranslate('GoogleBot').'</small></th>
<th style="color:blue;"><small>'.abTranslate('YandexBot').'</small></th>
<th style="color:blue;"><small>'.abTranslate('BingBot').'</small></th>
<th style="color:red;"><small>AdBlock</small></th>
<th style="color:red;"><small>SQL errors</small></th>
</tr>
</thead>
<tbody>
';
$all_no = 0;
$all_auto = 0;
$all_click = 0;
$all_miss = 0;
$all_allow = 0;
$all_goodip = 0;
$all_uusers = 0;
$all_husers = 0;
$all_google = 0;
$all_yandex = 0;
$all_bing = 0;
$all_block = 0;
$all_fakes = 0;
$all_local = 0;

$all_adbp = 0;
$all_adbc = 0;
$all_sqlerror = 0;
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
$no = $echo['test'] - $echo['auto'] - $echo['click'] - $echo['miss'];
$date1 = date("Y-m-d", strtotime($echo['date']));
$date2 = date("Y-m-d", strtotime($echo['date'])+86400);
if (date("Ymd", $ab_config['time']) == $echo['date']) {$echo['adbpercent'] = $percentage;}
$content .= '<tr style="text-align: right;">
<td>'.date("d.m.Y", strtotime($echo['date'])).' '.$days_of_week[$lang_code][date("N", strtotime($echo['date']))].'</td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=0&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($no).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=1&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['auto']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=2&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['click']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=8&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['miss']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=4&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['allow']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=5&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['goodip']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=6&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['block']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=7&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['fakes']).'</a></td>
<td><a class="text-reset" href="?'.$abw.$abp.'=hits&search=&table=ip&status=3&date1='.$date1.'T00:00&date2='.$date2.'T00:00&operator=equally">'.number_format($echo['local']).'</a></td>
<td class="jscounter">'.number_format($echo['uusers']).'</td>
<td class="jscounter">'.number_format($echo['husers']).'</td>
<td>'.number_format($echo['google']).'</td>
<td>'.number_format($echo['yandex']).'</td>
<td>'.number_format($echo['bing']).'</td>
<td>'.(($echo['adbpercent'] == '') ? '-' : number_format($echo['adbpercent'], 2)).'%</td>
<td>'.number_format($echo['sqlerror']).'</td>
</tr>';
$all_no = $all_no + $no;
$all_auto = $all_auto + $echo['auto'];
$all_click = $all_click + $echo['click'];
$all_miss = $all_miss + $echo['miss'];
$all_allow = $all_allow + $echo['allow'];
$all_goodip = $all_goodip + $echo['goodip'];
$all_uusers = $all_uusers + $echo['uusers'];
$all_husers = $all_husers + $echo['husers'];
$all_google = $all_google + $echo['google'];
$all_yandex = $all_yandex + $echo['yandex'];
$all_bing = $all_bing + $echo['bing'];
$all_block = $all_block + $echo['block'];
$all_fakes = $all_fakes + $echo['fakes'];
$all_local = $all_local + $echo['local'];
if ($echo['adbpercent'] != '') {
$all_adbp = $all_adbp + $echo['adbpercent'];
$all_adbc++;
}
$all_sqlerror = $all_sqlerror + $echo['sqlerror'];
}

if ($all_adbc == 0) {
$all_adbp = 0;
} else {
$all_adbp = round($all_adbp / $all_adbc, 2);
}

$content .= '
<tr style="text-align: right;">
<td><strong>'.abTranslate('Total:').'</strong></td>
<td>'.number_format($all_no).'</td>
<td>'.number_format($all_auto).'</td>
<td>'.number_format($all_click).'</td>
<td>'.number_format($all_miss).'</td>
<td>'.number_format($all_allow).'</td>
<td>'.number_format($all_goodip).'</td>
<td>'.number_format($all_block).'</td>
<td>'.number_format($all_fakes).'</td>
<td>'.number_format($all_local).'</td>
<td class="jscounter">'.number_format($all_uusers).'</td>
<td class="jscounter">'.number_format($all_husers).'</td>
<td>'.number_format($all_google).'</td>
<td>'.number_format($all_yandex).'</td>
<td>'.number_format($all_bing).'</td>
<td>'.$all_adbp.'%</td>
<td>'.$all_sqlerror.'</td>
</tr>
</tbody>
</table>
</div>
<p><strong style="color:red;">STOP</strong> - '.abTranslate('visitors who have not passed the anti-bot check.').'<br />
<strong style="color:green;">AUTO</strong> - '.abTranslate('visitors who have successfully passed the check automatically.').'<br />
<strong style="color:green;">CLICK</strong> - '.abTranslate('visitors who have not passed the automatic check but have clicked on the button.').'<br />
<strong style="color:red;">MISS</strong> - '.abTranslate('visitors who did not pass automatic check and clicked on the wrong button.').'<br />
<strong style="color:green;">ALLOW</strong> - '.abTranslate('visitors who have passed without check according to the allowing rules.').'<br />
<strong style="color:blue;">GOODIP</strong> - '.abTranslate('bots allowed in the rule table #3 (search engine bots) and in the IP rules (table # 1 and 2).').'<br />
<strong style="color:red;">BLOCK</strong> - '.abTranslate('the number of requests blocked according to your rules (by IP, country, language, referrer, PTR).').'<br />
<strong style="color:red;">FAKE</strong> - '.abTranslate('requests by bots that were disguised as good bots in rule table #3.').'<br />
<strong>LOCAL</strong> - '.abTranslate('page views by visitors who have previously passed the anti-bot check (have allowed cookies).').'<br />
<strong>'.abTranslate('Unique').'</strong> - '.abTranslate('unique visitors (unique IPs) recorded by the JS counter.').'<br />
<strong>'.abTranslate('Hits').'</strong> - '.abTranslate('page views recorded by the JS counter.').'<br />
<strong style="color:red;">AdBlock</strong> - '.abTranslate('percentage of visitors (unique IPs) with an ad blocker among those who passed the AUTO and CLICK check.').'<br />
<strong style="color:red;">SQL errors</strong> - '.abTranslate('number of SQL errors during the operation of the AntiBot script. These are mainly errors related to adding new entries to logs. Ideally, there should be none. If there are many, consider disabling some unnecessary logs (LOCAL, BLOCK, GOODIP, ALLOW).').'
</p>
<hr />
<p>'.abTranslate('The counter code for counting unique visitors and pageviews. This code should be inserted into your website template.').'</p>
<textarea class="form-control" rows="5"><script>
var abc = new XMLHttpRequest();
var microtime = Date.now();
var abcbody = "t="+microtime+"&w="+screen.width+"&h="+ screen.height+"&cw="+document.documentElement.clientWidth+"&ch="+document.documentElement.clientHeight;
abc.open("POST", "'.$ab_config['webdir'].'8.php", true);
abc.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
abc.send(abcbody);
</script></textarea>
<hr />
<p>
<form action="?'.$abw.$abp.'=clearcounters" method="post" style="display: inline-block;" onsubmit="return check()">
<input name="clearcounters_submit" type="hidden" value="1">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="clearhits_submit" value="'.abTranslate('Delete all records').'">
</form>
</p>
';

if ($all_uusers == 0 AND $all_husers == 0) {
$content .= '<style>.jscounter {display: none;}</style>';
}
