<?php

include("phpMQTT.php");

if (!function_exists("functions_once"))
{
    function functions_once()
    {
    }

    define("tmeSecondsPerDay", 86400);

// ---------------------------------------------------------------------------
// log to Syslog
// ---------------------------------------------------------------------------

function tell($message)
{
   syslog(LOG_DEBUG, "poold: " . $message);
}

// ---------------------------------------------------------------------------
// Check for mobile browser
// ---------------------------------------------------------------------------

function isMobile()
{
	$useragent = $_SERVER['HTTP_USER_AGENT'];

	if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent,0,4)))
      return 1;

   return 0;
}

// ---------------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------------

function haveLogin()
{
   // $localIP = getHostByName(getHostName());

   if (ipInRange(getClientIp(), $_SESSION['localLoginNetmask']))
      return true;

   if (isset($_SESSION['angemeldet']) && $_SESSION['angemeldet'])
      return true;

   return false;
}

function checkLogin($user, $passwd)
{
   global $mysqli;

   $md5 = md5($passwd);

   if (requestAction("check-login", 5, 0, "$user:$md5", $resonse) == 0)
      return true;

   return false;
}

// ---------------------------------------------------------------------------
// IP In Range
// ---------------------------------------------------------------------------

function ipInRange($ip, $range)
{
   if ($range == "")
      return false;

	if (strpos($range, '/') == false)
		$range .= '/32';

	// $range is in IP/CIDR format eg 127.0.0.1/24

	list($range, $netmask) = explode( '/', $range, 2);

	$range_decimal = ip2long($range);
	$ip_decimal = ip2long($ip);
	$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
	$netmask_decimal = ~ $wildcard_decimal;

	return ($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal);
}

// ---------------------------------------------------------------------------
// Get Client IP
// ---------------------------------------------------------------------------

function getClientIp()
{
   if (!empty($_SERVER['HTTP_CLIENT_IP']))
      $ipAddress = $_SERVER['HTTP_CLIENT_IP'];

   elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))     // whether ip is from proxy
      $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];

   else                                                  // whether ip is from remote address
      $ipAddress = $_SERVER['REMOTE_ADDR'];

   return $ipAddress;
}

// ---------------------------------------------------------------------------
// Check/Create Folder
// ---------------------------------------------------------------------------

function chkDir($path, $rights = 0777)
{
   if (!(is_dir($path) OR is_file($path) OR is_link($path)))
      return mkdir($path, $rights);
   else
      return true;
}

function mysqli_result($res, $row=0, $col=0)
{
   $numrows = mysqli_num_rows($res);

   if ($numrows && $row <= ($numrows-1) && $row >=0)
   {
      mysqli_data_seek($res,$row);
      $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);

       if (isset($resrow[$col]))
         return $resrow[$col];
   }

   return false;
}

// ---------------------------------------------------------------------------
// To Time Ranges String
// ---------------------------------------------------------------------------

function toTimeRangesString($idNameBase, array $post)
{
   $times = "";

   for ($i = 0; $i < 10; $i++)
   {
      $idName = $idNameBase.$i;

      if (isset($post[$idName."From"]) && $post[$idName."From"] != "" &&
          isset($post[$idName."To"]) && $post[$idName."To"] != "")
      {
         $from = htmlspecialchars($post[$idName."From"]);
         $to = htmlspecialchars($post[$idName."To"]);

         if ($times != "")
            $times = $times.",";

         $times = $times.$from."-".$to;
      }
   }

   return $times;
}

// ---------------------------------------------------------------------------
// Request Action (for JS Interface)
// ---------------------------------------------------------------------------

function reqAction($cmd, $timeout, $address, $data)
{
   $response = "";
   return requestAction($cmd, $timeout, $address, $data, $response);
}

// ---------------------------------------------------------------------------
// Request Action
// ---------------------------------------------------------------------------

function requestAction($cmd, $timeout, $address, $data, &$response)
{
   global $mysqli;

   $timeout = time() + $timeout;
   $response = "";

   $address = mysqli_real_escape_string($mysqli, $address);
   $data = mysqli_real_escape_string($mysqli, $data);
   $cmd = mysqli_real_escape_string($mysqli, $cmd);

   tell("requesting ". $cmd . " with " . $address . ", '" . $data . "'");

   $mysqli->query("insert into jobs set requestat = now(), state = 'P', command = '$cmd', address = '$address', data = '$data'")
      or die("Error" . $mysqli->error);
   $id = $mysqli->insert_id;

   wakeupFor($cmd);

   while (time() < $timeout)
   {
      usleep(10000);

      $result = $mysqli->query("select * from jobs where id = $id and state = 'D'")
         or die("Error" . $mysqli->error);

      if ($result->num_rows)
      {
         $buffer = mysqli_result($result, 0, "result");
         list($state, $response) = explode(":", $buffer, 2);

         if ($state == "fail")
            return -2;

         if ($response == "BDATA")
            $response = mysqli_result($result, 0, "bdata");

         return 0;
      }
   }

   tell("timeout on " . $cmd);

   return -1;
}

// ---------------------------------------------------------------------------
// Wakeup For 'command'
// ---------------------------------------------------------------------------

function wakeupFor($cmd)
{
   // MQTT client id to use for the device. "" will generate a client id automatically

   $mqtt = new bluerhinos\phpMQTT("192.168.200.101", 1883, "poolPHP");

   $message = "{ \"command\" : \"$cmd\" }";

   if ($mqtt->connect(true, NULL, "", ""))
   {
      $topic = "poold2mqtt/light/wakeup/set";

      $mqtt->publish($topic, $message, 0);
      $mqtt->close();
   }
}

// ---------------------------------------------------------------------------
// Date Picker
// ---------------------------------------------------------------------------

function datePicker($title, $name, $year, $day, $month)
{
   $html = "";
   $startyear = date("Y")-10;
   $endyear = date("Y")+1;

   $months = array('','Januar','Februar','März','April','Mai',
                   'Juni','Juli','August', 'September','Oktober','November','Dezember');

   if ($title != "")
      $html = $title . ": ";

   // day

   $html .= "  <select name=\"" . $name . "day\">\n";

   for ($i = 1; $i <= 31; $i++)
   {
      $sel = $i == $day  ? "SELECTED" : "";
      $html .= "     <option value='$i' " . $sel . ">$i</option>\n";
   }

   $html .= "  </select>\n";

   // month

   $html .= "  <select name=\"" . $name . "month\">\n";

   for ($i = 1; $i <= 12; $i++)
   {
      $sel = $i == $month ? "SELECTED" : "";
      $html .= "     <option value='$i' " . $sel . ">$months[$i]</option>\n";
   }

   $html.="  </select>\n";

   // year

   $html .= "  <select name=\"" . $name . "year\">\n";

   for ($i = $startyear; $i <= $endyear; $i++)
   {
      $sel = $i == $year  ? "SELECTED" : "";
      $html .= "     <option value='$i' " . $sel . ">$i</option>\n";
   }

   $html .= "  </select>\n";

   return $html;
}

// ---------------------------------------------------------------------------
// HTML report of Daemon State
// ---------------------------------------------------------------------------

function printDaemonState()
{
   global $webVersion;
   global $mysqli;
   global $daemonTitle;

   // -------------------------
   // get last time stamp

   $result = $mysqli->query("select DATE_FORMAT(max(time),'%d. %M %Y   %H:%i') as maxPretty, " .
                            "DATE_FORMAT(max(time),'%H:%i:%S') as maxPrettyShort from samples;")
      or die("Error" . $mysqli->error);

   $row = $result->fetch_assoc();
   $maxPretty = $row['maxPretty'];
   $maxPrettyShort = $row['maxPrettyShort'];

   // ------------------
   // State of Daemon

   $daemonState = requestAction("daemon-state", 3, 0, "", $response);
   $load = "";

   if ($daemonState == 0)
      list($dNext, $dVersion, $dSince, $load) = explode("#", $response, 4);

   $result = $mysqli->query("select * from samples where time >= CURDATE()")
      or die("Error" . $mysqli->error);
   $dCountDay = $result->num_rows;

   echo "        <div class=\"rounded-border daemonInfo\" id=\"divDaemondState\">\n";

   if ($daemonState == 0)
   {
      echo  "              <div id=\"aStateOk\"><span>$daemonTitle ONLINE</span>   </div>\n";
      echo  "              <div><span>Läuft seit:</span>            <span>$dSince</span>       </div>\n";
      echo  "              <div><span>Messungen heute:</span>       <span>$dCountDay</span>    </div>\n";
      echo  "              <div><span>Letzte Messung:</span>        <span>$maxPrettyShort</span> </div>\n";
      echo  "              <div><span>Nächste Messung:</span>       <span>$dNext</span>        </div>\n";
      echo  "              <div><span>Version (poold / webif):</span> <span>$dVersion / $webVersion</span></div>\n";
      echo  "              <div><span>CPU-Last:</span>              <span>$load</span>           </div>\n";
   }
   else
   {
      echo  "          <div id=\"aStateFail\">ACHTUNG:<br/>$daemonTitle OFFLINE</div>\n";
   }

   echo "        </div>\n"; // Deamon Info
}

// ---------------------------------------------------------------------------
// Test Mail
// ---------------------------------------------------------------------------

function sendTestMail($subject, $body, &$resonse, $alertid = "")
{
   global $mysqli;
   $state = 0;

   if ($alertid != "")
      $state = requestAction("test-alert-mail", 5, 0, "$alertid", $resonse);
   else
      $state = requestAction("test-mail", 5, 0, "$subject:$body", $resonse);

   return $state == 0 ? true : false;
}

// ---------------------------------------------------------------------------
// Seperator
// ---------------------------------------------------------------------------

function seperator($title, $top = 0, $class = "seperatorTitle1")
{
   echo "        <div class=\"rounded-border " . $class . "\">$title</div>\n";
}

// ---------------------------------------------------------------------------
// Read Config Item
// ---------------------------------------------------------------------------

function readConfigItem($name, &$value, $default = "")
{
   global $mysqli;

   // new version - read direct from config table

   $result = $mysqli->query("select value from config where owner = 'poold' and name = '$name'")
         or die("Error" . $mysqli->error);

   if ($result->num_rows > 0)
     $value = mysqli_result($result, 0, "value");
   else
     $value = $default;

   return 0;
}

// ---------------------------------------------------------------------------
// Text Config items
// ---------------------------------------------------------------------------

function configStrItem($flow, $title, $name, $value, $comment = "", $width = 200, $ro = "\"", $ispwd = false)
{
   $end = htmTags($flow);

   echo "          <span>$title:</span>\n";

   if ($ispwd)
      echo "          <span><input class=\"rounded-border input\" type=\"password\" name=\"$name\" value=\"$value\"/></span>\n";
   else
      echo "          <span><input class=\"rounded-border input\" style=\"$ro type=\"text\" name=\"$name\" value=\"$value\"/></span>\n";

   if ($comment != "")
      echo "          <span class=\"inputComment\">($comment)</span>\n";

   echo $end;
}

// ---------------------------------------------------------------------------
// Number Config items
// ---------------------------------------------------------------------------

function configNumItem($flow, $title, $name, $value, $comment = "", $min = 0, $max = 100)
{
   $end = htmTags($flow);

   echo "          <span>$title:</span>\n";
   echo "          <span><input class=\"rounded-border inputNum\" type=\"number\" min=\"$min\" max=\"$max\" name=\"$name\" value=\"$value\"/></span>\n";

   if ($comment != "")
      echo "          <span class=\"inputComment\">($comment)</span>\n";

   echo $end;
}

// ---------------------------------------------------------------------------
// Float Config items
// ---------------------------------------------------------------------------

function configFloatItem($flow, $title, $name, $value, $comment = "", $min = 0, $max = 100)
{
   $step = 0.1;
   $end = htmTags($flow);

   echo "          <span>$title:</span>\n";
   echo "          <span><input class=\"rounded-border inputFloat\" type=\"number\" step=\"$step\" min=\"$min\" max=\"$max\" name=\"$name\" value=\"$value\"/></span>\n";

   if ($comment != "")
      echo "          <span class=\"inputComment\">($comment)</span>\n";

   echo $end;
}

// ---------------------------------------------------------------------------
// Time Range Config Item
// ---------------------------------------------------------------------------

function configTimeRangesItem($flow, $title, $name, $ranges, $comment = "")
{
    $end = htmTags($flow);
    $title = $title != "" ? $title.":" : "";
    $aRanges = explode(",", $ranges);

    $i = 0;

    for ( ; $i < count($aRanges); $i++)
    {
       echo "          <span>$title</span>\n";

       if ($aRanges[$i] == "")
          continue;

       list($from, $to) = explode("-", $aRanges[$i], 2);

       $nameFrom = $name.$i."From";
       $nameTo = $name.$i."To";

       echo "          <span>\n";
       echo "            <input type=\"text\" class=\"rounded-border inputTime\" name=\"$nameFrom\" value=\"$from\"/> -";
       echo "            <input type=\"text\" class=\"rounded-border inputTime\" name=\"$nameTo\" value=\"$to\"/>\n";
       echo "          </span>\n";
       echo "          <span></span>\n";

       $title = "";
    }

    $nameFrom = $name.$i."From";
    $nameTo = $name.$i."To";

    echo "          <span>$title</span>\n";
    echo "          <span>\n";
    echo "            <input type=\"text\" class=\"rounded-border inputTime\" name=\"$nameFrom\" value=\"\"/> -";
    echo "            <input type=\"text\" class=\"rounded-border inputTime\" name=\"$nameTo\" value=\"\"/>\n";
    echo "          </span>\n";

    if ($comment != "")
        echo "          <span class=\"inputComment\">($comment)</span>\n";
    else
        echo "          <span class=\"inputComment\"></span>\n";

    echo $end;
}

// ---------------------------------------------------------------------------
// Checkbox Config items
// ---------------------------------------------------------------------------

function configBoolItem($flow, $title, $name, $value, $comment = "", $attributes = "")
{
   $end = htmTags($flow);

   echo "          <span>$title:</span>\n";
   echo "          <span><input type=\"checkbox\" class=\"rounded-border input\" name=\""
      . $name . "\" " . $attributes . ($value ? " checked" : "") . "/></span>\n";

   if ($comment != "")
      echo "          <span class=\"inputComment\">($comment)</span>\n";

   echo $end;
}

// ---------------------------------------------------------------------------
// Option Config Item
// ---------------------------------------------------------------------------

function configOptionItem($flow, $title, $name, $value, $options, $comment = "", $attributes = "")
{
   $end = htmTags($flow);

   echo "          <span>$title:</span>\n";
   echo "          <span>\n";
   echo "            <select class=\"rounded-border input\" name=\"" . $name . "\" "
      . $attributes . ">\n";

   foreach (explode(" ", $options) as $option)
   {
      $opt = explode(":", $option);
      $sel = ($value == $opt[1]) ? "SELECTED" : "";

      echo "              <option value=\"$opt[1]\" " . $sel . ">$opt[0]</option>\n";
   }

   echo "            </select>\n";
   echo "          </span>\n";

   if ($comment != "")
      echo "          <span class=\"inputComment\">($comment)</span>\n";

   echo $end;
}

// ---------------------------------------------------------------------------
// Set Start and End Div-Tags
// ---------------------------------------------------------------------------

function htmTags($flow)
{
   $end = "";

   switch ($flow)
   {
      case 1:           // 'begin' input div block
         echo   "        <div>\n";
         break;
   	case 2:           // 'end/begin' input div block
         echo   "        </div>\n        <div>\n";
         break;
   	case 3:           // 'begin/end' input div block
         echo   "        <div>\n" ;
         $end = "        </div>\n";
         break;
   	case 4:           // 'end' input div block
         echo   "        \n" ;
         $end = "        </div>\n";
         break;
   	case 5:           // '' input div block
         echo   "        \n";
         break;
   	case 6:           // 'end/begin/end' input div block
         echo   "        </div>\n        <div>\n" ;
         $end = "        </div>\n";
         break;
   	case 7:           // '' input div block
         echo   "        \n";
         break;
   }

   return $end;
}

// ---------------------------------------------------------------------------
// Pretty Unit
// ---------------------------------------------------------------------------

function prettyUnit($u)
{
    $unit = "";

    if ($u == "zst" || $u == "dig")      // Zustand
        $unit = "";
    else if ($u == "U")                  // Umdrehungen/Minute
        $unit = " U/min";
    else if ($u == "°")                  // Temperatur
        $unit = "°C";
    else if ($u == "T")                  // Datum /Urzeit
        $unit = "";
    else if ($u == "h")                  // Stunden
        $unit = "Stunden";
    else if ($u == "R")                  // Ohmscher Widerstand
        $unit = "Ohm";
    else
        $unit = $u;                      // all other

    return $unit;
}

// ---------------------------------------------------------------------------
// build Id List
// ---------------------------------------------------------------------------

function buildIdList($tuples, &$sensors, &$addrWhere, &$ids)
{
    $addrWhere = "";
    $sensors = preg_split("/[\s,]+/", $tuples, -1, PREG_SPLIT_NO_EMPTY);

    if (count($sensors) > 0)
    {
        for ($i = 0; $i < count($sensors); $i++)
        {
            $sensor = preg_split("/:/", $sensors[$i]);

            if ($i > 0)
                $addrWhere = $addrWhere." or ";

            if (count($sensor) < 2)
                $sensor[1] = "W1";

            $addrWhere = $addrWhere."(s.address = $sensor[0] and s.type = '$sensor[1]')";
            $hex = dechex(intval($sensor[0], 0));
            $ids[$i] = "0x".$hex.":".$sensor[1];
        }
    }

    return 0;
}

}  // "functions_once"
?>
