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

class Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Product extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('inventorypurchasing/purchaseorder_product', 'purchase_order_product_id');
    }
    

    public function getLastPurchasedSuppliers($productData) {
        $poProducts = Mage::getResourceModel('inventorypurchasing/purchaseorder_product_collection')
                ->addFieldToFilter('product_id', array('in' => array_keys($productData)))
                ->setOrder('purchase_order_product_id', 'DESC');
        $poProducts->getSelect()->join(array('po' => $this->getTable('inventorypurchasing/purchaseorder')), 'main_table.purchase_order_id = po.purchase_order_id', array('supplier_id'));
        $poProducts->getSelect()->group('main_table.product_id');
        $poProducts->getSelect()->columns(array(
            'list_supplier' => new Zend_Db_Expr('GROUP_CONCAT(po.supplier_id SEPARATOR ",")')
        ));
        return $poProducts;
    }    
}