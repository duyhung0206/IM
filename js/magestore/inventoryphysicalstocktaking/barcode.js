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
 * @package     Magestore_Inventoryfulfillment
 * @copyright   Copyright (c) 2015 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

Ajax.PhysicalStockTakingBarcodeAutocompleter = Class.create(Autocompleter.Base, {
    initialize: function (element, update, url, options) {
        this.baseInitialize(element, update, options);
        this.options.asynchronous = true;
        this.options.onComplete = this.onComplete.bind(this);
        this.options.defaultParams = this.options.parameters || null;
        this.url = url;
    },

    getUpdatedChoices: function () {
        this.startIndicator();

        var entry = encodeURIComponent(this.options.paramName) + '=' +
            encodeURIComponent(this.getToken());

        this.options.parameters = this.options.callback ?
            this.options.callback(this.element, entry) : entry;

        if (this.options.defaultParams)
            this.options.parameters += '&' + this.options.defaultParams;

        new Ajax.Request(this.url, this.options);
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
                physicalStockTakingBarcodeCtrl.updateLastScannedItems(jsonResponse.productName, jsonResponse.productSku, jsonResponse.productImage);
                physicalStockTakingBarcodeCtrl.increaseStockTakeQty(jsonResponse.productId, jsonResponse.qty);
                $('barcode_search').value = '';
                $('barcode_search_indicator').hide();
                $('barcode_search_autocomplete').hide();
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
                $('barcode_search_indicator').hide();
                $('barcode_search_autocomplete').hide();
            }
        }
        catch (e) {
            this.updateChoices(request.responseText);
        }

    }
});

var physicalStockTakingBarcodeController = new Class.create();
physicalStockTakingBarcodeController.prototype = {
    initialize: function () {

    },
    getSelectionId: function (li) {
        return false;
    },
    increaseStockTakeQty: function (productId, qty) {
        Element.show('loading-mask');
        //var hiddenGridData = $$('[name="physicalstocktaking_products"]')[0];
        //var hiddenGridDataValue = hiddenGridData.value;
        //if (hiddenGridDataValue.indexOf(productId) > -1) {
        //    var re = new RegExp(productId + '=(.*?)&', 'g');
        //    var hiddenGridDataValue = hiddenGridDataValue.replace(re, '');
        //}
        //hiddenGridData.value = hiddenGridDataValue + "&" + productId + "=" + encode_base64("adjust_qty=" + qty);
        productGridJsObject.reload();
        Element.hide('loading-mask');
    },
    updateLastScannedItems: function (name, sku, image) {
        var table = document.getElementById("last-scanned-items-table");
        var numRows = table.rows.length;
        if (numRows == 6) {
            table.deleteRow(5);
        }
        var row = table.insertRow(1);
        var cell1 = row.insertCell(0);
        var cell2 = row.insertCell(1);
        var cell3 = row.insertCell(2);
        cell1.innerHTML = name;
        cell2.innerHTML = sku;
        cell3.innerHTML = '<img src="' + image + '"/>';
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
    }
}

var physicalStockTakingBarcodeCtrl = new physicalStockTakingBarcodeController();