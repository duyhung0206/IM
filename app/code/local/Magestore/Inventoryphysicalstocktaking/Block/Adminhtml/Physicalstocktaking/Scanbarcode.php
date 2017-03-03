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
 * @package     Magestore_Inventory
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Supplier Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryphysicalstocktaking_Block_Adminhtml_Physicalstocktaking_Scanbarcode extends Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Form
{
    const SCAN_ACTION = 'stock_taking';
    
    /**
     * 
     * @return string
     */
    public function getScanActionName() {
        return self::SCAN_ACTION;
    }

    /*
     * 
     * @return string
     */
    public function getQtyInput() {
        return 'adjust_qty';
    }

}