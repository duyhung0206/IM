/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @copyright   Copyright (c) 2015 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
var lastMessage = '';
Ajax.ScanBarcodeAutocompleter = Class.create(Autocompleter.Base, {
    initialize: function (element, update, comfirmMessage, scannedInput, scanUrl, resetDataUrl, gridJS, options) {
        this.baseInitialize(element, update, options);
        this.options.asynchronous = true;
        this.options.onComplete = this.onComplete.bind(this);
        this.options.defaultParams = this.options.parameters || null;
        this.comfirmMessage = comfirmMessage;
        this.scanUrl = scanUrl;
        this.resetDataUrl = resetDataUrl;
        this.scannedInput = scannedInput;
        this.gridJS = gridJS;
        Event.observe('reset_barcode_scan', 'click', this.resetData.bind(this));
    },
    getUpdatedChoices: function () {
        //this.startIndicator();
        var entry = encodeURIComponent(this.options.paramName) + '=' +
                encodeURIComponent(this.getToken());

        this.options.parameters = this.options.callback ?
                this.options.callback(this.element, entry) : entry;

        if (this.options.defaultParams)
            this.options.parameters += '&' + this.options.defaultParams;

        new Ajax.Request(this.scanUrl, this.options);
    },
    onComplete: function (request) {
        try {
            var jsonResponse = JSON.parse(request.responseText);
            if (jsonResponse.status == 1) {
                soundManager.setup({
                    url: jsonResponse.media + 'inventorybarcode/audio/soundmanager2/',
                    onready: function () {
                        var successSound = soundManager.createSound({
                            id: 'barcodeBeep',
                            url: jsonResponse.media + 'inventorybarcode/audio/barcode-scanner-beep.mp3'
                        });
                        successSound.play();
                    },
                    ontimeout: function () {
                        var wrongSound = soundManager.createSound({
                            id: 'barcodeBeep',
                            url: jsonResponse.media + 'inventorybarcode/audio/barcode-scanner-wrong.mp3'
                        });
                        wrongSound.play();
                    }
                });
                this.updateLastScannedItems(jsonResponse.productName, jsonResponse.productSku, jsonResponse.barcode);

                //Michael 201602 - update qty product when scan
                var product_qty = 0;
                var rowp = document.getElementsByClassName('row-'+jsonResponse.productId)[0];
                if(rowp && this.options.updateurl){
                    var qty_field = rowp.select('[name="' + this.scannedInput + '"]')[0];
                    if(qty_field) {
                        product_qty = parseFloat(qty_field.value) + 1;
                        var parameters = {product_id: jsonResponse.productId, product_qty_update: product_qty};
                        new Ajax.Request(this.options.updateurl, {
                            parameters: parameters,
                            onSuccess: '',
                            onFailure: function(transport){
                                product_qty = jsonResponse.productId;
                            }
                        });
                    }
                }
                if(!product_qty)
                    product_qty = jsonResponse.qty;
                this.increaseScannedQty(jsonResponse.productId, product_qty);
                //this.increaseScannedQty(jsonResponse.productId, jsonResponse.qty);

                this.element.value = '';
                $(this.options.indicator).hide();
                this.update.hide();
            } else if (jsonResponse.result == 0) {
                soundManager.setup({
                    url: jsonResponse.media + 'inventorybarcode/audio/soundmanager2/',
                    onready: function () {
                        var successSound = soundManager.createSound({
                            id: 'barcodeBeep',
                            url: jsonResponse.media + 'inventorybarcode/audio/barcode-scanner-wrong.mp3'
                        });
                        successSound.play();
                    },
                    ontimeout: function () {
                        var wrongSound = soundManager.createSound({
                            id: 'barcodeBeep',
                            url: jsonResponse.media + 'inventorybarcode/audio/barcode-scanner-wrong.mp3'
                        });
                        wrongSound.play();
                    }
                });
                $(this.options.indicator).hide();
                this.update.hide();
            }
        } catch (e) {
        }
    },
    increaseScannedQty: function (productId, qty) {
        //Element.show('loading-mask');
        this.scannedProductId = productId;
        this.scannedQty = qty;
        /* reload grid data */
        if (this.gridJS) {
            this.gridObject = eval(this.gridJS);
            //this.gridObject.rowClickCallback = null;
            /*
             this.gridObject.initCallback = function (grid) {
             if ($$('.row-' + this.scannedProductId).length) {
             var inputField = $$('.row-' + this.scannedProductId)[0].select('[name="' + this.scannedInput + '"]')[0];
             inputField.value = this.scannedQty;
             alert(inputField.value);
             Event.fire(inputField, 'change');
             } else {
             var hiddenGridData = $$('[name="' + this.hiddenInput + '"]')[0];
             var hiddenGridDataValue = hiddenGridData.value;
             if (hiddenGridDataValue.indexOf(this.scannedProductId) > -1) {
             var re = new RegExp(this.scannedProductId + '=(.*?)&', 'g');
             var hiddenGridDataValue = hiddenGridDataValue.replace(re, '');
             }
             hiddenGridData.value = hiddenGridDataValue + "&" + this.scannedProductId + "=" + encode_base64(this.scannedInput + "=" + this.scannedQty);
             }
             grid.initCallback = null;
             }.bind(this);
             */
            //this.initRowEvent();
            //this.gridObject.doFilter();
            var self = this;

            //self.initRowEvent();
            //self.gridObject.doFilter();
            var p = new Promise(function(resolve, reject) {
                resolve(1);
                self.initRowEvent();
            });

            p.then(function(value) {
                self.gridObject.doFilter();
            });
        }

        //Element.hide('loading-mask');
    },
    updateLastScannedItems: function (name, sku, barcode) {
        var table = document.getElementById("last-scanned-items-table");
        var numRows = table.rows.length;
        if (numRows == 6) {
            table.deleteRow(5);
        }
        var row = table.insertRow(1);
        var cell1 = row.insertCell(0);
        cell1.innerHTML = barcode + ' - ' + name + ' (' + sku + ')';
    },
    resetLastScannedItemTable: function () {
        var table = document.getElementById("last-scanned-items-table");
        for (var i = 1; i <= table.rows.length; i++) {
            table.deleteRow(i);
        }
    },
    /**
     * Check to see if input value has changed
     * @param selector
     * @param callback
     */
    checkInputValueChanged: function (selector, callback) {
        var input = $$('[name="' + selector + '"]')[0];
        var oldValue = input.value;
        setInterval(function () {
            if (input.value != oldValue) {
                oldValue = input.value;
                callback();
            }
        }, 100);
    },
    /**
     * Fire an event
     * 
     * @param {type} element
     * @param {type} event
     * @returns {Boolean}
     */
    fireEvent: function (element, event) {
        if (document.createEventObject) {
            // dispatch for IE
            var evt = document.createEventObject();
            return element.fireEvent('on' + event, evt)
        } else {
            // dispatch for firefox + others
            var evt = document.createEvent("HTMLEvents");
            evt.initEvent(event, true, true);
            return !element.dispatchEvent(evt);
        }
    },
    initRowEvent: function () {
        if(!this.gridJS)
            return;
        if (!this.gridObject) {
            this.gridObject = eval(this.gridJS);
        }
        if(!this.initedRowEvent) {
            if(this.gridObject.initRowCallback) {
                this.oldInitRowCallback = this.gridObject.initRowCallback;
            }
            
            this.gridObject.initRowCallback = function (grid, row) {
                if(this.oldInitRowCallback) {
                    this.oldInitRowCallback(grid, row);
                }  
 
                if (row.hasClassName('row-' + this.scannedProductId)) {
                    var checkbox = row.select('[type="checkbox"]')[0];
                    if(checkbox.checked == false) {
                        checkbox.checked = true;
                        this.fireEvent(checkbox, 'click');
                    }
                    var inputField = row.select('[name="' + this.scannedInput + '"]')[0];
                    inputField.value = this.scannedQty;
                    this.fireEvent(inputField, 'change');
                    this.scannedProductId = '';
                }              
            }.bind(this); 
            this.initedRowEvent = true;
        }
        lastMessage = $('last-scanned-items').innerHTML;
        this.updateDataGrid();

    },    
    resetData: function () {
        if (!confirm(this.comfirmMessage)) {
            return;
        }
        var url = this.resetDataUrl;
        var parameters = {};
        Element.show('loading-mask');
        new Ajax.Request(url, {
            method: 'get',
            parameters: parameters,
            onComplete: function (transport) {
                Element.hide('loading-mask');
                if (transport.responseText.isJSON()) {
                    var response = transport.responseText.evalJSON();
                    if (response.status == '0') {
                        alert(response.message);
                        return;
                    }
                    /* success */
                    location.reload();
                    /* reload grid data */
                    /*
                     if (this.gridJS) {
                     this.gridObject = eval(this.gridJS);
                     this.gridObject.doFilter();
                     }
                     this.resetLastScannedItemTable();
                     */
                }
            }.bind(this)
        });
    },
    updateDataGrid: function() {
        //send stock
        if ($('sendstock_tabs_products_section')) {
            $('sendstock_tabs_products_section').addClassName('notloaded');
            sendstock_tabsJsTabs.showTabContent($('sendstock_tabs_products_section'));

            setTimeout(function () {
                $('last-scanned-items').innerHTML = lastMessage;
            }.bind(this), 2000);
        }
        //request stock
        if ($('requeststock_tabs_products_section')) {
            $('requeststock_tabs_products_section').addClassName('notloaded');
            requeststock_tabsJsTabs.showTabContent($('requeststock_tabs_products_section'));

            setTimeout(function () {
                $('last-scanned-items').innerHTML = lastMessage;
            }.bind(this), 2000);
        }
    }
});