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
 * @package     Magestore_Inventory
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

window.onload = function () {
    setCustomObserver();
};

function setCustomObserver() {
    /* inventoryplus_barcode_use_multiple_barcode */
    if($('inventoryplus_barcode_use_multiple_barcode')) {
        var old_value = $('inventoryplus_barcode_use_multiple_barcode').value;
        $('inventoryplus_barcode_use_multiple_barcode').setAttribute('old_value', old_value);
        $('inventoryplus_barcode_use_multiple_barcode').observe('change', inventoryplus_barcode_use_multiple_barcode_change);
        inventoryplus_barcode_use_multiple_barcode_change($('inventoryplus_barcode_use_multiple_barcode'));
    }
    /* inventoryplus_barcode_update_barcode */
    if ($('row_inventoryplus_barcode_update_barcode')) {
        $('row_inventoryplus_barcode_update_barcode').hide();
    }
    if($('inventoryplus_barcode_update_barcode')) {
        $('inventoryplus_barcode_update_barcode').observe('change', inventoryplus_barcode_update_barcode_change); 
        inventoryplus_barcode_update_barcode_change($('inventoryplus_barcode_update_barcode'));
    }
    /* inventoryplus_purchasing_delivery_field_scan */
    if($('inventoryplus_purchasing_delivery_field_scan')) {
        $('inventoryplus_purchasing_delivery_field_scan').observe('change', inventoryplus_purchasing_delivery_field_scan_change); 
        inventoryplus_purchasing_delivery_field_scan_change($('inventoryplus_purchasing_delivery_field_scan'));    
    }

}

function inventoryplus_barcode_update_barcode_change(event) {
    var element = null;
    try {
        element = event.element();
    }catch(e) {
        
    }
    if(!element) {
        element = event;
    }    
    var key = $('row_inventoryplus_barcode_update_barcode');
    var note = key.getElementsByClassName('note')[0];
    if (note) {
        if (element.value == '1') { // use old barcodes
            note.innerHTML = '<strong>You can continue using old barcode labels.</strong>';
        } else if (element.value == '2') { //Automatically generate new barcodes
            note.innerHTML = '<strong>Generate new barcode labels automatically for all products.</strong>';
        } else {
            note.innerHTML = '<strong>Create new barcode labels manually for all products.</strong>';
        }
    }
}

function inventoryplus_purchasing_delivery_field_scan_change(event) {
    var element = null;
    try {
        element = event.element();
    }catch(e) {
        
    }
    if(!element) {
        element = event;
    }    
    var tdElement = element.up('td').next('td', 0);
    if (element.value == 'supplier_sku') {
       tdElement.innerHTML = '<i>You can see Supplier SKU attribute by going to Suppliers in the left Menu, choose one supplier, then select "Products" tab.</i>';
    } else {
        tdElement.innerHTML = '<i>This is an attribute of product.</i>';
    }
}

function inventoryplus_barcode_use_multiple_barcode_change(event) {
    var element = null;
    try {
        element = event.element();
    }catch(e) {
        
    }
    if(!element) {
        element = event;
    }
    var old_value = element.getAttribute('old_value');
    var noteElement = element.next('.note');
    var tdElement = element.up('td').next('td', 0);
    if (element.value == '1') {
        if (old_value == 0) {
            $('row_inventoryplus_barcode_update_barcode').show();
        }
        $('row_inventoryplus_barcode_barcode_attribute').hide();
        $('row_inventoryplus_barcode_pattern').show();
        $('row_inventoryplus_barcode_createbarcode_afterdelivery').show();
        noteElement.innerHTML = '<i>You must to generate new barcode labels for all products.</i>';
    } else {
        if (old_value == 1) {
            var r = confirm("This action will reset the barcode data. Do you really want to change this option?");
            if (r == true) {
            } else {
                element.value = 1;
                return false;
            }
        }
        $('row_inventoryplus_barcode_update_barcode').hide();
        $('row_inventoryplus_barcode_barcode_attribute').show();
        $('row_inventoryplus_barcode_pattern').hide();
        $('row_inventoryplus_barcode_createbarcode_afterdelivery').hide();
        noteElement.innerHTML = '<i>You can continue using your current barcode labels.</i>';
    }
}
