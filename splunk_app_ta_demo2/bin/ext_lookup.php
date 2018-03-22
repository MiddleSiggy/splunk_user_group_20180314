#!/usr/bin/php
<?php
// https://answers.splunk.com/answers/145561/how-to-script-a-lookup-in-python.html
// http://docs.splunk.com/Documentation/Splunk/7.0.2/Knowledge/DefineanexternallookupinSplunkWeb
// http://docs.splunk.com/Documentation/Splunk/7.0.2/Knowledge/Configureexternallookups

error_reporting(2047); # =- This will make all errors and other info report

## =- User Variables =- ##
##########################

$version = "2018-03-07.1";

$debug = true;  // If we want debugging turned on

// Define any of the returned fields
$new_fields = array();
$new_fields[] = "field1";
$new_fields[] = "field2";
$new_fields[] = "field3";

##########################
## =- User Variables =- ##

$v = array();
set_time_limit(0);
$process_pid = posix_getpid();

if ( $debug ) $fh = fopen("debug.log", "w+");

if ( $debug ) fwrite($fh, "Start - " . time() . "\n");
if ( $debug ) fwrite($fh, "ARGS: " . $argc . "\n");
if ( $debug ) fwrite($fh, "ARGV: " . var_export($argv, true) . "\n");

// -- Begin - Generate the Return Header
// -- This will create a header array, that is made up of
// -- the passed in variables, plus the defined new_fields
$passed_headers = $argc;
for ( $i = 1; $i < $argc; $i++ )
{
    $v["headers"][$argv[$i]] = $argv[$i];
}

if ( count($new_fields) > 0 )
{
    for ( $i = 0; $i < count($new_fields); $i++ )
    {
        $v["headers"][$new_fields[$i]] = $new_fields[$i];
    }
}

if ( $debug ) fwrite($fh, "V: " . var_export($v, true) . "\n");
// -- End - Generate the Return Header

// -- Begin - Create the header string, and output it
// We need to first echo out the return lookup table headers:
// We will do this by building the headers based on what is passed to us
if ( count($v["headers"]) > 0 )
{
    $header_string = "";
    foreach ( $v["headers"] as $kh => $vh )
    {
        $header_string .= $kh . ",";
    }
    $header_string .= "\n";
    
    if ( $debug ) fwrite($fh, "HEADER: " . $header_string);

    echo $header_string;
}
// -- End - Create the header string, and output it

// http://docs.splunk.com/Documentation/Splunk/7.0.2/Knowledge/Configureexternallookups


// -- Begin - Now Splunk will stream all events returned as a row
// -- We will want to read stdin and parse the data being sent by splunk
$row = 1;
if (($handle = fopen('php://stdin', 'r')) !== FALSE) 
{
	while (($data = fgetcsv($handle, 4096, ",")) !== FALSE) 
	{

		if ( $debug ) fwrite($fh, "DD: (" . $row . ") " . var_export($data, true) . "\n");
		
		if ( $row >= 1 ) // Skip the header on line #1
		{
            if ( count($data) > 0 )
            {
                // $data is an array
                // $kd - will be the field number of the values being passed to the script from splunk
                // $vd - will be the value in the field
                // 1 => value = will be the first field passed
                // 2 => value = vill be the second field passed
                
                $hostname = gethostbyaddr ( $data[0] );
                $time = time();
                
                // We now want to echo out the result
                // If 2 values are passed to the script, we want to return them back first, 
                // in the order received
                $out_string = $data[0] . "," . $data[1] . "," . $hostname . "," . $time . "," . time() . "," . "\n";

                // Echo out the result
                echo $out_string;
            }
		}
		$row++;
	}
	fclose($handle);
}
// -- End - Now Splunk will stream all events returned as a row

if ( $debug ) fwrite($fh, "End:" . time() . "\n");
if ( $debug ) fclose($fh);
?>