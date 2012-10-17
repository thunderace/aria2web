<?php
/**
* @version		$Id: functions.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/
defined( '_ARIA2WEB' ) or die();


function startAria2() {
	global $aria2_parameters, $aria2_executable;
	
	if( !is_file( $aria2_executable ) || !is_executable( $aria2_executable ) ) {
		return false;
	}
	$parameterstring = '';
	foreach( $aria2_parameters as $key => $value ) {
		if( empty( $value )) continue;
		$parameterstring .= ' --'.str_replace('_', '-', $key).'='.escapeshellarg($value);
	}
	
	$pid = PsExec($aria2_executable.' --enable-xml-rpc=true'.$parameterstring);

	if( $pid === false ) {
		return false;
	}
	$_SESSION['aria2c_pid'] = $pid;
	return true;
}

function findAria2() {
	global $aria2_parameters, $aria2_executable;
	$addparameter = !file_exists( '/bin/busybox' ) ? ' ax' : '';
	exec("ps$addparameter | grep \"$aria2_executable --enable-xml-rpc=true\" 2>&1", $output);

	while( list(,$row) = each($output) ) {
		$row_array = explode(" ", trim($row));
		foreach( $row_array as $idx => $val ) {
			if($val == $aria2_executable && $row_array[$idx+1] == '--enable-xml-rpc=true') {
				$_SESSION['aria2c_pid'] = $row_array[0];
				return $_SESSION['aria2c_pid'];
			}
		}
	}

	return false;
}

function stopAria2() {
	if( !empty( $_SESSION['aria2c_pid'] )) {
		PsKill( $_SESSION['aria2c_pid'] );
		return true;
	}
	$pid = findAria2();
	if( $pid !== false  ) {
		PsKill( $pid );
		return true;
	}
	return false;
}

/**
 * Reads a file and sends them in chunks to the browser
 * This should overcome memory problems
 * http://www.php.net/manual/en/function.readfile.php#54295
 *
 * @since 1.4.1
 * @param string $filename
 * @param boolean $retbytes
 * @return mixed
 */
function readFileChunked($filename,$retbytes=true) {
	$chunksize = 1*(1024*1024); // how many bytes per chunk
	$buffer = '';
	$cnt =0;
	// $handle = fopen($filename, 'rb');
	$handle = fopen($filename, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, $chunksize);
		echo $buffer;
		sleep(1);
		ob_flush();
		flush();
		if ($retbytes) {
			$cnt += strlen($buffer);
		}
	}
	$status = fclose($handle);
	if ($retbytes && $status) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}

function id_browser() {
	$browser=$_SERVER['HTTP_USER_AGENT'];

	if(ereg('Opera(/| )([0-9].[0-9]{1,2})', $browser)) {
		return 'OPERA';
	} else if(ereg('MSIE ([0-9].[0-9]{1,2})', $browser)) {
		return 'IE';
	} else if(ereg('OmniWeb/([0-9].[0-9]{1,2})', $browser)) {
		return 'OMNIWEB';
	} else if(ereg('(Konqueror/)(.*)', $browser)) {
		return 'KONQUEROR';
	} else if(ereg('Mozilla/([0-9].[0-9]{1,2})', $browser)) {
		return 'MOZILLA';
	} else {
		return 'OTHER';
	}
}

function PsExecute($command, $timeout = 60, $sleep = 2) {
	// First, execute the process, get the process ID

	$pid = PsExec($command);

	if( $pid === false )
		return false;

	$cur = 0;
	// Second, loop for $timeout seconds checking if process is running
	while( $cur < $timeout ) {
		sleep($sleep);
		$cur += $sleep;
		// If process is no longer running, return true;

	   echo "\n ---- $cur ------ \n";

		if( !PsExists($pid) )
			return true; // Process must have exited, success!
	}

	// If process is still running after timeout, kill the process and return false
	PsKill($pid);
	return false;
}

function PsExec($commandJob) {

	$command = $commandJob.' > /dev/null 2>&1 & echo $!';
	exec($command ,$op);
	$pid = (int)$op[0];

	if($pid!="") return $pid;

	return false;
}

function PsExists($pid) {
	$addparameter = !file_exists( '/bin/busybox' ) ? ' ax' : '';
	
	exec("ps$addparameter | grep $pid 2>&1", $output);

	while( list(,$row) = each($output) ) {

			$row_array = explode(" ", trim($row));
			$check_pid = $row_array[0];

			if($pid == $check_pid) {
					return true;
			}

	}

	return false;
}

function PsKill($pid) {
	exec("kill -9 $pid", $output);
}
/**
 * Sends an action result in JSON format
 *
 * @param boolean $success
 * @param string $msg
 */
function sendResult($success, $msg) {
	$result = array('message' => str_replace("'", "\\'", $msg ),
							'error' => str_replace("'", "\\'", $msg ),//.print_r($_POST,true),
							'success' => $success 
						);
	$result = json_encode($result);
	header("Content-type: text/html");
	echo $result;
}
/**
 * Parses a given filesize in bytes and returns a properly formatted size with unit
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function parse_file_size($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    if( !is_float($bytes)) {
    	$bytes = (int)sprintf("%u", $bytes);
    }
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
  
    $bytes /= pow(1024, $pow);
  
    return round($bytes, $precision) . ' ' . $units[$pow];
} 

function calc_remaining_time( $speed, $totalsize ) {
	if( $speed > 0 ) {
		$remaining_time = (int)$totalsize / $speed;
		if( $remaining_time > 60 ) {
			return duration($remaining_time);
		}
	} else {
		$remaining_time = false;
	}
	
}
/**
 * Calculates a formatted duration time
 *
 * @param unknown_type $seconds
 * @return unknown
 */
function duration($seconds) {
	 
	 $days = floor($seconds/60/60/24);
	 $hours = $seconds/60/60%24;
	 $mins = $seconds/60%60;
	 $secs = $seconds%60;
	 
	 $duration='';
	 if($days>0) $duration .= $days.'d, ';
	 if($hours>0) $duration .= $hours.' hrs, ';
	 if($mins>0) $duration .= $mins.' min, ';
	 if($secs>0) $duration .= $secs. ' sec.';
	 
	 $duration = trim($duration);
	 if($duration==null) $duration = '0 seconds';
	 
	 return $duration;
}