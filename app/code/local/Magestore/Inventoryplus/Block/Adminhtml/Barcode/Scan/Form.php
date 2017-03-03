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
 * @package     Magestore_Inventoryplus
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

abstract class Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Form extends Mage_Adminhtml_Block_Template
{
    public function __construct() {
        parent::__construct();
        $this->setTemplate('inventoryplus/barcode/scan/form.phtml');
        return $this;
    }
    
    /**
     * 
     * @return Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Form
     */
    protected function _beforeToHtml() {
        if(!Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')) {
            $this->setTemplate(null);
        }
        return parent::_beforeToHtml();
    }


    /**
     * GEt Grid Js Object name
     * 
     * @return string
     */
    public function getGridJsObject() {
        if($this->getGridBlockName()) {
            $grid = $this->getLayout()->getBlock($this->getGridBlockName());
            return $grid->getId().'JsObject';
        }
        return null;
    }
    
    /**
     * 
     * @param string $gridName
     */
    public function setGridBlockName($gridName) {
        $this->setData('grid_block_name', $gridName);
    }
    
    /**
     * Get scan barcode url
     * 
     * @return string
     */
    public function getScanUrl() {
        return $this->getUrl('adminhtml/inb_barcode/scan',  array(
                '_current' => true,
                '_secure' => true,
                'action' => $this->getScanActionName(),
            ));
    }
    
    /**
     * Get reset scan data Url
     * 
     * @return string
     */
    public function getResetDataUrl() {
        return $this->getUrl('adminhtml/inb_barcode/resetscan',  array(
                '_current' => true,
                '_secure' => true,
                'action' => $this->getScanActionName(),
            ));        
    }
    
    /**
     * Get confirm message
     * 
     * @return string
     */
    public function getConfirmMessage() {
        return $this->__('Are you sure?');
    }
    
    /**
     * Get action name
     * 
     * @return string
     */
    abstract public function getScanActionName();
    
    
    /**
     * Get qty input name
     * 
     * @return string
     */    
    abstract public function getQtyInput();

    /**
     * Get reupdate qty data Url
     *
     * @return string
     * Michael 201602
     */
    public function getReUpdateQtyUrl() {
        return $this->getUrl('adminhtml/inb_barcode/reupdateqty',  array(
            '_current' => true,
            '_secure' => true,
            'action' => $this->getScanActionName(),
        ));
    }

    /**
     * Get reupdate qty manual data Url
     *
     * @return string
     * Michael 201602
     */
    public function getReUpdateQtyManualUrl() {
        return $this->getUrl('adminhtml/inb_barcode/reupdateqtymanual',  array(
            '_current' => true,
            '_secure' => true,
            'action' => $this->getScanActionName(),
        ));
    }
}