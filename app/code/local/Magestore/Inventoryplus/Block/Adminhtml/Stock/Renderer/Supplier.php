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
 * Warehouse Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Block_Adminhtml_Stock_Renderer_Supplier extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $product_id = $row->getEntityId();
        $supplier_products = Mage::getModel('inventorypurchasing/supplier_product')
                ->getCollection()
                ->addFieldToFilter('product_id', $product_id);
        $content = '';
        $check = 0;
        if (count($supplier_products) == 0) {
            $content = Mage::helper('inventoryplus')->__('No Supplier');
        }
        foreach ($supplier_products as $supplier_product) {
            $supplier_id = $supplier_product->getSupplierId();
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/inpu_supplier/edit', array('id' => $supplier_id));
            $supplier = Mage::getModel('inventorypurchasing/supplier')
                    ->getCollection()
                    ->addFieldToFilter('supplier_id', $supplier_id)
                    ->setPageSize(1)->setCurPage(1)
                    ->getFirstItem();
            $name = $supplier->getSupplierName();
            if (in_array(Mage::app()->getRequest()->getActionName(), array('exportCsv', 'exportXml', 'exportCsvProductInfo', 'exportXmlProductInfo'))) {
                if ($check)
                    $content.=', ' . $name;
                else
                    $content.=$name;
            } else
                $content .= "<a href=" . $url . ">$name;<a/>" . "<br/>";
            $check++;
        }
        return $content;
    }

}
