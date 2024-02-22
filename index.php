<!DOCTYPE html>
<html lang="en">
<head>
	<title>Server status</title>
	<meta content="text/html" charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<style>
pre {
    overflow-x: auto;
	max-width: 60vw;
}

pre code {
    word-wrap: normal;
    white-space: pre;
}

.memory_percent {
    width: 60px;
}

.memory_value {
    width: 180px;
}

@media screen and (max-width: 1000px) {
    .memory_td {
        display: flex;
        flex-direction: column;
    }
    .memory_progress {
        width: 100%;
    }
}

	</style>
</head>
<html><div class="container">
<?php
/*

 *
 * @author      Trường An Phạm Nguyễn
 * @copyright   2019, The authors
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE
 *        http://www.gnu.org/licenses/agpl-3.0.html
 *
 * Jul 27, 2013

Original author:
*       Disclaimer Notice(s)                                                          
*       ex: This code is freely given to you and given "AS IS", SO if it damages      
*       your computer, formats your HDs, or burns your house I am not the one to
*       blame.                                                                     
*       Moreover, don't forget to include my copyright notices and name.               
*   +------------------------------------------------------------------------------+
*       Author(s): Crooty.co.uk (Adam C)                                    
*   +------------------------------------------------------------------------------+

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
$start_time = microtime(TRUE);

$data = "";
$data .= '
<div class="card my-2">
  <h4 class="card-header text-center">
    Service status
  </h4>
  <div class="card-body pb-0">
';


//configure script
$timeout = "1";

//set service checks
/* 
The script will open a socket to the following service to test for connection.
Does not test the fucntionality, just the ability to connect
Each service can have a name, port and the Unix domain it run on (default to localhost)
*/
$services = array();


$services[] = array("port" => "80",       "service" => "💻 Web server",                  "ip" => "") ;
$services[] = array("port" => "3306",     "service" => "📦 MySql",                   "ip" => "") ;
$services[] = array("port" => "5432",     "service" => "📦 PostgreSql",                   "ip" => "") ;
$services[] = array("port" => "22",       "service" => "🔑 Open SSH",				"ip" => "") ;


//begin table for status
$data .= "<small><table  class='table table-striped table-sm '><thead><tr><th>Service</th><th>Port</th><th>Status</th></tr></thead>";
foreach ($services  as $service) {
	if($service['ip']==""){
	   $service['ip'] = "localhost";
	}
	$data .= "<tr><td>" . $service['service'] . "</td><td>". $service['port'];

	$fp = @fsockopen($service['ip'], $service['port'], $errno, $errstr, $timeout);
	if (!$fp) {
		$data .= "</td><td class='table-danger'>✖</td></tr>";
	  //fclose($fp);
	} else {
		$data .= "</td><td class='table-success'>✅</td></tr>";
		fclose($fp);
	}

}  
//close table
$data .= "</table></small>";
$data .= '
  </div>
</div>
';
echo $data;


/* =====================================================================
//
// ////////////////// SERVER INFORMATION  /////////////////////////////////
//
//
* =======================================================================/*/

$data1 = "";
$data1 .= '
<div class="card mb-2">
  <h4 class="card-header text-center">
    Server information
  </h4>
  <div class="card-body">
';

$data1 .= "<table  class='table table-sm mb-0'>";
// $data1 .= "<div class='table-responsive'><table  class='table table-sm mb-0'>";

//GET SERVER LOADS
$loadresult = @exec('uptime');  
preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$loadresult,$avgs);


//GET SERVER UPTIME
$uptime = explode(' up ', $loadresult);
$uptime = explode(',', $uptime[1]);
$uptime = $uptime[0].', '.$uptime[1];

$phpload = round(memory_get_usage() / 1000000,2);
$end_time = microtime(TRUE);
$time_taken = $end_time - $start_time;
$total_time = round($time_taken,4);

//Get the disk space
function getSymbolByQuantity($bytes) {
	$symbol = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
	$exp = floor(log($bytes)/log(1024));
	
	return sprintf('%.2f <small>'.$symbol[$exp].'</small>', ($bytes/pow(1024, floor($exp))));
}
function percent_to_color($p){
	if($p < 30) return 'success';
	if($p < 45) return 'info';
	if($p < 60) return 'primary';
	if($p < 75) return 'warning';
	return 'danger';
}
function format_storage_info($disk_space, $disk_free, $disk_name, $percent){
	$str = "";
	$disk_free_precent = 100 - round($disk_free*1.0 / $disk_space*100, 2);
		$str .= '<div class="col p-0 d-inline-flex memory_td">';
		$str .= "<span class='mr-2 memory_percent'>" . $percent ." %</span>";
		$str .= "<span class='mr-2 memory_value'>" . getSymbolByQuantity($disk_free) . ' / '. getSymbolByQuantity($disk_space) ."</span>";
		$str .= '
<div class="memory_progress progress flex-grow-1 align-self-center">
  <div class="progress-bar progress-bar-striped progress-bar-animated ';
		$str .= 'bg-' . percent_to_color($disk_free_precent) .'
  " role="progressbar" style="width: '.$disk_free_precent.'%;" aria-valuenow="'.$disk_free_precent.'" aria-valuemin="0" aria-valuemax="100">'.$disk_free_precent.'%</div>
</div>
</div>		';

	return $str;

}

function get_disk_free_status($disks){
	$str="";
	$max = 5;
	foreach($disks as $disk){
		if(strlen($disk["name"]) > $max) 
			$max = strlen($disk["name"]);
	}
	
	foreach($disks as $disk){
		$disk_space = disk_total_space($disk["path"]);
		$disk_free = disk_free_space($disk["path"]);
    $diskusage = round(($disk_space-$disk_free)/$disk_space*100);

		$str .= format_storage_info($disk_space, $disk_free, $disk['name'], $diskusage);

	}
	return $str;
}
function badge($str, $type){
	return "<span class='badge badge-" . $type . " ' >$str</span>";
}

//Get ram usage
$total_mem = preg_split('/ +/', @exec('grep MemTotal /proc/meminfo'));
$total_mem = $total_mem[1];
$free_mem = preg_split('/ +/', @exec('grep MemFree /proc/meminfo'));
$cache_mem = preg_split('/ +/', @exec('grep ^Cached /proc/meminfo'));

$free_mem = $free_mem[1] + $cache_mem[1];

$load = sys_getloadavg();
//$cpuload = round(floatval($load[0]) * 100, 2);
var_dump($load);
$cpuload = $load[0];

$free = shell_exec('free');
$free = (string)trim($free);
$free_arr = explode("\n", $free);
$mem = explode(" ", $free_arr[1]);
$mem = array_filter($mem, function($value) { return ($value !== null && $value !== false && $value !== ''); }); // removes nulls from array
$mem = array_merge($mem); // puts arrays back to [0],[1],[2] after
$memtotal = round($mem[1] / 1000000,2);
$memused = round($mem[2] / 1000000,2);
$memfree = round($mem[3] / 1000000,2);
$memshared = round($mem[4] / 1000000,2);
$memcached = round($mem[5] / 1000000,2);
$memavailable = round($mem[6] / 1000000,2);
$memusage = round(($memused/$memtotal)*100);

//Get top mem usage
$tom_mem_arr = array();
$top_cpu_use = array();

//-- The number of processes to display in Top RAM user
$i = 5;


/* ps command:
-e to display process from all user
-k to specify sorting order: - is desc order follow by column name
-o to specify output format, it's a list of column name. = suppress the display of column name
head to get only the first few lines 
*/
exec("ps -e k-rss -o rss,args | head -n $i", $tom_mem_arr, $status);
exec("ps -e k-pcpu -o pcpu,args | head -n $i", $top_cpu_use, $status);


$top_mem = implode('<br/>', $tom_mem_arr );
$top_mem = "<pre class='mb-0 '><code>" . $top_mem . "</code></pre>";

$top_cpu = implode('<br/>', $top_cpu_use );
$top_cpu = "<pre class='mb-0 '><code>" . $top_cpu. "</code></pre>";

$data1 .= "<tr><td>📟 Server Name        </td><td>" . $_SERVER['SERVER_NAME'] . "</td></tr>";
$data1 .= "<tr><td>🏠 Server Addr        </td><td>" . $_SERVER['SERVER_ADDR'] . "</td></tr>";

$data1 .= "<tr><td>🌀 PHP Version</td><td>" . phpversion(). "</td></tr>";
$data1 .= "<tr><td>🏋️ PHP Load</td><td>" . $phpload. "</td></tr>";
$data1 .= "<tr><td>⏱️ Load Time</td><td>" . $total_time. "</td></tr>";

$data1 .= "<tr><td>💙 Average load</td><td><h5>". badge($avgs[1],'secondary'). ' ' .badge($avgs[2], 'secondary') . ' ' . badge( $avgs[3], 'secondary') . " </h5></td>\n";
$data1 .= "<tr><td>🕐 Uptime</td><td>$uptime                     </td></tr>";


$disks = array();

/*
* The disks array list all mountpoint you wan to check freespace
* Display name and path to the moutpoint have to be provide, you can 
*/
$disks[] = array("name" => "local" , "path" => getcwd()) ;
// $disks[] = array("name" => "Your disk name" , "path" => '/mount/point/to/that/disk') ;


$data1 .= "<tr><td>💾 Disk free        </td><td>" . get_disk_free_status($disks) . "</td></tr>";

$data1 .= "<tr><td>📋 RAM free        </td><td>". format_storage_info($total_mem *1024, $free_mem *1024, '', $memusage) ."</td></tr>";
$data1 .= "<tr><td>🎶 CPU Usage        </td><td>". $cpuload . "%</td></tr>";
$data1 .= "<tr><td>📈 Top RAM user    </td><td><small>$top_mem</small></td></tr>";
$data1 .= "<tr><td>📈 Top CPU user    </td><td><small>$top_cpu</small></td></tr>";

$data1 .= "</table>";
// $data1 .= '  </div></div>';
$data1 .= '  </div>';
echo $data1;  

/* =============================================================================
*
* DISPLAY BANDWIDTH STATISTIC, REQUIRE VNSTAT INSTALLED AND PROPERLY CONFIGURED.
*
* ===============================================================================s
*/


$data2 = "";
$data2 .=  '
<div class="card mb-2">
  <h4 class="card-header text-center">
    vnstat Network traffic
  </h4>
  <div class="card-body text-center">
';


$data2 .="<span class=' d-block'><pre class='d-inline-block text-left'><small>";
$traffic_arr = array();
exec('vnstat -m', $traffic_arr, $status);

///for testing
$traffic = "
enp0s20  /  monthly

month        rx      |     tx      |    total    |   avg. rate
------------------------+-------------+-------------+---------------
Sep '18     36.60 GiB |    7.04 GiB |   43.64 GiB |  144.62 kbit/s
Oct '18    400.69 GiB |    1.19 TiB |    1.58 TiB |    5.19 Mbit/s
Nov '18    393.52 GiB |    2.19 TiB |    2.57 TiB |    8.72 Mbit/s
Dec '18    507.28 GiB |    2.05 TiB |    2.55 TiB |    8.37 Mbit/s
Jan '19    269.01 GiB |    1.39 TiB |    1.65 TiB |    7.51 Mbit/s
------------------------+-------------+-------------+---------------
estimated    371.92 GiB |    1.92 TiB |    2.29 TiB |
";
/// for real
$traffic = implode("\n", $traffic_arr);

$data2 .="$traffic</small></pre></span>";

echo $data2;
?>
</div></html>
