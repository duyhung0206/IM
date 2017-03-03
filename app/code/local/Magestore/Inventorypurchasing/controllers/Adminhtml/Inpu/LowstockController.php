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
 * Inventorypurchasing Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventorylowstock
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Adminhtml_Inpu_LowstockController extends Magestore_Inventoryplus_Controller_Action {
    /*
     * Menu path of this controller
     * 
     * @var string
     */
    protected $_menu_path = 'inventoryplus/purchaseorder/po_lowstock';

    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Inventorylowstock_Adminhtml_NotificationlogController
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->_menu_path)
                ->_addBreadcrumb(
                        Mage::helper('adminhtml')->__('Generate Purchase Orders from Low Stocks'), Mage::helper('adminhtml')->__('Generate Purchase Orders from Low Stocks')
        );
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Generate Purchase Orders from Low Stocks'));
        return $this;
    }

    /**
     * index action
     */
    public function indexAction() {

		if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing') && !$this->_hasSupplier()) {
            Mage::getSingleton('adminhtml/session')->addNotice($this->__('You need to add supplier before using the Generating P.O from Low Stocks feature.'));
            return $this->_redirect('adminhtml/inpu_supplier/new');
        }

        if ($this->getRequest()->getPost('createpo') == 'createpo') {
            return $this->_forward('createpo');
        }
        $this->_initAction();
        $this->getLayout()->getBlock('inventorypurchasing.lowstock.grid')
                ->setProducts($this->getRequest()->getPost('purchasing_products', null));
        $this->renderLayout();
    }

    /**
     * Grid action
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('inventorypurchasing.lowstock.grid')
                ->setProducts($this->getRequest()->getPost('purchasing_products', null));
        $this->renderLayout();
    }

    /**
     * Change supplier
     * 
     * @return type
     */
    public function changesupplierAction() {
        $supplierId = $this->getRequest()->getPost('supplier_id');
        Mage::getSingleton('adminhtml/session')->setData('lowstock_curr_supplier_id', $supplierId);
    }

    public function createpoAction() {
        $helperClass = Mage::helper('inventorypurchasing/draftpo');
        if ($helperClass->getDraftPO()->getId()) {
            Mage::getSingleton('adminhtml/session')->addNotice(
                    $helperClass->__('There was an existed draft purchase order. Please process it before creating new one'));
            return $this->_redirect('*/*/index', $this->_helper()->prepareParams());
        }
        $data = $this->_helper()->prepareDataForDraftPO();
        try {
            if (!isset($data['product_data']) || !count($data['product_data'])) {
                throw new Exception($this->_helper()->__('There is no product needed to purchase.'));
            }
            $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')
                    ->addData($data);
            $model->setCreatedAt(Mage::getModel('core/date')->date())
                    ->setCreatedBy($this->_getUser()->getUsername());
            $model->setType(Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::LOWSTOCK_TYPE);
            $model->create();
            Mage::getSingleton('adminhtml/session')
                    ->addSuccess($this->_helper()->__('The purchase data have been saved successfully as draft purchase order(s).'));
            return $this->_redirect('adminhtml/inpu_draftpo/view', array('id' => $model->getId()));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')
                    ->addError($this->_helper()->__('There is error while creating new draft purchase order.'));
            Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            return $this->_redirect('*/*/index', $this->_helper()->prepareParams());
        }
    }

    /**
     * export grid item to CSV type
     */
    public function exportCsvAction() {
        $fileName = 'lowstocks.csv';
        $content = $this->getLayout()
                ->createBlock('inventorypurchasing/adminhtml_purchaseorder_lowstock_grid')
                ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction() {
        $fileName = 'lowstocks.xml';
        $content = $this->getLayout()
                ->createBlock('inventorypurchasing/adminhtml_purchaseorder_lowstock_grid')
                ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _helper() {
        return Mage::helper('inventorypurchasing/lowstock');
    }

    /**
     * Get logged-in user
     * 
     * @return Varien_Object
     */
    protected function _getUser() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    /**
     * 
     * 
     * @return boolean
     */
    protected function _hasSupplier() {
        $colection = Mage::getModel('inventorypurchasing/supplier')->getCollection();
        if ($colection->getSize() > 0)
            return true;
        return false;
    }

}
