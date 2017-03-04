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
 * Inventory Supplier Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Editdelivery extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'inventorywarehouse';
        $this->_controller = 'adminhtml_sendstock';
        $sendstock_id = $this->getRequest()->getParam('sendstock_id');
        $this->_updateButton('save', 'label', Mage::helper('inventorywarehouse')->__('Create Delivery'));
        $this->_updateButton('back', 'onclick', 'setLocation(\''.$this->getUrl("adminhtml/inw_sendstock/edit", array("id" => $sendstock_id)).'\')');
        $this->removeButton('delete');
        $this->_formScripts[] = "
            $('edit_form').writeAttribute('action', '".$this->getUrl('*/*/saveDelivery',array('id' => $sendstock_id))."');
        ";
        
    }
    
    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        $sendstock_id = $this->getRequest()->getParam('sendstock_id');
        return Mage::helper('inventorypurchasing')->__("Create New Delivery For Sendstock No. '%s'", $sendstock_id);
    }
}