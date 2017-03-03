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
class Magestore_Inventorywarehouse_Block_Adminhtml_Requeststock_Edit_Tabs extends
Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        $this->setId('requeststock_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('inventoryplus')->__('Stock Requesting Information'));
    }

    protected function _beforeToHtml() {
        $id = $this->getRequest()->getParam('id');
        $source = $this->getRequest()->getParam('source');
        $target = $this->getRequest()->getParam('target');
        if(!$id && !$source && !$target)
            $this->addTab('form_section', array(
                'label' => Mage::helper('inventorywarehouse')->__('General Information'),
                'title' => Mage::helper('inventorywarehouse')->__('General Information'),
                'content' => $this->getLayout()
                        ->createBlock('inventorywarehouse/adminhtml_requeststock_edit_tab_form')
                        ->toHtml(),
            ));
        if ($id || ($source && $target)) {
            $this->addTab('products_section', array(
                'label' => Mage::helper('inventorywarehouse')->__('Request Stock'),
                'title' => Mage::helper('inventorywarehouse')->__('Request Stock'),
                'url' => $this->getUrl('*/*/products', array('_current' => true, 'id' => $this->getRequest()->getParam('id'))),
                'class' => 'ajax',
            ));
        }

        return parent::_beforeToHtml();
    }

}
