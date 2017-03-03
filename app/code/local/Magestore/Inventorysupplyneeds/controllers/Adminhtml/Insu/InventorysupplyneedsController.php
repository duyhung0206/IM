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
 * @package     Magestore_Inventorysupplyneeds
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorysupplyneeds Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventorysupplyneeds
 * @author      Magestore Developer
 */
class Magestore_Inventorysupplyneeds_Adminhtml_Insu_InventorysupplyneedsController extends Magestore_Inventoryplus_Controller_Action {
    
    /*
     * Menu path of this controller
     * 
     * @var string
     */
    protected $_menu_path = 'inventoryplus/purchaseorder/po_supplyneeds';


    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Inventorysupplyneeds_Adminhtml_Insu_InventorysupplyneedsController
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->_menu_path)
                ->_addBreadcrumb(
                        Mage::helper('adminhtml')->__('Manage Supply Needs'), Mage::helper('adminhtml')->__('Manage Supply Needs')
        );
        return $this;
    }

    protected function _hasSupplier() {
        $colection = Mage::getModel('inventorypurchasing/supplier')->getCollection();
        if ($colection->getSize() > 0)
            return true;
        return false;
    }

    /**
     * index action
     */
    public function indexAction() {
        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing') && !$this->_hasSupplier()) {
            Mage::getSingleton('adminhtml/session')->addNotice($this->__('You need to add supplier before using the Supply Needs feature.'));
            return $this->_redirect('adminhtml/inpu_supplier/new');
        }
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Manage Supply Needs'));
        $this->_initAction()
                ->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('adminhtml_inventorysupplyneeds.grid')
                ->setProducts($this->getRequest()->getPost('purchase_products', null));
        $this->renderLayout();
    }

    public function exportCsvAction() {
        $fileName = 'supplyneeds.csv';
        $content = $this->getLayout()
                ->createBlock('inventorysupplyneeds/adminhtml_inventorysupplyneeds_gridexport')
                ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportXmlAction() {
        $fileName = 'supplyneeds.xml';
        $content = $this->getLayout()
                ->createBlock('inventorysupplyneeds/adminhtml_inventorysupplyneeds_gridexport')
                ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }


    /**
     * Create a draff purchase order
     * 
     */
    public function createpoAction() {
        $filter = $this->getRequest()->getParam('top_filter');
        $helperClass = Mage::helper('inventorysupplyneeds');
        if ($helperClass->getDraftPO()->getId()) {
            Mage::getSingleton('adminhtml/session')->addNotice(
                    $helperClass->__('There was an existed draft purchase order. Please process it before creating new one'));
            return $this->_redirect('*/*/index', array('top_filter' => $filter));
        }
        $helperClass->setTopFilter($filter);
        $data = $helperClass->prepareDataForDraftPO();
        try {
            if (!isset($data['product_data']) || !count($data['product_data'])) {
                throw new Exception($helperClass->__('There is no product needed to purchase.'));
            }
            //var_dump($data); die();
            $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')
                    ->addData($data);
            $model->setCreatedAt(now())
                    ->setCreatedBy($this->_getUser()->getUsername());
            $model->setType(Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::SUPPLYNEED_TYPE);
            $model->create();
            Mage::getSingleton('adminhtml/session')
                    ->addSuccess($helperClass->__('The supply needs have been saved successfully as draft purchase order(s).'));
            return $this->_redirect('*/inpu_draftpo/view', array('id' => $model->getId()));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')
                    ->addError($helperClass->__('There is error while creating new draft purchase order.'));
            Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            return $this->_redirect('*/*/index', array('top_filter' => $filter));
        }
    }


    /**
     * Save draft purchase order
     * 
     */
    public function saveDraftPOAction() {
        if (!$data = $this->getRequest()->getPost()) {
            $this->_redirect('*/*/index');
        }
        $productData = $this->_prepareDataForDraftPO($data);
        $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')
                ->load($this->getRequest()->getParam('id'))
                ->setData('product_data', $productData);
        try {
            $model->save();
            Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('inventorysupplyneeds')->__('Data has been saved.'));
            return $this->_redirect('*/inpu_draftpo/viewpo', array('id' => $model->getId()));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            return $this->_redirect('*/inpu_draftpo/viewpo', array('id' => $model->getId()));
        }
    }

    /**
     * Prepare data for saving draft purchase order
     * 
     * @param array
     * @return array
     */
    protected function _prepareDataForDraftPO($postData) {
        $supplierProducts = $postData['supplierproduct'];
        $products = array();
        if (isset($postData['submit_products']) && $postData['submit_products']) {
            $productData = explode('&', urldecode($postData['submit_products']));
            foreach ($productData as $stringData) {
                $explodedData = explode('=', $stringData);
                $fieldData = array();
                Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($explodedData[1]), $fieldData);
                $productId = (int) $explodedData[0];
                $products[$productId] = $fieldData;
                $products[$productId]['supplier_id'] = isset($supplierProducts[$productId]) ? $supplierProducts[$productId] : null;
            }
        }
        return $products;
    }

    public function helper() {
        return Mage::helper('inventorysupplyneeds');
    }

    /**
     * Get logged-in user
     * 
     * @return Varien_Object
     */
    protected function _getUser() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    public function viewpogridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed($this->_menu_path);
    }

}
