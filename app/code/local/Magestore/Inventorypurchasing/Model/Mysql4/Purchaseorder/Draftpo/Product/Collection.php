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

class Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Draftpo_Product_Collection 
    extends Magestore_Inventoryplus_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('inventorypurchasing/purchaseorder_draftpo_product');
    }
    public function getDraftpoCollection(){
        $this->getSelect()
                ->join(
                        array('supplierProduct' => $this->getTable('inventorypurchasing/supplier_product')), 'main_table.product_id = supplierProduct.product_id', array('cost', 'tax', 'discount'));
        $this->getSelect()
                ->join(
                        array('supplier' => $this->getTable('inventorypurchasing/supplier')), 'supplierProduct.supplier_id = supplier.supplier_id', array('supplier_name'));
        $this->getSelect()
                ->joinLeft(
                        array('product' => $this->getTable('catalog/product')), 'main_table.product_id = product.entity_id', array('sku'));

        $this->getSelect()->group('main_table.product_id');
        $this->getSelect()->columns(array(
            'supplier_list' => new Zend_Db_Expr("GROUP_CONCAT(supplier.supplier_id, ',,', supplier.supplier_name, ',,', supplierProduct.cost, ',,', supplierProduct.tax, ',,', supplierProduct.discount  SEPARATOR ';')"),
        ));
        return $this;
    }
}