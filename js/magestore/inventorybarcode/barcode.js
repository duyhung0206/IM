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

var inventorybarcodeController = new Class.create();
inventorybarcodeController.prototype = {
    initialize: function () {

    },
    checkBarcodeQty: function (element) {
        if (isNaN(parseFloat(element.value)) == true) {
            element.value = element.min;
        }

        if (parseFloat(element.value) > parseFloat(element.max)) {
            element.value = element.max;
        }
        if (parseFloat(element.value) < parseFloat(element.min)) {
            element.value = element.min;
        }
    }
}

var inventorybarcodeCtrl = new inventorybarcodeController();