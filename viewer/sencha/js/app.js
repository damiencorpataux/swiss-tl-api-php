Ext.setup({
    //tabletStartupScreen: 'tablet_startup.png',
    //phoneStartupScreen: 'phone_startup.png',
    //icon: 'icon.png',
    //glossOnIcon: false,
    onReady: function() {

        Ext.regModel('Station', {
            fields: [
                {name: 'name', type: 'string'}
            ]
        });
        var station_store = new Ext.data.JsonStore({
            model: 'Station',
            proxy: {
                type: 'rest',
                url: '../../service.php',
                extraParams: {
                    provider: 'stations',
                    line: 1
                },
                reader: {
                    type: 'json',
                    root: 'data'
                }
            },
            sorters: 'name',
            getGroupString: function(record) {
                return record.get('name')[0];
            }
        });

        Ext.regModel('Departure', {
            fields: [
                {name: 'time', type: 'string'},
                {name: 'destination', type: 'string'}
            ]
        });
        var departure_store = new Ext.data.JsonStore({
            model: 'Departure',
            proxy: {
                type: 'rest',
                url: '../../service.php',
                extraParams: {
                    provider: 'departures',
                    line: 1
                },
                reader: {
                    type: 'json',
                    root: 'data'
                }
            },
            sorters: 'name',
            getGroupString: function(record) {
                return record.get('name')[0];
            }
        });

        var stations = new Ext.Panel({
            title: 'Stations',
            //scroll: 'vertical',
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [{
                xtype: 'formpanel',
                submitOnAction: false,
                items: [{
                    xtype: 'searchfield',
                    label: 'Stations',
                    listeners: {
                        keyup: function() {
                            var query = this.getValue();
                            station_store.clearFilter();
                            station_store.filter('name', query);
                        }
                    }
                }]
            },{
                xtype: 'list',
                itemTpl : '{name}',
                grouped : true,
                indexBar: true,
                store: station_store,
                listeners: {
                    itemtap: function(dataview, index) {
                        var station = this.store.getAt(index).get('name');
                        departure_store.load({params: {station: station}});
                        Ext.getCmp('departures-panel').setActiveItem(1);
                    },
                    beforerender: function() {
                        //this.store.load();
                    }
                }
            }]
        });

        var departures = new Ext.Panel({
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [{
                html: 'Departures'
            },{
                xtype: 'list',
                itemTpl : '{time} ({destination})',
                store: departure_store,
                listeners: {
                    itemtap: function(dataview, index) {
                        // What nice here?
                    }
                }
            }],
            dockedItems: [{
                dock: 'top',
                xtype: 'toolbar',
                //ui: 'light',
                items: [{
                    text: 'Back',
                    handler: function() {
                        Ext.getCmp('departures-panel').setActiveItem(0);
                    }
                }]
            }]
        });
/*
        var map = new Ext.Map({
            title: 'Map',
            getLocation: true,
            mapOptions: {
                zoom: 12
            }
        });
*/

        var panel = new Ext.TabPanel({
            fullscreen: true,
            items: [{
                id: 'departures-panel',
                title: 'Departures',
                layout: 'card',
                cardSwitchAnimation: 'slide',
                items: [stations, departures]
            },{
                title: 'Complain',
                html: 'You can complain here'
            },{
                title: 'Busters',
                html: 'Reported busters'
            }]
        });

/*
        var tabBar = panel.getTabBar();
        tabBar.addDocked({
            xtype: 'button',
            ui: 'mask',
            iconCls: 'refresh',
            dock: 'right',
            stretch: false,
            align: 'center',
            handler: function() {
        });
*/
    }
});
