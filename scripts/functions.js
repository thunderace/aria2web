/*
* @version		$Id: functions.js 9 2010-01-21 16:07:42Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/
function openActionDialog( caller, action ) {
	var dialog;
	var selectedRows = Ext.getCmp("fileGrid").getSelectionModel().getSelections();
	var dontNeedSelection = { add:1, get_about:1, globalOptions: 1, purge: 1};
	if( dontNeedSelection[action] == null  && selectedRows.length < 1 ) {
		Ext.Msg.alert( 'No file selected!');
		return false;
	}

	switch( action ) {
		case 'add':
		case 'options':
		case 'globalOptions':
		case 'getOption':
		case 'get_about':
			requestParams = getRequestParams();
			requestParams.action = "dialog_" + action;
			
        	dialog = new Ext.Window( {
        		id: "dialog",
                autoCreate: true,
                autoScroll: true,
                modal:true,
                width:600,
                height:400,
                shadow:true,
                minWidth:300,
                minHeight:200,
                proxyDrag: true,
                resizable: true,
                renderTo: Ext.getBody(),
                keys: {
				    key: 27,
				    fn  : function(){
                        dialog.hide();
                    }
				},
				title: "Action Dialog"
        	});			
			
			Ext.Ajax.request( { url: 'index.php',
								params: Ext.urlEncode( requestParams ),
								scripts: true,
								callback: function(oElement, bSuccess, oResponse) {
											if( !bSuccess ) {
												msgbox = Ext.Msg.alert( "Ajax communication failure!");
												msgbox.setIcon( Ext.MessageBox.ERROR );
											}
											if( oResponse && oResponse.responseText ) {
												
												//Ext.Msg.alert("Debug", oResponse.responseText );
												try{ json = Ext.decode( oResponse.responseText );
													if( json.error && typeof json.error != 'xml' ) {
														Ext.Msg.alert( "Error", json.error );
														dialog.destroy();
														return false;
													}
												} catch(e) {
													msgbox = Ext.Msg.alert( "Error", "JSON Decode Error: " + e.message );
													msgbox.setIcon( Ext.MessageBox.ERROR );
													return false; 
												}
												// we expect the returned JSON to be an object that
												// contains an "Ext.Component" or derivative in xtype notation
												// so we can simply add it to the Window
												
												dialog.add(json);
												dialog.doLayout();
												if( json.dialogtitle ) {
													// if the component delivers a title for our
													// dialog we can set the title of the window
													dialog.setTitle(json.dialogtitle);
												}
/*
												try {
													// recalculate layout
													dialog.doLayout();
													// recalculate dimensions, based on those of the newly added child component
													firstComponent = dialog.getComponent(0);
													newWidth = firstComponent.getWidth() + dialog.getFrameWidth();
													newHeight = firstComponent.getHeight() + dialog.getFrameHeight();
													dialog.setSize( newWidth, newHeight );
													
												} catch(e) {}
												//alert( "Before: Dialog.width: " + dialog.getWidth() + ", Client Width: "+ Ext.getBody().getWidth());
												if( dialog.getWidth() >= Ext.getBody().getWidth() ) {
													dialog.setWidth( Ext.getBody().getWidth() * 0.8 );
												}
												//alert( "After: Dialog.width: " + dialog.getWidth() + ", Client Width: "+ Ext.getBody().getWidth());
												if( dialog.getHeight() >= Ext.getBody().getHeight() ) {
													dialog.setHeight( Ext.getBody().getHeight() * 0.7 );
												} else if( dialog.getHeight() < Ext.getBody().getHeight() * 0.3 ) {
													dialog.setHeight( Ext.getBody().getHeight() * 0.5 );
												}
*/
												// recalculate Window size
												dialog.syncSize();
												// center the window
												dialog.center();
												
											} else if( !response || !oResponse.responseText) {
												msgbox = Ext.Msg.alert( "Error", "Received an empty response");
												msgbox.setIcon( Ext.MessageBox.ERROR );

											}
										}
							});
            
            	dialog.on( 'hide', function() { dialog.destroy(true); } );
            	dialog.show();
            
            break;

        case 'purge':
        	Ext.Msg.confirm('Remove finished downloads?', 'Are you sure you want to remove finished downloads?' , purgeFiles);
			break;

        case 'pause':
            var num = selectedRows.length;
    		Ext.Msg.confirm('Pause the File?', String.format("Are you sure you want to pause these {0} item(s)?", num ), pauseFiles);
			break;

case 'remove':
            var num = selectedRows.length;
			Ext.Msg.confirm('Remove the File?', String.format("Are you sure you want to remove these {0} item(s)?", num ), removeFiles);
			break;
		case 'download':
			document.location = 'index.php?action=download&gid='+ encodeURIComponent(Ext.getCmp("fileGrid").getSelectionModel().getSelected().get('gid'));
			break;
	}
}
function handleCallback(requestParams, node) {
	var conn = new Ext.data.Connection();

	conn.request({
		url: 'index.php',
		params: requestParams,
		callback: function(options, success, response ) {
			if( success ) {
//                Ext.Msg.alert(response.responseText);
				json = Ext.decode( response.responseText );
				if( json.success ) {
					statusBarMessage( json.message, false, true );
					datastore.reload();
				} else {
					Ext.Msg.alert( 'Failure', json.error );
				}
			}
			else {
				Ext.Msg.alert( 'Error', 'Failed to connect to the server.');
			}

		}
	});
}
function getRequestParams() {
	var selitems, dir, node;
	var selectedRows = Ext.getCmp("fileGrid").getSelectionModel().getSelections();
	selitems = Array(selectedRows.length);
		if( selectedRows.length > 0 ) {
			for( i=0; i < selectedRows.length;i++) {
				selitems[i] = selectedRows[i].get('gid');
			}
		}
		
	//Ext.Msg.alert("Debug", gid );
	var requestParams = {
		item: selitems.length > 0 ? selitems[0]:'',
		'selitems[]': selitems
	};
	return requestParams;
}
/**
* Function for actions, which don't require a form like download, extraction, deletion etc.
*/
function removeFiles(btn) {
	if( btn != 'yes') {
		return;
	}
	requestParams = getRequestParams();
	requestParams.action = 'remove';
	handleCallback(requestParams);
}


function purgeFiles(btn) {
    if( btn != 'yes') {
		return;
	}
	requestParams = getRequestParams();
	requestParams.action = 'purge';
	handleCallback(requestParams);
}


function pauseFiles(btn) {
    if( btn != 'yes') {
    	return;
	}
	requestParams = getRequestParams();
	requestParams.action = 'pause';
	handleCallback(requestParams);
}

Ext.msgBoxSlider = function(){
    var msgCt;

    function createBox(t, s){
        return ['<div class="msg">',
                '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
                '<div class="x-box-ml"><div class="x-box-mr"><div id="x-box-mc-inner" class="x-box-mc"><h3>', t, '</h3>', s, '</div></div></div>',
                '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
                '</div>'].join('');
    }
    return {
        msg : function(title, format){
            if(!msgCt){
                msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
            }
            msgCt.alignTo(document, 't-t');
            var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
            var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s)}, true);
            m.setWidth(400 );
            m.position(null, 5000 );
           m.alignTo(document, 't-t');
           Ext.get('x-box-mc-inner' ).setStyle('background-image', 'url("images/_accept.png")');
           Ext.get('x-box-mc-inner' ).setStyle('background-position', '5px 10px');
           Ext.get('x-box-mc-inner' ).setStyle('background-repeat', 'no-repeat');
           Ext.get('x-box-mc-inner' ).setStyle('padding-left', '35px');
            m.slideIn('t').pause(3).ghost("t", {remove:true});
        }
    };
}();


function statusBarMessage( msg, isLoading, success ) {
	var statusBar = Ext.getCmp('statusPanel');
	if( !statusBar ) return;
	if( isLoading ) {
		statusBar.showBusy();
	}
	else {
		statusBar.setStatus("Done.");
	}
	if( success ) {
		statusBar.setStatus({
		    text: 'Success: ' + msg,
		    iconCls: 'x-status-valid',
		    clear: true
		});
		Ext.msgBoxSlider.msg('Success', msg );
	} else if( success != null ) {
		statusBar.setStatus({
		    text: 'Error: ' + msg,
		    iconCls: 'x-status-error',
		    clear: true
		});
		
	}
	

}