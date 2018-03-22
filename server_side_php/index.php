<?php
/*

This file will spit out any of the POST and GET variables that are sent to it

*/

$this_dir = dirname(__FILE__);

$currentCookieParams = session_get_cookie_params();

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

session_set_cookie_params( 
    $currentCookieParams["secure"], 
    $currentCookieParams["httponly"] 
);

$not_accepted = array();

if(session_id() == "")
{
	session_start(); # ...lines in every file in the sequence!!! 
} 

foreach( $_GET as $key => $value ) 
{ 
	switch ( $key ) {
		case "a":	// Should only put authorized keys into the SESSION memoryspace
			$_SESSION[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING);
            write_to_log("log", "[GET:" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "] - session:KEY AUTHORIZED:: " . $key . " = " . $_SESSION[$key], $log_folder, true);
        break;
		default:
			//$_SESSION[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_STRING);
            $not_accepted["GET"][$key] = $value;
			write_to_log("log", "[GET:" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "] - session:KEY NOT AUTHORIZED:" . $key . " = " . $value, $log_folder, FALSE);
		break;		
	}
} 

foreach( $_POST as $key => $value ) 
{ 
	switch ( $key ) {
        // Input Filtering
		case "abcdefghijklmnopqrstuvwxyz":
			$_SESSION[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);
			write_to_log("log", "[POST:A:" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "] - session:KEY AUTHORIZED:: " . $key . " = " . $_SESSION[$key], $log_folder, FALSE);
		break;

        default:
			//$_SESSION[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);
            $not_accepted["POST"][$key] = $value;
			write_to_log("log", "[POST:ZZ:" . $_SERVER['HTTP_X_FORWARDED_FOR'] . "] - session:KEY NOT AUTHORIZED:" . $key . " = " . $value, $log_folder, FALSE);
		break;		
	}
}

unset($_POST);
unset($_GET);

// Session values that will get turned into variables
echo "The Following Fields Were Accepted:" . "<br>\n";
foreach ( $_SESSION as $key => $value )
{
	$$key = $value;
	$v[$key] = $value;
    
    echo "Valid Fields : " . $key . " => " . $value . "<br>\n";
}

echo "<hr>";

echo "The Following Fields Were Not Accepted:" . "<br>\n";
if ( count($not_accepted["GET"]) > 0 )
{
    foreach ( $not_accepted["GET"] as $key => $value )
    {
        echo "GET -> Invalid Fields : " . $key . " => " . $value . "<br>\n";
    }
}

if ( count($not_accepted["POST"]) > 0 )
{
    foreach ( $not_accepted["POST"] as $key => $value )
    {
        echo "POST -> Invalid Fields : " . $key . " => " . $value . "<br>\n";
    }
}

// https://www.owasp.org/index.php/Clickjacking_Defense_Cheat_Sheet
header('X-Frame-Options: SAMEORIGIN');

// --- Functions ---
function write_to_log($log_type = "default", $output = "Time Stamp", $display = FALSE)
{
	global $this_dir;
    
    $keep_log_for_days = 2;

	$log_type = str_replace(" ", "_", $log_type);

	if ( ! is_dir($this_dir."/".$log_type) ) 
	{
		mkdir($this_dir."/".$log_type, 0750, TRUE);
		@chown($this_dir."/".$log_type, "apache");
		@chgrp($this_dir."/".$log_type, "apache");
	}

	$log_filename = date("z") . "-".date("m.d.y").".log";
	$time_stamp = time();

	$outputfile = fopen($this_dir."/".$log_type."/".$log_filename, 'a');
	fwrite($outputfile, $time_stamp." ".date("m.d.y H:i:s",$time_stamp)." [" . getmypid() . "] - ".$output."\n");
	fclose($outputfile);

	if ( file_exists($this_dir."/".$log_type."/".$log_filename) )
	{
		@chown($this_dir."/".$log_type."/".$log_filename, "apache");
		@chgrp($this_dir."/".$log_type."/".$log_filename, "apache");
		@chmod($this_dir."/".$log_type."/".$log_filename, 0770);
	}

	// Let's do a log cleanup process and only keep the last ${keep_log_for_days} days
	$today_number = date("z", time());
    
    //echo "TODAY: " . $today_number . "\n";
    
    $numbers = array();
    
    for ( $i = 0; $i <= $keep_log_for_days ; $i++)
    {
        //echo "CALC: " . $today_number . " - " . $i . " = " . ($today_number - $i) . "<br>\n";
        if ( ($today_number - $i) < 0 )
        {
            // 0 or less
            $c = ($today_number + 365) - $i;
            $numbers[$c] = true;
        }
        else
        {
            $c = $today_number - $i;
            $numbers[$c] = true;
        }
    }
    
    //echo "NUMBERS: " . var_export($numbers, true) . "\n";
    
	$log_files = glob($this_dir."/".$log_type."/*");

	foreach ( $log_files as $file )
	{
		$path_parts = pathinfo($file);

		$values = explode("-", trim($path_parts['basename'],".log"));

        if ( ! array_key_exists($values[0], $numbers) )
        {
            unlink($file);
        }
	}    
}

?>