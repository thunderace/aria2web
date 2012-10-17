<?php 
/**
* @version		$Id: index.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/
define( '_ARIA2WEB', 1 );
define( '_ARIA2WEB_VERSION', '0.1' );
define( '_ARIA2WEB_HOMEPAGE', 'https://sourceforge.net/projects/aria2web/' );

session_name('aria2web');
session_start();

require_once('config.php');
//require_once('auth.php');


require_once( 'functions.php');
require_once( 'actions.php');
?>
<html>d
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Aria2c Webfrontend</title>
<link rel="shortcut icon" href="images/logo.ico">

<link rel="stylesheet" type="text/css" href="scripts/extjs/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="styles/style.css" />
<link rel="stylesheet" type="text/css" href="scripts/extjs/ux.statusbar/css/statusbar.css" />
 	<script type="text/javascript" src="scripts/extjs/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="scripts/extjs/ext-all.js"></script>    
    <script type="text/javascript" src="scripts/extjs/ux.statusbar/StatusBar.js"></script>
<script type="text/javascript" src="scripts/application.js"></script>
<script type="text/javascript" src="scripts/functions.js"></script>
<script type="text/javascript">var aria2web_mode="<?php echo $aria2_mode ?>";</script>
</head>
<body>
<div id="header"><h1 style="float: left;">Aria2c Webfrontend</h1>
<a href="<?php echo _ARIA2WEB_HOMEPAGE ?>" title="Visit the Aria2Web Homepage" target="_blank"><img align="right" src="images/logo.png" alt="aria2web logo2" border="0" /></a>
 <br /><br style="clear: left;" /><p>Controlling <a href="http://aria2.sourceforge.net/" target="_blank">Aria2</a> over the network.</p>
 </div>
<?php 
$bottomtext = 'No connection to an Aria2 instance established.';

if( $aria2_mode == 'local' ) {
	if( @$_REQUEST['action'] != 'stopAria2' ) {
		if( findAria2() === false ) {
			$exec_success = startAria2();
			if( $exec_success === false ) {
				$bottomtext = 'Failed to start Aria2c, check if the executable and all required libraries exist';
			}
		}
	} else {
		stopAria2();
		$bottomtext = 'Aria2c has been closed.';
	}
}

try {
    $result = $client->aria2_getVersion(); 
    if($result['version']) {
    	$bottomtext = 'Connected to aria2 <span style="font-weight: bold;">version '  . $result['version'].'</span>. Enabled features: '.implode(', ', $result['enabledFeatures'] );
    }
   
} catch (XML_RPC2_FaultException $e) {
    // The XMLRPC server returns a XMLRPC error
   echo ('<script type="text/javascript">
Ext.Msg.show({
   title:"Error",
   msg: "Exception #' . $e->getFaultCode() . ' : ' . $e->getFaultString().'",
   buttons: {ok: "Reload" },
   fn: function(id,text,opt) { location.reload() },
   icon: Ext.MessageBox.ERROR
});</script>');

} catch (Exception $e) {  
    // Other errors (HTTP or networking problems...)
 	echo ('<script type="text/javascript">
Ext.Msg.show({
   title:"Error",
   msg: "Connection Error: ' . $e->getMessage().'<br/>Please review your config.php and check if Aria2c is properly installed/running.",
   buttons: {ok: "Try again" },
   fn: function(id,text,opt) { location.reload() },  
   icon: Ext.MessageBox.ERROR
});</script>');
    
}

?>
<!-- a place holder for the grid. requires the unique id to be passed in the javascript function, and width and height ! -->
<div id="downloads-grid"></div>
<div id="bottom"><?php echo $bottomtext ?></div>
</body>
</html>
