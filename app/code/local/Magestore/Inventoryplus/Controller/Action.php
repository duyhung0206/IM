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

/**
 * Inventory Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventoryplus
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Controller_Action extends Mage_Adminhtml_Controller_Action {
    
    /**
     * Menu Path
     * 
     * @var string
     */
    protected $_menu_path;
    
    /**
     * Define active menu item in inventoryplus menu block
     *
     * @return Magestore_Inventoryplus_Adminhtml_Controller_Action
     */
    protected function _setActiveMenu($menuPath)
    {
        if($inventoryMenu = $this->getLayout()->getBlock('inventory_menu')){
            $inventoryMenu->setActive($menuPath);
        }
        return $this;
    }    
    
    /**
     * Check permission
     * 
     * @return boolean
     */
    protected function _isAllowed() {
        if($this->_menu_path)
            return Mage::getSingleton('admin/session')->isAllowed($this->_menu_path);
        else 
            return true;
    }
    
}