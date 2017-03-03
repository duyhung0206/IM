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
 */
class Magestore_Inventorybarcode_Model_Source_Barcodeattribute extends Varien_Object {

    /**
     * get model option as array
     *
     * @return array
     */
    public function toOptionArray() {
        $options = array();
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addFieldToFilter('is_unique', 1)
                ->addFieldToFilter('frontend_input', 'text')
        ;
        if (count($attributes)) {
            foreach ($attributes as $attribute) {
                $options[] = array('value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel());
            }
        }
        return $options;
    }

}
