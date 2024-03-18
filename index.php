<?php include 'functions.php';?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
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

pre.command_result {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
<body><div class="container">
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

$alerts = [];

$service_table = '
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
//$services[] = array("port" => "3306",     "service" => "📦 MySql",                   "ip" => "") ;
$services[] = array("port" => "5432",     "service" => "📦 PostgreSql",                   "ip" => "") ;
$services[] = array("port" => "22",       "service" => "🔑 Open SSH",				"ip" => "") ;


//begin table for status
$service_table .= "<small><table  class='table table-striped table-sm '><thead><tr><th>Service</th><th>Port</th><th>Status</th></tr></thead>";
foreach ($services  as $service) {
	if($service['ip']==""){
	   $service['ip'] = "localhost";
	}
	$service_table .= "<tr><td>" . $service['service'] . "</td><td>". $service['port'] . '</td>';

	$fp = @fsockopen($service['ip'], $service['port'], $errno, $errstr, $timeout);
	if (!$fp) {
      $alerts[] = "Service down : " . $service['service'];
		$service_table .= "<td class='table-danger'>✖</td></tr>";
	} else {
		$service_table .= "<td class='table-success'>✅</td></tr>";
		fclose($fp);
	}
}  
//close table
$service_table .= "</table></small>";
$service_table .= '
  </div>
</div>
';
echo $service_table;


// web site checks
$ping_table = '
<div class="card my-2">
  <h4 class="card-header text-center">
    Websites status
  </h4>
  <div class="card-body pb-0">
';

$websites = [
  'jaden-achain.dev',
  'store.jaden-achain.dev',
  'home.jaden-achain.dev',
  'kitchen.jaden-achain.dev',
  'target.jaden-achain.dev',
  'arkemie.jaden-achain.dev',
  'archery-target.netlify.app',
  'kitchen-party.fr',
  'arkemie.net',
];

//begin table for status
$ping_table .= "<small><table  class='table table-striped table-sm '><thead><tr><th>Website</th><th>Status</th></tr></thead>";
foreach ($websites  as $website) {
	$ping = check_website("https://$website");

  $status =  $ping[0] >= 200 && $ping[0] < 300;

  $ping_table .= "<tr><td>" . $website . "</td>";

	if (!$status) {
    $error_info = $ping[0] . " : " . $ping[1]." : ".$ping[2];

    $alerts[] = "Website down : " . $website . ' / ' . $error_info;

    $ping_table .= "<td class='table-danger'>".$error_info."</td></tr>";
	} else {
      $ping_table .= "<td class='table-success'>✅</td></tr>";
	}
}
//close table
$ping_table .= "</table></small>";
$ping_table .= '
  </div>
</div>
';
echo $ping_table;


/* =====================================================================
//
// ////////////////// SERVER INFORMATION  /////////////////////////////////
//
//
* =======================================================================/*/

$server_table = '
<div class="card mb-2">
  <h4 class="card-header text-center">
    Server information
  </h4>
  <div class="card-body">
';

$server_table .= "<table  class='table table-sm mb-0'>";

//GET SERVER LOADS
$loadresult = @exec('uptime');

//GET SERVER UPTIME
$uptime = explode(' up ', $loadresult);
$uptime = explode(',', $uptime[1]);
$uptime = $uptime[0].', '.$uptime[1];

$phpload = round(memory_get_usage() / 1000000,2);
$end_time = microtime(TRUE);
$time_taken = $end_time - $start_time;
$total_time = round($time_taken,4);

//Get ram usage
$total_mem = preg_split('/ +/', @exec('grep MemTotal /proc/meminfo'));
$total_mem = $total_mem[1];
$free_mem = preg_split('/ +/', @exec('grep MemFree /proc/meminfo'));
$cache_mem = preg_split('/ +/', @exec('grep ^Cached /proc/meminfo'));

$free_mem = $free_mem[1] + $cache_mem[1];

$load = sys_getloadavg();
$cpuload1 = round($load[0] * 100, 2);
$cpuload5 = round($load[1] * 100, 2);
$cpuload15 = round($load[2] * 100, 2);

//Get top mem usage
$tom_mem_arr = array();
$top_cpu_use = array();

$memCount = 16;
$cpuCount = 6;

/* ps command:
-e to display process from all user
-k to specify sorting order: - is desc order follow by column name
-o to specify output format, it's a list of column name. = suppress the display of column name
head to get only the first few lines 
*/
exec("ps -e k-rss -o pid,%mem,args | head -n $memCount", $tom_mem_arr, $status);
exec("ps -e k-pcpu -o pid,%cpu,args | head -n $cpuCount", $top_cpu_use, $status);


$top_mem = implode('<br/>', $tom_mem_arr );
$top_mem = "<pre class='mb-0 command_result'><code>" . $top_mem . "</code></pre>";

$top_cpu = implode('<br/>', $top_cpu_use );
$top_cpu = "<pre class='mb-0 command_result'><code>" . $top_cpu. "</code></pre>";

$server_table .= "<tr><td>🏠 Server Addr        </td><td>" . $_SERVER['SERVER_ADDR'] . "</td></tr>";

$server_table .= "<tr><td>🌀 PHP Version</td><td>" . phpversion(). "</td></tr>";
$server_table .= "<tr><td>🏋️ PHP Load</td><td>" . $phpload. "</td></tr>";
$server_table .= "<tr><td>⏱️ Load Time</td><td>" . $total_time. "</td></tr>";

$server_table .= "<tr><td>🕐 Uptime</td><td>$uptime                     </td></tr>";
$server_table .= "<tr><td>🕐 Time</td><td>".date('d/m/Y H:i:s')."                     </td></tr>";

$disks = array();

/*
* The disks array list all mountpoint you wan to check freespace
* Display name and path to the moutpoint have to be provide, you can 
*/
$disks[] = array("name" => "local" , "path" => getcwd()) ;
// $disks[] = array("name" => "Your disk name" , "path" => '/mount/point/to/that/disk') ;

$server_table .= "<tr><td>💾 Disk free        </td><td>" . get_disk_free_status($disks, $alerts) . "</td></tr>";

$server_table .= "<tr><td>📋 RAM free        </td><td>". format_storage_info($total_mem *1024, $free_mem *1024, '', 'RAM', $alerts) ."</td></tr>";
$server_table .= "<tr><td>📈 Top " . ($memCount - 1) .  " RAM    </td><td><small>$top_mem</small></td></tr>";
$server_table .= "<tr><td>💙 CPU Usage        </td><td>" .
    format_cpu_usage("Last minute", $cpuload1, $alerts).
    format_cpu_usage("Last 5 minutes", $cpuload5, $alerts).
    format_cpu_usage("Last 15 minutes", $cpuload15, $alerts).
    "</td></tr>";
$server_table .= "<tr><td>📈 Top " . ($cpuCount - 1) .  " CPU    </td><td><small>$top_cpu</small></td></tr>";

$server_table .= "</table></small>";
$server_table .= '
  </div>
</div>
';
echo $server_table;

/* =============================================================================
*
* DISPLAY BANDWIDTH STATISTIC, REQUIRE VNSTAT INSTALLED AND PROPERLY CONFIGURED.
*
* ===============================================================================s
*/

echo exec_commande("vnstat Network traffic", 'vnstat -m');

if(count($alerts) > 0) {
  send_notification($alerts);
}
?>
</div>
</body>
</html>
