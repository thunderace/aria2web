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

require_once 'XML/RPC2/Client.php';



function getFiles($gid)
{
    global $client;
    $result = $client->aria2_getFiles( $gid );
    return basename($result[0]['path']);
/*
return = array( 'numFiles' => count( $result ),
                    'items' => $result ); 
*/
}

function tellStatus($gid)
{
    global $client;
    $status = 'failed';
    try {
      $result = $client->aria2_tellStatus( $gid );
      if( !empty($result)) {
        $status = $result['status'];
        }
    }
    catch( XML_RPC2_FaultException $e ) {
    return $status;
    }
}

function tellFinished()
{
    global $client;
    global $num;
    global $offset;
    $items = array();
    try {
//      $result = $client->aria2_tellStopped( $offset, $num  );
      $result = $client->aria2_tellStopped( $offset, 2  );  //$$AL$$ for test
      if( !empty($result)) {
        foreach( $result as $file ) {
            if ($file['errorCode'] == 0)
                $items[] = $file['gid'];
          }
        
      }
      return $items;
    } 
    catch( XML_RPC2_FaultException $e ) {
    return $items;
    }
}


$aria2_url = 'http://';
if( $aria2_parameters['xml_rpc_user'] != '') {
  $aria2_url .= $aria2_parameters['xml_rpc_user'].':'.$aria2_parameters['xml_rpc_pass'].'@';
}
$aria2_url .= $aria2_xmlrpc_host.':'.$aria2_parameters['xml_rpc_listen_port']. $aria2_xmlrpc_uripath;

$client = XML_RPC2_Client::create( $aria2_url );

$action = @$_REQUEST['action'];

  /*
$num = @min( $_REQUEST['num'], 200 );
$offset = @min( $_REQUEST['offset'], 201 );
  */
$num = 2000;
$offset = 0;
  
if( strstr( $action, 'dialog_' )) {
  include( 'dialogs.php');
}

switch( $action ) {
  // Retrieves all files in the download queue
  case 'tellActive':
    $totalCount = 0;
    $activeArr = $waitingArr = array();
    $items = array();
    try {
      $result = $client->system_multicall( array(
          array('methodName' => 'aria2.tellActive',
                'params' => ''
          ),
          array('methodName' => 'aria2.tellWaiting',
                'params' => array($offset, $num )
          )                
          ) );
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
    
    if( !empty($activeArr[0])) {
      foreach( $activeArr as $files ) {
        foreach( $files as $file ) {
          $totalCount ++;
          $items[]  = $file;          
        }
      }      
    }
    if( !empty($waitingArr[0])) {
      foreach( $waitingArr as $files ) {
        foreach( $files as $file ) {
          $totalCount ++;
          $items[]  = $file;          
        }
      }
    }
    foreach( $items as $num => $item) {
      if( $item['completedLength'] != 0 ) {
        $items[$num]['completedPercentage'] = ' ('.round( ($item['completedLength'] / $item['totalLength'])*100 , 2) .'%)';
      } else {
        $items[$num]['completedPercentage'] = '';
      }
      $items[$num]['name'] = getFiles($item['gid']);
      $items[$num]['completedLength'] = parse_file_size($item['completedLength']).$items[$num]['completedPercentage'];
      $items[$num]['totalLength'] = parse_file_size($item['totalLength']);
      $items[$num]['downloadSpeed'] = parse_file_size($item['downloadSpeed']).'/s';
      $items[$num]['uploadSpeed'] = parse_file_size($item['uploadSpeed']).'/s';
      $items[$num]['estimatedTime'] = calc_remaining_time( $item['downloadSpeed'], $item['totalLength']-$item['completedLength'] );
    }
    
    $return = array( 'totalCount' => $totalCount,
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
    $totalCount = 0;
    try {
      $offset = intval($offset);
      $num = intval($num);
      $result = $client->aria2_tellStopped( $offset, $num  );
      if( !empty($result)) {
        foreach( $result as $file ) {
            switch( $file['errorCode'] ) {
              case 0: $status = 'download successful'; break;
              case 1:$status = 'unknown error occured'; break;
              case 2:$status = 'time out occured'; break;
              case 3:$status = 'resource not found'; break;
              case 4:$status = 'resource not found'; break;
              case 5:$status = 'download aborted, because download speed was too slow'; break;
              case 6:$status = 'network problem occured'; break;
            }
            if( $file['completedLength'] != 0 ) {
              $completedPercentage = ' ('.round( ($file['completedLength'] / $file['totalLength'])*100 , 2) .'%)';
            } else {
              $completedPercentage = '(0%)';
            }
            $file['status'] .= ', '. $status;    
            $file['name'] = getFiles($file['gid']);
            $file['completedLength'] = parse_file_size($file['completedLength']).$completedPercentage;
            $file['totalLength'] = parse_file_size($file['totalLength']);

            $totalCount ++;
            $items[] = $file;
          }
        
      }
      $return = array( 'totalCount' => $totalCount,
               'items' => $items );
      echo json_encode($return);
    } 
    catch( XML_RPC2_FaultException $e ) {
      $msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")aria2_tellStopped($offset, $num );";
      $success = false;  
      sendResult($success, $msg);
    }
    exit;
  case 'tellStatus':
    //aria2.tellStatus gid
    //This method returns download progress of the download denoted by gid. gid is of type string. The response is of type struct and it contains following keys. The value type is string.
    break;
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
  
  case 'addMetalink':
    //aria2.addMetalink metalink[, options[, position]]
    //This method adds Metalink download by uploading .metalink file. metalink is of type base64 which contains Base64-encoded .metalink file. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns array of GID of registered download.

  case 'remove':
    //aria2.remove gid
    //This method removes the download denoted by gid. gid is of type string. If specified download is in progress, it is stopped at first. The status of removed download becomes "removed". This method returns GID of removed download.
    if( !empty( $_POST['selitems'])) {
      $success = true;
      $msg = '';
      foreach($_POST['selitems'] as $gid ) {
        if( !empty($gid)) {
          try { 
            // first get current status of the gid
            // active : call remove
            // paused : call forceRemove
            // other (error or finished)  : removeDownloadResult
            $status = tellStatus($gid);
            switch($status) {
                case 'failed':
                    $msg .= $gid . " Can't get current status";
                    break;
                case 'active':
                    $result = $client->aria2_remove( $gid );
                    break;
                case 'paused':
                    $result = $client->aria2_forceRemove($gid) ;
                    break;
                case 'error':
                    $result = $client->aria2_removeDownloadResult($gid);
                    break;
                }
            $msg .= $gid . " File removed from queue";
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

  case 'purge':
    // list terminated without error and remove them from aria2
    $msg = '';
    $list2purge = tellFinished();
    if( !empty( $list2purge)) {
      $success = true;
      foreach($list2purge as $gid ) {
        if( !empty($gid)) {
          try { 
            $msg .= $gid .  " ";
            $result = $client->aria2_removeDownloadResult( $gid );
//            $msg .= $gid .  " ";
          }
          catch (XML_RPC2_FaultException $e) {  
            $msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
            $success = false;
          }
        }
      }
      $msg .= 'File removed from queue';
      sendResult($success, $msg);
      exit;
    }
    sendResult(false, 'No file to remove');
    exit;    

  case 'pause':
    // for each ids, if status = 'active' : set to 'paused'
    if( !empty( $_POST['selitems'])) {
      $success = true;
      $msg = '';
      foreach($_POST['selitems'] as $gid ) {
        if( !empty($gid)) {
          try { 
            $status = tellStatus($gid);
            switch ($status) {
                case 'failed':
                    $msg .= $gid . " Can't get current status";
                    break;
                case 'active':
                    $result = $client->aria2_pause( $gid );
                    $msg .= $gid . " File paused";
                    break;
                case 'paused':
                    $result = $client->aria2_unpause( $gid );
                    $msg .= $gid . " File unpaused";
                    break;
                case 'error':
                    //$$AL$$ TODO : restart downlaod in error
                    // get uri
                    $uri = $client->aria2_getUris($gid);
                    // remove gid
                    $result = $client->aria2_removeDownloadResult($gid);
                    // add again uri
                    $result = $client->aria2_addUri($uri);
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
  case 'getVersion':
    //aria2.getVersion
    //This method returns version of the program and the list of enabled features. The response is of type struct and contains following keys.
    try { 
      $result = $client->aria2_getVersion();
      if( is_array($result ) ) {
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