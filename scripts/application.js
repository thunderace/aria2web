/*
* @version		$Id: application.js 9 2010-01-21 16:07:42Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/
Ext.override(Ext.form.FormPanel, {

    beforeDestroy : function(){
        this.stopMonitoring();
        Ext.FormPanel.superclass.beforeDestroy.call(this);
        /*
         * Clear the items here to prevent them being destroyed again.
         * Don't move this behaviour to BasicForm because it can be used
         * on it's own.
         */
        this.form.items.clear();
        Ext.destroy(this.form);
    }

});
var pageSize = 30;
var datastore;
Ext.BLANK_IMAGE_URL = 'scripts/extjs/resources/images/default/s.gif';

Ext.onReady(function(){

	var xg = Ext.grid;
    
    var reader = new Ext.data.JsonReader({
                    root: "items",
                    totalProperty: "totalCount"
                }, 
                Ext.data.Record.create([
                    {name: "gid"},
                    {name: "status"},
                    {name: "totalLength"},
                    {name: "name"},
                    {name: "completedLength"},
                    {name: "uploadLength"},
                    {name: "bitfield"},
                    {name: "downloadSpeed"},
                    {name: "uploadSpeed"},
                    {name: "estimatedTime"},
                    {name: "infohash"},
                    {name: "numSeeders"},
                    {name: "pieceLength"},
                    {name: "numPieces"},
                    {name: "connections"},
                    {name: "errorCode"},
                    {name: "followedBy"},
                    {name: "belongsTo"}, 
                    {name: "package"},
                    {name: "dir"}
                ]));
	  // create the Data Store
    datastore = new Ext.data.Store({
        reader: reader,
        proxy: new Ext.data.HttpProxy({
        	 url: 'index.php',
        }),
        action: "tellActive",
        baseParams:{start:0, limit:pageSize, action: "tellActive"  }
    });
   
   
   
    var mask =new Ext.LoadMask(Ext.getBody(),{msg:'Loading data...', store: datastore});

    var gridtb = new Ext.Toolbar([
           	{
               	xtype: "tbbutton",
           		id: 'tb_home',
           		icon: 'images/_add.png',
           		text: 'Add new Download',
           		tooltip: 'Allows you to add a new download to the queue',
           		cls:'x-btn-text-icon',
           		handler: function() { openActionDialog(this, 'add'); }
           	},
           	{
               	xtype: "tbbutton",
           		id: 'tb_remove',
           		icon: 'images/_remove.png',
           		text: 'Remove',
           		disabled: true,
           		tooltip: 'Allows you to remove a download from the queue',
           		cls:'x-btn-text-icon',
           		handler: function() { openActionDialog(this, 'remove'); }
           	},
               {
               	xtype: "tbbutton",
           		id: 'tb_pause_all',
           		icon: 'images/_pause.png',
           		text: 'Pause All',
           		tooltip: 'Allows you to pause all downloads',
           		cls:'x-btn-text-icon',
           		handler: function() { openActionDialog(this, 'pauseAll'); }
           	},
               {
               	xtype: "tbbutton",
           		id: 'tb_start_all',
           		icon: 'images/_pause.png',
           		text: 'start All',
           		tooltip: 'Allows you to start all downloads',
           		cls:'x-btn-text-icon',
           		handler: function() { openActionDialog(this, 'startAll'); }
           	},
              {
           		xtype: "tbbutton",
           		id: 'tb_reload',
                icon: 'images/_reload.png',
                text: 'Refresh',
              	tooltip: 'Refreshed the download list',
                cls:'x-btn-text-icon',
                handler: function() { datastore.load(); }
              }, '-',
              {
                  text: 'Show Active',
                  enableToggle: true,
                  id: 'btn_showactive',
                  toggleHandler: onItemToggle,
                  pressed: true
              },
              {
                  text: 'Show Finished/Error',
                  enableToggle: true,
                  id: 'btn_showstopped',
                  toggleHandler: onItemToggle,
                  pressed: false
              },'-',
              {
                xtype: "tbbutton",
           		id: 'tb_purgeFinished',
                icon: 'images/_reload.png',
                text: 'Purge Finished',
                disabled: true,
              	tooltip: 'Purge Finished',
                cls:'x-btn-text-icon',
                handler: function() { openActionDialog(this, 'purgeFinished'); }
              },
                {
                xtype: "tbbutton",
               	id: 'tb_purgeErr',
                icon: 'images/_reload.png',
                text: 'Purge Error',
                disabled: true,
              	tooltip: 'Purge Error',
                cls:'x-btn-text-icon',
                handler: function() { openActionDialog(this, 'purgeErr'); }
              },'-',
              {
                  text: 'Auto-Refresh',
                  enableToggle: true,
                  id: 'btn_autorefresh',
                  pressed: false
              },
              
              '-',
              {
           		xtype: "tbbutton",
           		id: 'tb_globaloptions',
                icon: 'images/_options.png',
                text: 'Global Options',
              	tooltip: 'Allows you to change the global options for all downloads',
                cls:'x-btn-text-icon',
                handler: function() { openActionDialog(this, 'globalOptions') }
              },'-',
				{	// LOGOUT
					xtype: "tbbutton",
					id: 'tb_logout',
					icon: 'images/_logout.png',
					tooltip: 'Logout',
					cls:'x-btn-icon',
					handler: function() { document.location.href='index.php?logout'; }
				},
           ]);
    
    function onItemToggle(item, pressed){
    	
    	datastore.baseParams.action = item.id == 'btn_showstopped' ? "tellStopped" : "tellActive";

        if(item.id == 'btn_showstopped' && pressed == true ) 
            {
        	Ext.getCmp("btn_showactive").toggle( false, true );
            Ext.getCmp("tb_start_all").disable();
            Ext.getCmp("tb_pause_all").disable();
            Ext.getCmp("tb_purgeErr").enable();
            Ext.getCmp("tb_purgeFinished").enable();
            datastore.baseParams.action = "tellStopped";
    	    } 
        else 
            if(item.id == 'btn_showactive' && pressed == true) 
                {
        	    Ext.getCmp("btn_showstopped").toggle( false, true );
                Ext.getCmp("tb_purgeErr").disable();
                Ext.getCmp("tb_purgeFinished").disable();
                Ext.getCmp("tb_start_all").enable();
                Ext.getCmp("tb_pause_all").enable();
                datastore.baseParams.action = "tellActive";
                }
    	datastore.load();
    };
    // add a paging toolbar to the grid's footer
    var gridbb = new Ext.PagingToolbar({
        store: datastore,
        pageSize: pageSize,
        displayInfo: true,
        emptyMsg: "No download to display",
		items: ['-',' ',' ',' ',' ',' ',
			new Ext.ux.StatusBar({
			    defaultText: 'Done',
		        text: 'Ready',
		        iconCls: 'x-status-valid',
			    id: 'statusPanel'
			})]
    });
    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store

    var cm = new xg.ColumnModel({
        store: datastore,
        onRender:function() {
                // call parent
//                Example.Grid.superclass.onRender.apply(this, arguments);
         
                // load the store
                this.store.load({params:{start:0, limit:pageSize}});
         
            },
		columns: [
			{id: 'gridcm', header: "ID", dataIndex: 'gid', width: 50, css: 'white-space:normal;',  sortable: true},
    		{id: 'package', header: "Package", dataIndex: 'package', width: 120 , sortable: true},
			{header: "Name", dataIndex: 'name', width: 350, sortable: true},
			{header: "CompletedLength", dataIndex: 'completedLength', width: 120, sortable: true},
			{header: "TotalLength", dataIndex: 'totalLength', width: 120, sortable: true},
			{header: "Download Speed", dataIndex: 'downloadSpeed', width: 100},
			{header: "Upload Speed", dataIndex: 'uploadSpeed', width: 100},
			{header: "Estimated Time", dataIndex: 'estimatedTime', width: 100},
			{header: "# Connections", dataIndex: 'connections', width: 60},
    		{header: "StatusHidden", dataIndex: 'status', width: 250, align: 'right', hidden: true, hideable: false },
    		{header: "Status", xtype: 'actioncolumn', width: 60, align: 'center',
                items: [{
                    getClass: function(v, meta, rec) { 
                        // Or return a class from a function
                        this.items[0].tooltip = rec.get('status');
                        if (rec.get('status') == 'waiting') 
                            return 'status-wait';
                        else
                            if(rec.get('status') == 'active')
                                return 'status-down';
                            else
                                if(rec.get('status') == 'error')
                                    return 'status-err';
                                else
                                    {
                                    if(rec.get('status') == 'complete')
                                        return 'status-success';
                                    else
                                        if(rec.get('status') == 'paused')
                                            return 'status-paused';
                                        else
                                            return '';
                                    }
                    }
                }]
		    },  
			{header: "Target dir", dataIndex: 'dir', width: 120 , sortable: true},
			{dataIndex: 'bitfield', hidden: true, hideable: false },
			{dataIndex: 'infohash', hidden: true, hideable: false },
			{dataIndex: 'numSeeders', hidden: true, hideable: false },
			{dataIndex: 'pieceLength', hidden: true, hideable: false },
			{dataIndex: 'errorCode', hidden: true, hideable: false },
			{dataIndex: 'followedBy', hidden: true, hideable: false },
			{dataIndex: 'belongsTo', hidden: true, hideable: false },
			{dataIndex: 'numPieces', hidden: true, hideable: false }
			],
			view: new Ext.grid.GroupingView({
				forceFit:true,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
			})		
	});
	

    // by default columns are sortable
    cm.defaultSortable = true;

    // The Quicktips are used for the toolbar and Tree mouseover tooltips!
	Ext.QuickTips.init();

	  function rowContextMenu(grid, rowIndex, e, f) {
	    	if( typeof e == 'object') {
	    		e.preventDefault();
	    	} else {
	    		e = f;
	    	}
	    	gsm = Ext.getCmp("fileGrid").getSelectionModel();
	    	gsm.clickedRow = rowIndex;
	    	var selections = gsm.getSelections();
			showingFinished = Ext.getCmp("btn_showstopped").pressed;
			gridCtxMenu.items.get('gc_start').enable();
            
			if( !showingFinished) {
				gridCtxMenu.items.get('gc_download').disable();
			} else {
	    		gridCtxMenu.items.get('gc_edit').disable();
	    		gridCtxMenu.items.get('gc_delete').enable();				
			}
	    	if( selections.length > 1 ) {
	    		gridCtxMenu.items.get('gc_edit').disable();
	    	} else if(selections.length == 1) {				
	    		//gridCtxMenu.items.get('gc_edit').enable();
	    		//gridCtxMenu.items.get('gc_delete').enable();
	    	}
			if( aria2web_mode != "local" ) {
				gridCtxMenu.items.get('gc_download').disable();
			}
			gridCtxMenu.show(e.getTarget(), 'tr-br?' );

	    }
	    gridCtxMenu = new Ext.menu.Menu({
	    	id:'gridCtxMenu',
	    
	        items: [{
	    		id: 'gc_edit',
	    		icon: 'images/_edit.png',
	    		text: 'Change Options',
	    		handler: function() { openActionDialog(this, 'getOption'); }
	    	},
	    	{
	    		id: 'gc_delete',
	    		icon: 'images/_remove.png',
	    		text: 'Remove File',
	    		handler: function() { openActionDialog(this, 'remove'); }
	    	},
	    	{
	    		id: 'gc_download',
	    		icon: 'images/_down.png',
	    		text: 'Download File',
	    		handler: function() { openActionDialog(this,'download'); }
	    	},
        	{
	    		id: 'gc_start',
	    		icon: 'images/_down.png',
	    		text: 'Start Download',
	    		handler: function() { openActionDialog(this,'start'); }
	    	},
        	{
	    		id: 'gc_pause',
	    		icon: 'images/_down.png',
	    		text: 'Pause Download',
	    		handler: function() { openActionDialog(this,'pause'); }
	    	},
	    	'-',
			{
				id: 'cancel',
	    		icon: 'images/_cancel.png',
	    		text: 'Cancel',
	    		handler: function() { gridCtxMenu.hide(); }
	    	}
	    	]
	    });
	    
	
    // create the grid
    var viewport = new Ext.Viewport({	
	    defaults: {
	        split: true,
	    	frame: true
	    },
       layout: "border",
       renderTo:'downloads-grid',
       items: [{
    	   region: 'north',
    	   height: 70,
    	   contentEl: "header"
       }, {
    	   xtype: 'grid',
    	   id: 'fileGrid',
    	   title: "Download List",
	        store: datastore,
	        colModel: cm,
	        selModel: new Ext.grid.RowSelectionModel({
        		listeners: {
					'rowselect': { fn: handleRowClick },
        			'selectionchange': { fn: handleRowClick }
    			}
    		  }),
    		 listeners: { 
    	   		'rowcontextmenu': { fn: rowContextMenu }
       		},
	       	tbar: gridtb,
	       	bbar: gridbb,
	        width:'100%',
			split: true,
			region: 'center'
       },{
			region: "south",
			contentEl: "bottom" 
		}]

    });

    function handleRowClick(sm, rowIndex, r) {
    	
    	var selections = sm.getSelections();
    	tb = Ext.getCmp("fileGrid").getTopToolbar();		
    	if( selections.length >= 1 ) 
            {
        	tb.items.get('tb_remove').enable();
//        	tb.items.get('tb_pause').enable();
            }
	    else 
            {
    		tb.items.get('tb_remove').disable();
//    		tb.items.get('tb_pause').disable();
            }
    	return true;
    }
    

    
    firstRun = true;
    
    Ext.TaskMgr.start({
        run: function() {  
    		if( !firstRun 
    				&& Ext.getCmp("fileGrid").getStore().getTotalCount() > 0 
    				&& !Ext.getCmp("btn_showstopped").pressed
    				&&  Ext.getCmp("btn_autorefresh").pressed ) {
    			datastore.load(); 
    		} else if( firstRun ) {
    			datastore.load();firstRun = false;
    		}
    	},
        interval: 10000
    });
       
});
