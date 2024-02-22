<?php


//Get the disk space
function getSymbolByQuantity($bytes) {
    $symbol = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
    $exp = floor(log($bytes)/log(1024));

    return sprintf('%.2f <small>'.$symbol[$exp].'</small>', ($bytes/pow(1024, floor($exp))));
}

function check_website($url)
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_USERAGENT      => "spider", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 5,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );

    $data = curl_exec($ch);
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$httpcode, $err, $errmsg];
}

function percent_to_color($p){
    if($p < 30) return 'success';
    if($p < 45) return 'info';
    if($p < 60) return 'primary';
    if($p < 75) return 'warning';
    return 'danger';
}

function format_storage_info($disk_space, $disk_free, $disk_name, $type, &$alerts)
{
    $disk_free_precent = 100 - round($disk_free*1.0 / $disk_space*100, 2);

    if($disk_free_precent > 80) {
        $alerts[] = $type . " usage : " . $disk_free_precent;
    }

    $str = '<div class="col p-0 d-inline-flex memory_td">';
    $str .= "<span class='mr-2 memory_value'>" . getSymbolByQuantity($disk_free) . ' / '. getSymbolByQuantity($disk_space) ."</span>";
    $str .= progress_bar($disk_free_precent);
    $str .= '</div>';

    return $str;
}

function format_cpu_usage($type, $cpu_usage_percent, &$alerts)
{
    if($cpu_usage_percent > 80) {
        $alerts[] = $type . " cpu usage : " . $cpu_usage_percent;
    }

    $str = '<div class="col p-0 d-inline-flex memory_td">';
    $str .= "<span class='mr-2 memory_value'>" . $type ."</span>";
    $str .= progress_bar($cpu_usage_percent);
    $str .= '</div>';

    return $str;
}

function progress_bar($percent) {
    $str = '
    <div class="memory_progress progress flex-grow-1 align-self-center">
      <div class="progress-bar progress-bar-striped progress-bar-animated ';
        $str .= 'bg-' . percent_to_color($percent) .'
      " role="progressbar" style="width: '.$percent.'%;" aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100">'.$percent.'%
      </div>
  </div>';

    return $str;
}

function get_disk_free_status($disks, &$alerts) {
    $str="";
    $max = 5;
    foreach($disks as $disk){
        if(strlen($disk["name"]) > $max)
            $max = strlen($disk["name"]);
    }

    foreach($disks as $disk){
        $disk_space = disk_total_space($disk["path"]);
        $disk_free = disk_free_space($disk["path"]);

        $str .= format_storage_info($disk_space, $disk_free, $disk['name'], 'Disk', $alerts);

    }
    return $str;
}

function badge($str, $type) {
    return "<span class='badge badge-" . $type . " ' >$str</span>";
}

function exec_commande($block_name, $command) {
    $html =  '
        <div class="card mb-2">
          <h4 class="card-header text-center">
            '.$block_name.'
          </h4>
          <div class="card-body text-center">
        ';


    $html .="<span class=' d-block'><pre class='d-inline-block text-left'><small>";
    $command_result_str = array();
    exec($command, $command_result_str, $status);
    $command_result_array = implode("\n", $command_result_str);

    $html .="$command_result_array</small></pre></span>";

    return $html.'</div></div>';
}

function send_notification($alerts) {
    $message = "Alertes :" . join("\r\n    - ", $alerts);

    // Dans le cas où nos lignes comportent plus de 70 caractères, nous les coupons en utilisant wordwrap()
    $message = wordwrap($message, 70, "\r\n");

    $from = 'Jaden Achain <noreply@jaden-achain.dev>';
    $to = $reply = 'jaden.achain@gmail.com';
    $subject = '[Server] Alertes';
    $headers = 'From: ' . $from . "\r\n" .
        'Reply-To: ' . $reply . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}

