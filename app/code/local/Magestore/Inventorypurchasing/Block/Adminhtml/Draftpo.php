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

/**
 * Inventorysupplyneeds Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Inventorypurchasing
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Block_Adminhtml_Draftpo extends Mage_Adminhtml_Block_Widget {

    public function __construct() {
        parent::__construct();
    }

    protected function _prepareLayout() {
        $this->setChild('back_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(
                                array(
                                    'label' => Mage::helper('inventorypurchasing')->__('Back'),
                                    'onclick' => "window.location.href = '" . $this->getUrl('*/*/index', array($this->getRequest()->getParams())) . "'",
                                    'class' => 'back'
                                )
                        )
        );
        $this->setChild('delete_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(
                                array(
                                    'label' => Mage::helper('inventorypurchasing')->__('Delete All Drafts'),
                                    'onclick' => 'if(confirm(\''.$this->__('Are you sure you want to delete all draft purchase orders?').'\')) location.href=\'' . $this->getUrl('*/*/deletepo', array('id'=>$this->getDraftPOId())) . '\'',
                                    'class' => 'delete'
                                )
                        )
        );
        $this->setChild('create_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(
                                array(
                                    'label' => Mage::helper('inventorypurchasing')->__('Create Purchase Order'),
                                    'onclick' => 'if(confirm(\''.$this->__('Are you sure you want to create pending purchase orders from these drafts?').'\')) location.href=\'' . $this->getUrl('*/*/submitpo',array('id'=>$this->getDraftPOId())) . '\'',
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
        if ($this->getRequest()->getParam('id') && !$this->getRequest()->getParam('top_filter'))
            $this->setChild('adminhtml.inventorypurchasing.edit.suggestdraft', $this->getLayout()->createBlock('inventorysupplyneeds/adminhtml_inventorysupplyneeds_edit_suggestdraft')
            );
        else
            $this->setChild('adminhtml.inventorypurchasing.edit.supplyneeds', $this->getLayout()->createBlock('inventorysupplyneeds/adminhtml_inventorysupplyneeds_edit_supplyneeds')
            );
        return parent::_prepareLayout();
    }
    
    public function getDraftPOId(){
        return $this->getRequest()->getParam('id');
    }
    
    /**
     * @param string|int $type
     * @return string
     */
    public function getMassChangeSupplierUrl($type) {
         return $this->getUrl('adminhtml/inpu_inventorysupplyneeds/masschangesupplier',
                                    array('id'=>$this->getDraftPOId(),
                                            'type' => $type,
                            ));
    }    

    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText() {
        if($this->getDraftPO()->getType() == Magestore_Inventorysupplyneeds_Model_Draftpo::SUPPLYNEED_TYPE) {
            return Mage::helper('inventorypurchasing')
                ->__('Draft Purchase Orders (Forecast to %s)', $this->formatDate($this->getDraftPO()->getForecastTo(),'long'));
        }
        
        if($this->getDraftPO()->getType() == Magestore_Inventorysupplyneeds_Model_Draftpo::LOWSTOCK_TYPE) {
            return Mage::helper('inventorypurchasing')
                ->__('Draft Purchase Orders (created from Low Stocks)');
        }        
        
        if($this->getDraftPO()->getType() == Magestore_Inventorysupplyneeds_Model_Draftpo::PENDINGORDER_TYPE) {
            return Mage::helper('inventorypurchasing')
                ->__('Draft Purchase Orders (created from Pending Orders)');
        }          
    }

    public function getSaveUrl() {
        return $this->getUrl('*/*/saveDraftPO', array('_current' => true));
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
    public function getAddProductUrl(){
        return $this->getUrl('*/*/addproducttopo', array('id'=>$this->getRequest()->getParam('id'), '_secure' => true));
    }    

    /**
     * 
     * @return \Magestore_Inventorypurchasing_Model_Draftpo
     */
    public function getDraftPO(){
        return Mage::registry('draftpo');
    }    
}
