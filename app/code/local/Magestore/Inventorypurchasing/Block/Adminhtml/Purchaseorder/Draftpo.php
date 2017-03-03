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
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Draftpo extends Mage_Adminhtml_Block_Widget {

    protected function _prepareLayout() {

        $this->setChild('back_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(
                                array(
                                    'label' => Mage::helper('inventorypurchasing')->__('Back'),
                                    'onclick' => "window.location.href = '" . $this->getBackUrl() . "'",
                                    'class' => 'back'
                                )
                        )
        );
        $this->setChild('delete_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(
                                array(
                                    'label' => Mage::helper('inventorypurchasing')->__('Delete All Drafts'),
                                    'onclick' => 'if(confirm(\'' . $this->__('Are you sure you want to delete all draft purchase orders?') . '\')) location.href=\'' . $this->getUrl('*/*/delete', array('id' => $this->getDraftPOId())) . '\'',
                                    'class' => 'delete'
                                )
                        )
        );
        $this->setChild('create_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(
                                array(
                                    'label' => Mage::helper('inventorypurchasing')->__('Create Purchase Order'),
                                    'onclick' => 'if(confirm(\'' . $this->__('Are you sure you want to create pending purchase orders from these drafts?') . '\')) location.href=\'' . $this->getUrl('*/*/submit', array('id' => $this->getDraftPOId())) . '\'',
                                    'class' => 'save'
                                )
                        )
        );
        $this->setChild('add_product_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(array(
                            'label' => Mage::helper('inventorypurchasing')->__('Add Product'),
                            'onclick' => "drafPOObject.addProductToDraftPO();return false;",
                            'class' => 'add'
                                )
                        )
        );
        /*
        if ($this->getRequest()->getParam('id') && !$this->getRequest()->getParam('top_filter'))
            $this->setChild('adminhtml.inventorysupplyneeds.edit.suggestdraft', $this->getLayout()->createBlock('inventorysupplyneeds/adminhtml_inventorysupplyneeds_edit_suggestdraft')
            );
        else
            $this->setChild('adminhtml.inventorysupplyneeds.edit.supplyneeds', $this->getLayout()->createBlock('inventorysupplyneeds/adminhtml_inventorysupplyneeds_edit_supplyneeds')
            );
         */
        return parent::_prepareLayout();
    }
    
    /**
     * 
     * @return string
     */
    public function getBackUrl() {
        $type = $this->getDraftPO()->getType();
        $backUrl = $this->getUrl('adminhtml/inpu_pendingorder/index', array($this->getRequest()->getParams())); 
        if($type == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::SUPPLYNEED_TYPE)
            $backUrl = $this->getUrl('adminhtml/insu_inventorysupplyneeds/index', array($this->getRequest()->getParams()));
        if($type == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::LOWSTOCK_TYPE)
            $backUrl = $this->getUrl('adminhtml/inpu_lowstock/index', array($this->getRequest()->getParams()));   
        if($type == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::PENDINGORDER_TYPE)
            $backUrl = $this->getUrl('adminhtml/inpu_pendingorder/index', array($this->getRequest()->getParams())); 
        return $backUrl;
    }

    public function getDraftPOId() {
        return $this->getRequest()->getParam('id');
    }

    /**
     * @param string|int $type
     * @return string
     */
    public function getMassChangeSupplierUrl($type) {
        return $this->getUrl('adminhtml/inpu_draftpo/masschangesupplier', array('id' => $this->getDraftPOId(),
                    'type' => $type,
        ));
    }

    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText() {
        if ($this->getDraftPO()->getType() == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::SUPPLYNEED_TYPE) {
            return Mage::helper('inventorypurchasing')
                            ->__('Draft Purchase Orders (Forecast to %s)', $this->formatDate($this->getDraftPO()->getForecastTo(), 'long'));
        }

        if ($this->getDraftPO()->getType() == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::LOWSTOCK_TYPE) {
            return Mage::helper('inventorypurchasing')
                            ->__('Draft Purchase Orders (created from Low Stocks)');
        }

        if ($this->getDraftPO()->getType() == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::PENDINGORDER_TYPE) {
            return Mage::helper('inventorypurchasing')
                            ->__('Draft Purchase Orders (created from Pending Orders)');
        }
    }

    public function getSaveUrl() {
        return $this->getUrl('*/*/save', array('_current' => true));
    }

    public function getDeleteButtonHtml() {
        return $this->getChildHtml('delete_button');
    }

    public function getCreateButtonHtml() {
        return $this->getChildHtml('create_button');
    }

    public function getBackButtonHtml() {
        return $this->getChildHtml('back_button');
    }

    public function getAddProductButtonHtml() {
        return $this->getChildHtml('add_product_button');
    }

    /**
     * Get add product to draff purchase order url
     * 
     * @return string
     */
    public function getAddProductUrl() {
        return $this->getUrl('*/*/addproducttopo', array('id' => $this->getRequest()->getParam('id'), '_secure' => true));
    }

    /**
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function getDraftPO() {
        return Mage::registry('draftpo');
    }

}
