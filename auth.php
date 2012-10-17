<?php
/**
* @version		$Id: auth.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/

$username = $aria2_parameters['xml_rpc_user'];
$password = $aria2_parameters['xml_rpc_pass'];

if(isset($_GET['logout'])) {
    unset( $_SESSION['login'] );
    session_destroy();
	session_regenerate_id();
    session_write_close();
	echo "You have logged out ... ";
	echo "[<a href='" . $_SERVER['PHP_SELF'] . "'>Login</a>]";
	exit;
}
if( !empty( $username )){
	if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || !isset($_SESSION["login"])) {
	  header('WWW-Authenticate: Basic realm="Aria2Web Login (#'.uniqid().')"');
	  header("HTTP/1.0 401 Unauthorized");
	  $_SESSION["login"] = true;
	  echo "You are not authorized ... ";
	  echo "[<a href='" . $_SERVER['SCRIPT_NAME'] . "'>Login</a>]";
	  exit;
	}
	else {
	  if($_SERVER['PHP_AUTH_USER'] == $username && $_SERVER['PHP_AUTH_PW'] == $password
		&& empty( $_SESSION['username']) )  {
		// all good
		$_SESSION['username'] = $_SERVER['PHP_AUTH_USER'];
		$_SESSION['password'] = md5($_SERVER['PHP_AUTH_PW']);
		
	  }
	  elseif( $_SESSION['username'] == $username && $_SESSION['password'] == md5( $password ) ){
		// yep
	  }
	  else  {
		unset( $_SESSION['login'] );
		session_destroy();
		session_write_close();
		header("Location: " . $_SERVER['PHP_SELF']);
		exit;
	  }
	}
}