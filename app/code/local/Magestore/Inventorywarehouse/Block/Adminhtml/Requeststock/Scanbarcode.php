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
 * @package     Magestore_Inventorywarehouse
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorywarehouse Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Block_Adminhtml_Requeststock_Scanbarcode extends Magestore_Inventorybarcode_Block_Adminhtml_Scan_Form
{
    const SCAN_ACTION = 'request_stock';
    
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
        return 'qty_request';
    }

}