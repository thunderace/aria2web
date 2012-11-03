<?php
/**
* @version		$Id: dialogs.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/
defined( '_ARIA2WEB' ) or die();

switch( $action ) {
	case 'dialog_add':
		?>
		{	
		"xtype": "form",
		"id": "transferform",
		"url":"index.php",
		"title": "Add File(s)",
		"autoHeight": "true",
		"frame": "true",
		"items": [
		<?php
			for($i=0;$i<7;$i++) {
				echo '{ xtype: "compositefield", items: [
					{
						"xtype": "textfield",
						"fieldLabel": "URL",
						"name": "userfile['.$i.']",
						"width":275
					},
					{
					"xtype": "textfield",
					"fieldLabel": "Package",
					"name": "package['.$i.']",
					"width":170
					}
				]}';
				if( $i <6 ) echo ",\n";
			}
		?>
			
		],
		"buttons": [{
	
			"text": "Add File(s)", 
			"handler": function() {
				statusBarMessage( 'Adding Files to Queue', true );
				transfer = Ext.getCmp("transferform").getForm();
				transfer.submit({
					reset: false,
					success: function(form, action) {
						datastore.reload();
						statusBarMessage( action.result.message, false, true );
						Ext.getCmp("dialog").destroy();
					},
					failure: function(form, action) {
						if( !action.result ) return;
						Ext.MessageBox.alert('Error', action.result.error);
						statusBarMessage( action.result.error, false, false );
					},
					scope: transfer,
					// add some vars to the request, similar to hidden fields
					params: { 
						"action": "addUri", 
						"confirm": 'true'
					}
				});
			}
		},{
			"text": "Cancel", 
			"handler": function() { Ext.getCmp("dialog").destroy(); }
		}]
	}
	<?php 
	exit;

	case 'dialog_getOption':
		//aria2.getOption gid
		//This method returns options of the download denoted by gid. The response is of type struct. Its key is the name of option. The value type is string.
		$gid = $_POST['selitems'][0];
		try { 
			$result = $client->aria2_getOption($gid);
			
				?>
				{	
				"xtype": "form",
				"id": "fileoptionsform",
				"url":"index.php",
				"title": "Change File Options",
				"autoScroll": "true",
				"height": "400",
				"frame": "true",
				"items": [{
					"xtype": "hidden",
					"name": "gid",
					"value": "<?php echo $gid ?>"
					},
				<?php
				
				$i = 0;
				$available_options = array('bt-max-peers', 'bt-request-peer-speed-limit', 'max-download-limit', 'max-upload-limit');
				$numOptions = count( $available_options );
				foreach($result as $key => $value) {
					if( !in_array($key, $available_options)) continue;
					if( $value == 'false' || $value == 'true' ) {
						echo '{
						"xtype": "combo",
						"store": [
								["true", "Yes" ],
								["false", "No" ]
								], 
						"fieldLabel": "'.$key.'",
						"name": "'.$key.'",
						"hiddenName": "'.$key.'",
						"value": "'.$value.'",
						"triggerAction": "all",
						"editable": "false",
						"forceSelection": "true",
						"mode": "local"
					}';
					} 
					else {
						echo '{
						"xtype": "textfield",
						"fieldLabel": "'.$key.'",
						"name": "'.$key.'",
						"value": "'.$value.'"
						}';
					}
    				if( ++$i < $numOptions ) echo ",\n";
				}
					?>
				],
				"buttons": [{			
					"text": "Save Options", 
					"handler": function() {
						statusBarMessage( 'Saving Options...', true );
						form = Ext.getCmp("fileoptionsform").getForm();
						form.submit({
							reset: false,
							success: function(form, action) {
								datastore.reload();
								statusBarMessage( action.result.message, false, true );
								Ext.getCmp("dialog").destroy();
							},
							failure: function(form, action) {
								if( !action.result ) return;
								Ext.MessageBox.alert('Error', action.result.error);
								statusBarMessage( action.result.error, false, false );
							},
							scope: form,
							// add some vars to the request, similar to hidden fields
							params: { 
								"action": "changeOption"
							}
						});
					}
				},{
					"text": "Cancel", 
					"handler": function() { try { Ext.getCmp("dialog").destroy(); } catch(e) { alert( e.message ); } }
				}]
			}
			<?php 
			exit;
		}
		catch( XML_RPC2_FaultException $e) {
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
			sendResult(false,$msg);
			exit;
		}
		catch(XML_RPC2_CurlException $e ) {
			$msg = 'Exception: ' . $e->getMessage().' (ErrorCode '. $e->getCode() . ')<br />';
			sendResult(false,$msg);
			exit;
		}
	exit;
	
    case 'dialog_uiOptions':
        exit;
	
	case 'dialog_globalOptions':
		//aria2.getGlobalOption
		//This method returns global options. The response is of type struct. Its key is the name of option. The value type is string. Because global options are used as a template for the options of newly added download, the response contains keys returned by aria2.getOption method.
		try { 
			$result = $client->aria2_getGlobalOption();
			if( empty( $result )) { 
				$msg = 'Exception: received an empty response from Aria2';
				sendResult(false,$msg);
				exit;
			}
				?>
				{	
				"xtype": "form",
				"id": "optionsform",
				"url":"index.php",
				"title": "Change global options",
				"autoScroll": "true",
				"height": "400",
				"frame": "true",
				"items": [
				<?php
				$numOptions = count( $result );
				$i = 0;
				$available_options = array('max-concurrent-downloads', 'max-overall-download-limit', 'max-overall-upload-limit');
				foreach($result as $key => $value) {
					if( !in_array($key, $available_options)) continue;
					if( $value == 'false' || $value == 'true' ) {
						echo '{
						"xtype": "combo",
						"store": [
								["true", "Yes" ],
								["false", "No" ]
								], 
						"fieldLabel": "'.$key.'",
						"name": "'.$key.'",
						"hiddenName": "'.$key.'",
						"value": "'.$value.'",
						"triggerAction": "all",
						"editable": "false",
						"forceSelection": "true",
						"mode": "local"
					}';
					} 
					else {
						echo '{
						"xtype": "textfield",
						"fieldLabel": "'.$key.'",
						"name": "'.$key.'",
						"value": "'.$value.'"
						}';
					}
					if( $i++ < $numOptions ) echo ",\n";
				}
				?>
				],
				"buttons": [{			
					"text": "Save Options", 
					"handler": function() {
						statusBarMessage( 'Saving Options...', true );
						form = Ext.getCmp("optionsform").getForm();
						form.submit({
							reset: false,
							success: function(form, action) {
								datastore.reload();
								statusBarMessage( action.result.message, false, true );
								Ext.getCmp("dialog").destroy();
							},
							failure: function(form, action) {
								if( !action.result ) return;
								Ext.MessageBox.alert('Error', action.result.error);
								statusBarMessage( action.result.error, false, false );
							},
							scope: form,
							// add some vars to the request, similar to hidden fields
							params: { 
								"action": "changeGlobalOption"
							}
						});
					}
				},{
					"text": "Cancel", 
					"handler": function() { try { Ext.getCmp("dialog").destroy(); } catch(e) { alert( e.message ); } }
				}]
			}
			<?php 
			exit;
		}
		catch( XML_RPC2_FaultException $e) {
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
			sendResult(false,$msg);
			exit;
		}
		catch(XML_RPC2_CurlException $e ) {
			$msg = 'Exception: ' . $e->getMessage().' (ErrorCode '. $e->getCode() . ')<br />';
			sendResult(false,$msg);
			exit;
		}
}
?>