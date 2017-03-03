<?php

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
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorybarcode Status Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 * Michael 201602
 */
class Magestore_Inventorybarcode_Model_Source_Updatebarcode extends Varien_Object {

    /**
     * get model option as array
     *
     * @return array
     */
    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('inventorybarcode')->__('Use old barcode labels')),
            array('value' => '2', 'label' => Mage::helper('inventorybarcode')->__('Generate new barcode labels')),
            array('value' => '3', 'label' => Mage::helper('inventorybarcode')->__('Create new barcode labels manually')),
        );
    }

}
