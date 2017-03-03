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

class Magestore_Inventorypurchasing_Adminhtml_Inpu_DraftpoController extends Magestore_Inventoryplus_Controller_Action{

    /*
     * Menu path of this controller
     * 
     * @var string
     */
    protected $_menu_path = 'inventoryplus/purchaseorder/draftpo';

    
    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Inventorypurchasing_Adminhtml_Inpu_DraftpoController
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->_menu_path)
                ->_addBreadcrumb(
                        Mage::helper('adminhtml')->__('Draft Purchase Orders'), Mage::helper('adminhtml')->__('Draft Purchase Orders')
        );
        return $this;
    }    

   public function viewAction() {
        $id = $this->getRequest()->getParam('id');
        $draftPO = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')->load($id);
        Mage::register('draftpo', $draftPO);       
        $this->_title($this->__('Draft Purchase Orders'));
        $this->_initAction()->_setActiveMenu($this->_menu_path);
        if($draftPO->getType() == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::SUPPLYNEED_TYPE)
            $this->_setActiveMenu($this->_menu_path);
        if($draftPO->getType() == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::LOWSTOCK_TYPE)
            $this->_setActiveMenu('inventoryplus/purchaseorder/po_lowstock');
        if($draftPO->getType() == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::PENDINGORDER_TYPE)
            $this->_setActiveMenu('inventoryplus/purchaseorder/po_pendingorder');        
        $this->renderLayout();
    }


    /**
     * Update draff purchase order
     * 
     */
    public function updateAction() {
        $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')
                ->load($this->getRequest()->getParam('id'));
        $field = $this->getRequest()->getParam('field');
        if (!$field) {
            return $this->getResponse()->setBody(json_encode(array('success' => 0)));
        }
        $value = $this->getRequest()->getParam('value');
        $updateData = Mage::helper('inventorypurchasing/draftpo')->prepareUpdateData($field, $value);
        try {
            $returnObject = $model->update($updateData);
            $return = $returnObject->getData();
            $return['success'] = 1;
            return $this->getResponse()->setBody(json_encode($return));
        } catch (Exception $ex) {
            return $this->getResponse()->setBody(json_encode(array('success' => 0)));
        }
    }

    /**
     * Save draft purchase order
     * 
     */
    public function saveAction() {
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
                    ->addSuccess(Mage::helper('inventorypurchasing')->__('Data has been saved.'));
            return $this->_redirect('*/*/view', array('id' => $model->getId()));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            return $this->_redirect('*/*/view', array('id' => $model->getId()));
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

    /**
     * Create real purchase orders
     * 
     */
    public function submitAction() {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')->load($id);
        try {
            $model->createPurchaseOrders();
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->helper()->__('New purchase orders has been added.'));
            $this->_redirect('adminhtml/inpu_purchaseorders');
        } catch (Exception $e) {
            $model->rollBackPO();
            Mage::getSingleton('adminhtml/session')->addError($this->helper()->__('An error has been occurred when adding new purchase orders. Please try again.'));
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/viewpo', array('id' => $id));
        }
    }

    /**
     * Delete a draft purchase order
     * 
     */
    /**
     * Delete a draft purchase order
     * 
     */
    public function deleteAction() {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')->load($id);
        $type = $model->getType();
        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->helper()->__('The draft purchase order(s) has been deleted successfully.'));
            if($type == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::SUPPLYNEED_TYPE)
                return $this->_redirect('adminhtml/insu_inventorysupplyneeds/index');
            if($type == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::LOWSTOCK_TYPE)
                return $this->_redirect('adminhtml/inpu_lowstock/index');   
            if($type == Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo::PENDINGORDER_TYPE)
                return $this->_redirect('adminhtml/inpu_pendingorder/index'); 
	    return $this->_redirect('adminhtml/inpu_lowstock/index');            
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->helper()->__('Unable to delete draft purchase order(s).'));
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            return $this->_redirect('*/*/viewpo', array('id' => $id));
        }
    }


    /**
     * Mass change suppliers of products in purchase order
     * 
     */
    public function masschangesupplierAction() {
        $id = $this->getRequest()->getParam('id');
        $type = $this->getRequest()->getParam('type');
        try {
            $this->helper()->massChangeSupplier($id, $type);
            Mage::getSingleton('adminhtml/session')->addSuccess($this->helper()->__('The supplier(s) has been changed successully.'));
            $this->_redirect('*/*/view', array('id' => $id));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->helper()->__('An error has been occurred when changing suppliers. Please try again.'));
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/view', array('id' => $id));
        }
    }

    /**
     * Add product to draft purchase order
     * 
     */
    public function addproducttopoAction() {
        $id = $this->getRequest()->getParam('id');
        $sku = $this->getRequest()->getParam('sku');
        $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')->load($id);
        try {
            $productId = Mage::getModel('catalog/product')->getIdBySku($sku);
            if (!$productId) {
                throw new Exception($this->helper()->__('Not found sku: %s', "<i>$sku</i>"));
            }
            $model->addProduct($productId);
            Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->helper()->__('Product %s has been added.', "<i>$sku</i>")
            );
            $this->_redirect('*/*/view', array('id' => $id));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->helper()->__('There is error while adding product.'));
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/view', array('id' => $id));
        }
    }

    /**
     * Remove product from draft purchase order
     * 
     */
    public function removeproductAction() {
        $id = $this->getRequest()->getParam('id');
        $poProductId = $this->getRequest()->getParam('poproductid');
        $model = Mage::getModel('inventorypurchasing/purchaseorder_draftpo_product')->load($poProductId);
        $product = Mage::getResourceModel('catalog/product_collection')
                ->addFieldToFilter('entity_id', $model->getProductId())
                ->setPageSize(1)->setCurPage(1)
                ->getFirstItem();
        try {
            $model->delete();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->helper()->__('Product %s has been removed.', '<i>' . $product->getSku() . '</i>')
            );
            $this->_redirect('*/*/view', array('id' => $id));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($this->helper()->__('There is error while removing product.'));
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/*/view', array('id' => $id));
        }
    }

    public function helper() {
        return Mage::helper('inventorypurchasing/draftpo');
    }

    /**
     * Get logged-in user
     * 
     * @return Varien_Object
     */
    protected function _getUser() {
        return Mage::getSingleton('admin/session')->getUser();
    }

    public function viewgridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed($this->_menu_path);
    }


}
  