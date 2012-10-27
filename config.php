<?php
/**
* @version    $Id: config.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package  aria2web
* @copyright  Copyright (C) 2010 soeren. All rights reserved.
* @license    GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/
defined( '_ARIA2WEB' ) or die();
// to run Aria2web in "local" mode means it's installed on the same machine as aria2, so we can start the aria2c executable through PHP
// when Aria2web is run in "web" mode, it's assumed that it's installed on a different machine and won't (be able to) start the aria2c executable
$aria2_mode = 'web'; 
$aria2_xmlrpc_host = 'localhost';
$aria2_xmlrpc_uripath = '/rpc';

$aria2_executable = '/usr/bin/aria2c'; // Location of the aria2c executable
$aria2_parameters = array();

// If aria2web is in local mode, it will try to start aria2c in XMl-RPC mode using the following additional parameters
$aria2_parameters['xml_rpc_listen_port'] = 6800;
$aria2_parameters['xml_rpc_user'] = 'thunder';
$aria2_parameters['xml_rpc_pass'] = 'rejane';
$aria2_parameters['xml_rpc_listen_all'] = 'true';
$aria2_parameters['dir'] = '/volume1/download'; // The directory to store the downloaded file. 
$aria2_parameters['log'] = '/volume1/download/aria2.log'; // The location of the log file.
$aria2_parameters['http_user']= ''; //Set HTTP user. This affects all URLs. 
$aria2_parameters['http_passwd']= '';//Set HTTP password. This affects all URLs. 
$aria2_parameters['ftp_user']= 'admin'; //Set HTTP user. This affects all URLs. 
$aria2_parameters['ftp_passwd']= '5j02h2JQob';//Set HTTP password. This affects all URLs. 
$aria2_parameters['load_cookies']= ''; //Load Cookies from FILE using the Firefox3 format (SQLite3) and the Mozilla/Firefox(1.x/2.x)/Netscape format. 
$aria2_parameters['user_agent']= ''; //Set user agent for HTTP(S) downloads. Default: aria2/$VERSION, $VERSION is replaced by package version. 

?>