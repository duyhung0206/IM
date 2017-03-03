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
 * @package     Magestore_Inventorypurchasing
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Lowstock extends Mage_Adminhtml_Block_Widget_Grid_Container
{
   public function __construct()
    {
        $this->_controller = 'adminhtml_purchaseorder_lowstock';
        $this->_blockGroup = 'inventorypurchasing';
        $this->_headerText = Mage::helper('inventorylowstock')->__('Generate Purchase Orders from Low Stocks');
        
        parent::__construct();
        $this->_removeButton('add');
    }
}