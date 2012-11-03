<?php
defined( '_ARIA2WEB' ) or die();
/**
* @version    $Id: actions.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package  aria2web
* @copyright  Copyright (C) 2010 soeren. All rights reserved.
* @license    GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/

require_once 'config.php';
require_once 'XML/RPC2/Client.php';
if ($aria2_debug == true)
{
    require_once '/volume1/web/lib/PhpConsole/PhpConsole.php';
    PhpConsole::start(true, true, dirname(__FILE__));
    require_once '/volume1/web/lib/KLogger.php';
    $log = new KLogger('/volume1/web/', KLogger::INFO );
}

function isDebug()
{
    global $aria2_debug;
    return $aria2_debug;
}

function pause($gid)
{
    global $client;
    $result = $client->aria2_pause( $gid);
    //debug_print_r("unPause", $result);
}


function unPause($gid)
{
    global $client;
    $result = $client->aria2_unpause( $gid);
    //debug_print_r("unPause", $result);
}

function addUri($uris, $dir)
{
    global $client;
    $success = true;
    $msg = "";
    try { 
      $result = $client->aria2_addUri( $uris, array("dir" => $dir));
      $msg = 'URI added';
    }
    catch (XML_RPC2_FaultException $e) {  
      $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
       debug($msg);
	  $success = false;
    }

    return $success;
}

function getGlobalOption($opt)
{	
	global $client;
	$result = $client->aria2_getGlobalOption();
	if (is_array($result))
		return $result[$opt];
	return "Error";
}

function debug_print_r($str, $var)
{
    if (isDebug() == false)
        return;
    ob_start();
    echo $str . " : ";
    print_r($var);
    debug(ob_get_contents());
    ob_end_clean();

}

function log_print_r($str, $var)
{
    if (isDebug() == false)
        return;
	global $log;
    ob_start();
    echo $str . " : ";
    print_r($var);
    $log->logInfo(ob_get_contents());
    ob_end_clean();
}

function getStats()
{
    global $client;
    $result = $client->aria2_getGlobalStat();
//    debug_print_r("Stats", $result);
    return $result;
}



function getFiles($gid)
{
    global $client;
    $items = array();
    $result = $client->aria2_getFiles( $gid );
    if( is_array($result))
        {
        foreach ($result as $file)
			{
			$f = basename($file['path']);
//			$f = mb_check_encoding($f, 'UTF-8') ? $f : utf8_encode($f);
			$items[] = $f;
			}
		}
    return $items;
}


function getUris($gid)
{
    global $client;
    $result = $client->aria2_getFiles( $gid );
//	debug_print_r("getUris", $result);
    $items = array();
    if( is_array($result))
        {
        foreach ($result as $files)
            foreach ($files['uris'] as $uris)
					$items[] = $uris['uri'];
        }
    return $items;
}

function getDir($gid)
{
    global $client;
//    $result = $client->aria2_getFiles( $gid );
    $result = $client->aria2_tellStatus( $gid , array('dir'));
    //debug_print_r("getDir", $result);
    return $result['dir'];
}

function getStatus($gid)
{
    global $client;
    $result = $client->aria2_tellStatus( $gid , array('status'));
//    debug_print_r("status", $result);

    return $result['status'];
}

function pauseAll()
{
    global $client;
    try {
        $result = $client->aria2_pauseAll();
		return $result;
    }
    catch( XML_RPC2_FaultException $e ) {
    return $result;
    }
}

function startAll()
{
    global $client;
    try {
        $result = $client->aria2_unpauseAll();
		return $result;
    }
    catch( XML_RPC2_FaultException $e ) {
    return $result;
    }
}


function tellFinished($start, $end)
{
    global $client;
    $totalCount = 0;
    $items = array();
    if ($end == 0)  // we want all 
        {
        $start = 0;
        $end = 20000;  // that's enough no?
        }
    try 
        {
        $result = $client->aria2_tellStopped( $start, $end  );
        if( !empty($result)) 
            {
            foreach( $result as $file ) 
                {
                if ($file['status'] == "complete" && $file['errorCode'] == 0)
                    {
                    $totalCount++;
                    $items[] = $file['gid'];
                    }
                }
            }
    //debug_print_r("tellFinished count", $totalCount++);
    return array( 'totalCount' => $totalCount,
                   'items' => $items );

    } 
    catch( XML_RPC2_FaultException $e ) {
    $return = array( 'totalCount' => $totalCount,
                   'items' => $items );
    }
}


function purge($list2purge)
{
    global $client;
    $msg = '';
    $count = 0;
    if ($list2purge['totalCount'] != 0) 
        {
        $success = true;
        foreach($list2purge['items'] as $gid ) 
            {
            if( !empty($gid)) 
                {
                try 
                    { 
                    $result = $client->aria2_removeDownloadResult( $gid );
                    $count++;
                    }
                catch (XML_RPC2_FaultException $e) 
                    {  
                    $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
                    $success = false;
                    sendResult(false, 'No file to remove');
                    exit;
                    }
                }
            }
        $msg .= $count . ' File removed from queue.';
        sendResult($success, $msg);
        exit;
        }
    sendResult(false, 'No file to remove');
}

function tellError($start, $end)
{
    global $client;
    $totalCount = 0;
    $items = array();
    if ($end == 0)  // we want all 
        {
        $start = 0;
        $end = 20000;  // that's enough no?
        }
    try 
        {
        $result = $client->aria2_tellStopped( $start, $end  );
        if( !empty($result)) 
            {
            foreach( $result as $file ) 
                {
                if ($file['status'] == "error")
                    {
                    $totalCount++;
                    $items[] = $file['gid'];
                    }
                }
            }
    //debug_print_r("tellFinished count", $totalCount++);
    return array( 'totalCount' => $totalCount,
                   'items' => $items );

    } 
    catch( XML_RPC2_FaultException $e ) {
    $return = array( 'totalCount' => $totalCount,
                   'items' => $items );
    }
}

function getPackageName($target_dir)
{
	global $global_download_dir;
//	log_print_r("tdir", $target_dir);
	if ($target_dir == $global_download_dir)
		return "";
	$shortDir = str_replace($global_download_dir, "", $target_dir);
	$package = 	dirname($shortDir);
	if ($package == '/')
		$package = basename($shortDir);
	else
		$package = str_replace("/", "", $package);

	return $package;
}

$aria2_url = 'http://';
if( $aria2_parameters['xml_rpc_user'] != '') {
  $aria2_url .= $aria2_parameters['xml_rpc_user'].':'.$aria2_parameters['xml_rpc_pass'].'@';
}
$aria2_url .= $aria2_xmlrpc_host.':'.$aria2_parameters['xml_rpc_listen_port']. $aria2_xmlrpc_uripath;
$aria2_xml_rpc_options = array(
    'encoding' => 'utf-8',
	'uglyStructHack' => FALSE
);

$client = XML_RPC2_Client::create( $aria2_url, $aria2_xml_rpc_options );


$global_download_dir = getGlobalOption("dir");
//log_print_r("down", $global_download_dir);
$action = @$_REQUEST['action'];



//$num = @min( $_REQUEST['num'], 200 );

$num = @$_REQUEST['limit'];
$offset = @$_REQUEST['start'];
//debug_print_r("Num req ", $num);
//debug_print_r("offset req", $offset);
$num = intval($num);
$offset = intval($offset);
//debug_print_r("Num req ", $num);
//debug_print_r("offset req", $offset);
$stats = getStats();

if( strstr( $action, 'dialog_' )) {
  include( 'dialogs.php');
}

switch( $action ) {
  // Retrieves all files in the download queue
  case 'tellActive':
    $activeArr = $waitingArr = array();
    $items = array();
    try {
      if ($offset == 0)
        $num -= $stats['numActive'];
      else
        {
        $offset -= $stats['numActive'];
        }
//    debug_print_r("Num tellActive", $num);
//    debug_print_r("Offset tellActive", $offset);


      $result = $client->system_multicall( array(
          array('methodName' => 'aria2.tellActive',
                'params' => ''
          ),
          array('methodName' => 'aria2.tellWaiting',
                'params' => array($offset, $num)
          )
          ) );
//	log_print_r("tellActive", $result);

    }
    catch( XML_RPC2_FaultException $e ) {
      $msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")aria2_tellStopped($offset, $num );";
      $success = false;  
      sendResult($success, $msg);
      exit;
    }  
    catch(XML_RPC2_CurlException $e ) {
    }

    if( !empty($result) ) {
      $activeArr = $result[0];
      $waitingArr = $result[1];
    }
    
    if( !empty($activeArr[0] ) && $offset == 0) {
      foreach( $activeArr as $files ) {
        foreach( $files as $file ) {
          $items[]  = $file;          
        }
      }      
    }
    if( !empty($waitingArr[0])) {
      foreach( $waitingArr as $files ) {
        foreach( $files as $file ) {
          $items[]  = $file;          
        }
      }
    }
//	debug_print_r("waiting", count($waitingArr));
//    debug_print_r("active", count($activeArr));

    foreach( $items as $num => $item) {
      if( $item['completedLength'] != 0 ) {
        $items[$num]['completedPercentage'] = ' ('.round( ($item['completedLength'] / $item['totalLength'])*100 , 2) .'%)';
      } else {
        $items[$num]['completedPercentage'] = '';
      }
	
		$items[$num]['package'] = getPackageName($items[$num]['dir']);
/*
        $items[$num]['package']  = mb_check_encoding($items[$num]['package'] , 'UTF-8') ? $items[$num]['package']  : utf8_encode($items[$num]['package'] );
		for ($i = 0; $i < count($item['files']); $i++)
			$items[$num]['files'][$i]['path'] = mb_check_encoding($items[$num]['files'][$i]['path'], 'UTF-8') ? $items[$num]['files'][$i]['path'] : utf8_encode($items[$num]['files'][$i]['path']);
*/
		if ($items[$num]['status'] == 'waiting' || $items[$num]['status'] == 'paused')
			{
			// read the first uri
			$items[$num]['name'] = basename($item['files'][0]['uris'][0]['uri']);
			}
		else
			{
			$items[$num]['name'] = basename($item['files'][0]['path']);
			}
//	  $items[$num]['name']  = mb_check_encoding($items[$num]['name'] , 'UTF-8') ? $items[$num]['name']  : utf8_encode($items[$num]['name'] );
//      debug_print_r("name", $items[$num]['name']);

//	  $items[$num]['dir'] = mb_check_encoding($items[$num]['dir'] , 'UTF-8') ? $items[$num]['dir']  : utf8_encode($items[$num]['dir'] );
      $items[$num]['completedLength'] = parse_file_size($item['completedLength']).$items[$num]['completedPercentage'];
      $items[$num]['totalLength'] = parse_file_size($item['totalLength']);
      $items[$num]['downloadSpeed'] = parse_file_size($item['downloadSpeed']).'/s';
      $items[$num]['uploadSpeed'] = parse_file_size($item['uploadSpeed']).'/s';
      $items[$num]['estimatedTime'] = calc_remaining_time( $item['downloadSpeed'], $item['totalLength']-$item['completedLength'] );
    }
    
    

    $return = array( 'totalCount' => $stats['numActive'] + $stats['numWaiting'],
                   'items' => $items );
    echo json_encode($return);
    die;
    
  case 'getFiles':    
    try {
      $result = $client->aria2_getFiles( strval(intval($_REQUEST['gid'])) );
      $return = array( 'gid' => intval($_REQUEST['gid']),
                    'numFiles' => count( $result ),
                    'totalLength' => parse_file_size($result[0]['length']),
                    'items' => $result 
                ); 
      echo json_encode($return);
      die;
      
    } 
    catch( XML_RPC2_FaultException $e ) {
      $msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")";
      $success = false;  
      sendResult($success, $msg);
    }
    catch(XML_RPC2_CurlException $e ) {
      $msg = 'Exception: ' . $e->getMessage().' (ErrorCode '. $e->getCode() . ')<br />';
      sendResult(false,$msg);
    }
    exit;
      
  case 'tellStopped':
    try 
        {
        $offset = intval($offset);
        $num = intval($num);
        $result = $client->aria2_tellStopped( $offset, $num  );
        $items = array();
        if( !empty($result)) 
            {
            foreach( $result as $file ) 
                {
                    switch( $file['errorCode'] ) 
                        {
                        case 0:$status = '';break;
                        case 1:$status = '-unknown error occured'; break;
                        case 2:$status = '-time out occured'; break;
                        case 3:$status = '-resource not found'; break;
                        case 4:$status = '-resource not found'; break;
                        case 5:$status = '-download aborted, because download speed was too slow'; break;
                        case 6:$status = '-network problem occured'; break;
    					default: $status = '-Unknown error : ' . $file['errorCode'];
                        }

                    if( $file['completedLength'] != 0 ) 
                        {
                        $completedPercentage = ' ('.round( ($file['completedLength'] / $file['totalLength'])*100 , 2) .'%)';
                        $file['name'] = basename($file['files'][0]['path']);
                        }
                    else 
                        {
    		            $file['name'] = basename($file['files'][0]['uris'][0]['uri']);
                        $completedPercentage = '(0%)';
                        }
                    $file['status'] .= $status; 
					$file['package'] = getPackageName($file['dir']);

                    $file['completedLength'] = parse_file_size($file['completedLength']).$completedPercentage;
                    $file['totalLength'] = parse_file_size($file['totalLength']);
                    $items[] = $file;
                }    
            }
        $return = array( 'totalCount' => $stats['numStopped'] ,
                            'items' => $items);
//		debug_print_r("tata", $return);
        echo json_encode($return);
        } 
    
    catch( XML_RPC2_FaultException $e ) 
        {
        $msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")aria2_tellStopped($offset, $num );";
        $success = false;  
        sendResult($success, $msg);
        }
    exit;

  case 'addUri':
    //aria2.addUri uris[, options[, position]]
    //This method adds new HTTP(S)/FTP/BitTorrent Magnet URI. uris is of type array and its element is URI which is of type string. For BitTorrent Magnet URI, uris must have only one element and it should be BitTorrent Magnet URI. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns GID of registered download.
    
    $msg = 'No URI provided';
    $success = true;
    foreach($_POST['userfile'] as $url ) {
      if( !empty($url)) {
        try { 
          $result = $client->aria2_addUri( array($url) );
          $msg = 'URI added';
        }
        catch (XML_RPC2_FaultException $e) {  
          $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
          $success = false;
        }
      }
    }
    sendResult($success, $msg);
    exit;
    
    
  case 'addTorrent':
    //aria2.addTorrent torrent[, uris[, options[, position]]]
    //This method adds BitTorrent download by uploading .torrent file. If you want to add BitTorrent Magnet URI, use aria2.addUri method instead. torrent is of type base64 which contains Base64-encoded .torrent file. uris is of type array and its element is URI which is of type string. uris is used for Web-seeding. For single file torrents, URI can be a complete URI pointing to the resource or if URI ends with /, name in torrent file is added. For multi-file torrents, name and path in torrent are added to form a URI for each file. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns GID of registered download.
    exit;
  case 'addMetalink':
    //aria2.addMetalink metalink[, options[, position]]
    //This method adds Metalink download by uploading .metalink file. metalink is of type base64 which contains Base64-encoded .metalink file. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns array of GID of registered download.
    exit;
  case 'remove':
//    debug_print_r("post", $_POST);
    //aria2.remove gid
    //This method removes the download denoted by gid. gid is of type string. If specified download is in progress, it is stopped at first. The status of removed download becomes "removed". This method returns GID of removed download.
    if( !empty( $_POST['selitems'])) {
      $success = true;
      $msg = '';
      foreach($_POST['selitems'] as $gid ) {
        if( !empty($gid)) {
          try { 
            $status = getStatus($gid);
            switch($status) {
                case 'active':
                case 'waiting':
                case 'paused':
                    $result = $client->aria2_remove( $gid );
                    break;
                case 'error':
				case 'complete':
                case 'removed':
                    $result = $client->aria2_removeDownloadResult($gid);
                    break;
                default:
                    $result = "Unknow file status";
                    $success = false;
                }
            $msg .= $gid . " File removed from queue : " . $result;
          }
          catch (XML_RPC2_FaultException $e) {  
            $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
            $success = false;
          }
        }
      }
      sendResult($success, $msg);
    }
  exit;

  case 'pause':
    // for error status : do nothing
    // for complete status : do nothing
    // for pause status : do nothing
    // for waiting status :  pause
    // for active : pause
    if( !empty( $_POST['selitems'])) {
      $success = true;
      $msg = '';
      foreach($_POST['selitems'] as $gid ) {
        if( !empty($gid)) {
          try {
            // get status
            $status = getStatus($gid);
            switch ($status) {
                case 'active':
                case 'waiting':
                    pause($gid);
                    break;
                default:
                    break;
            }
          }
          catch (XML_RPC2_FaultException $e) {  
            $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
            $success = false;
          }
        }
      }
      sendResult($success, $msg);
    }
    exit;
    exit;

  case 'start':
    // for error status : getinfo add new download, remove old one
    // for complete status : do nothing
    // for pause status : unpause
    // for waiting status :  do nothing
    // for active : do nothing
    if( !empty( $_POST['selitems'])) {
      $success = true;
      $msg = '';
      foreach($_POST['selitems'] as $gid ) {
        if( !empty($gid)) {
          try {
            // get status
            $status = getStatus($gid);
            switch ($status) {
                case 'active':
                case 'waiting':
                case 'complete':
                    break;
                case 'error':
                    // get uris
                    $uris = getUris($gid);
                    // get target dir
                    $target_dir = getDir($gid);
                    // add uri again with initial target dir
                    $success =addUri($uris, $target_dir);
                    // remove old one
                    if ($success == true)
                        $result = $client->aria2_removeDownloadResult($gid);
                    break;
                case 'paused':
                    unPause($gid);
                    //un pause
                    break;
                default:
                    break;
            }
          }
          catch (XML_RPC2_FaultException $e) {  
            $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
            $success = false;
          }
        }
      }
      sendResult($success, $msg);
    }
    exit;


    case 'purgeFinished':
        $list2purge = tellFinished(0, 0);
        purge($list2purge);
        exit;
    case 'purgeErr':
        $list2purge = tellError(0, 0);
        purge($list2purge);
        exit;

    case 'pauseAll':
		$msg = pauseAll();
		if ($msg != "OK")
			$success = false;
		else
			$success = true;
		
		sendResult($success, $msg);
		exit;
	case 'startAll':
		$msg = startAll();
		if ($msg != "OK")
			$success = false;
		else
			$success = true;
		
		sendResult($success, $msg);
		exit;
  case 'getUris':
    //aria2.getUris gid
    //This method returns URIs used in the download denoted by gid. gid is of type string. The response is of type array and its element is of type struct and it contains following keys. The value type is string.
    
  case 'getPeers':
    //aria2.getPeers gid
    //This method returns peer list of the download denoted by gid. gid is of type string. This method is for BitTorrent only. The response is of type array and its element is of type struct and it contains following keys. The value type is string.
    
  case 'changePosition':
    //aria2.changePosition gid, pos, how
    //This method changes the position of the download denoted by gid. pos is of type integer. how is of type string. If how is "POS_SET", it moves the download to a position relative to the beginning of the queue. If how is "POS_CUR", it moves the download to a position relative to the current position. If how is "POS_END", it moves the download to a position relative to the end of the queue. If the destination position is less than 0 or beyond the end of the queue, it moves the download to the beginning or the end of the queue respectively. The response is of type integer and it is the destination position.
    //For example, if GID#1 is placed in position 3, aria2.changePosition(1, -1, POS_CUR) will change its position to 2. Additional aria2.changePosition(1, 0, POS_SET) will change its position to 0(the beginning of the queue).

  case 'changeOption':
    //aria2.changeOption gid, options
    //This method changes options of the download denoted by gid dynamically. gid is of type string. options is of type struct and the available options are: bt-max-peers, bt-request-peer-speed-limit, max-download-limit and max-upload-limit. This method returns "OK" for success.
    try { 
      unset($_POST['action']);
      $gid = strval($_POST['gid']);
      unset($_POST['gid']);
      $result = $client->aria2_changeOption( $gid, $_POST );
      if( $result == 'OK' ) {
        $msg = 'File Options changed';
        $success = true;
      } else {
        $msg = 'Failed to change file options';
        $success = false;
      }
    }
    catch (XML_RPC2_FaultException $e) {  
      $msg = 'Exception: ' .$gid. $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')';
      $success = false;
    }
    sendResult($success, $msg);
    exit;
  case 'changeGlobalOption':
    //aria2.changeGlobalOption options
    //This method changes global options dynamically. options is of type struct and the available options are max-concurrent-downloads, max-overall-download-limit and max-overall-upload-limit. This method returns "OK" for success.
    try { 
      unset($_POST['action']);
      $result = $client->aria2_changeGlobalOption( $_POST );
      if( $result == 'OK' ) {
        $msg = 'Global Options changed';
        $success = true;
      } else {
        $msg = 'Failed to change global options';
        $success = false;
      }
    }
    catch (XML_RPC2_FaultException $e) {  
      $msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')';
      $success = false;
    }
    sendResult($success, $msg);
    exit;
  case 'purgeDownloadResult':
    //aria2.purgeDownloadResult
    //This method purges completed/error/removed downloads to free memory. This method returns "OK".
	break;
  case 'getVersion':
    //aria2.getVersion
    //This method returns version of the program and the list of enabled features. The response is of type struct and contains following keys.
    try { 
      $result = $client->aria2_getVersion();
      if( is_array($result ) ) {
//		log_print_r("version", $result);
        $result = json_encode($result);
        header("Content-type: text/html");
        echo $result;
        exit;
      } else {
        $msg = 'Failed to get Version';
        $success = false;
      }
    }
    catch (XML_RPC2_FaultException $e) {  
      $msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')';
      $success = false;
    }
    catch( XML_RPC2_CurlException $e ) {
      $msg = 'Exception: ' . $e->getMessage() . ')';
      $success = false;
    
    }
	
    sendResult($success, $msg);
    exit;
  case 'download':
    $gid = strval(intval($_REQUEST['gid']));
    try {
      $result = $client->aria2_getFiles( $gid );
    }
    catch (XML_RPC2_FaultException $e) {  
      sendResult(false,  'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')' );
      exit;
    }
    catch( XML_RPC2_CurlException $e ) {
      sendResult(false, 'Exception: ' . $e->getMessage() . ')' );
      exit;
    }
    $file = $result[0]['path']; //TODO
    $filename = basename($file);
    if (!file_exists($file)) {
      sendResult( false, 'File not found');exit;
    }

    if (!is_readable($file)) {
      sendResult(  false, 'File not readable');exit;
    }

    @set_time_limit( 0 );

    $browser = id_browser();

    if ($browser=='IE' || $browser=='OPERA') {
      header('Content-Type: application/octetstream; Charset=UTF-8' );
    } else {
      header('Content-Type: application/octet-stream; Charset=UTF-8');
    }

    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize(realpath($file)));
    //header("Content-Encoding: none");

    if($browser=='IE') {
      // http://support.microsoft.com/kb/436616/ja
      header('Content-Disposition: attachment; filename="'.urlencode($filename).'"');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
    } else {
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      header('Cache-Control: no-cache, must-revalidate');
      header('Pragma: no-cache');
    }

     @readFileChunked($file);
     
    ob_end_flush();
    exit;
}

?>