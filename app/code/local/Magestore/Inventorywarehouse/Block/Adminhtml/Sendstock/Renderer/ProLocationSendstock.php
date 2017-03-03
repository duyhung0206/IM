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
class Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Renderer_ProLocationSendstock extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $productId = $row->getId();
        $warehouse_sendstock_id = $this->getRequest()->getParam('id');
        $source = $this->getRequest()->getParam('source');
        $target = $this->getRequest()->getParam('target');

        /* edit send stock */
        if ($warehouse_sendstock_id) {
            $collection = Mage::getModel('inventorywarehouse/sendstock')->getCollection()
                    ->addFieldToFilter('warehouse_sendstock_id', $warehouse_sendstock_id)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
            $warehouseIdFrom = $collection->getWarehouseIdFrom();
            $warehouseIdTo = $collection->getWarehouseIdTo();
            $WarehouseNameFrom = $collection->getWarehouseNameFrom();
            $WarehouseNameTo = $collection->getWarehouseNameTo();
        }

        /* create new send stock */ elseif ($target && $target != 'others') {
            $WarehouseNameFrom = Mage::helper('inventorywarehouse/warehouse')->getWarehouseNameByWarehouseId($source);
            $WarehouseNameTo = Mage::helper('inventorywarehouse/warehouse')->getWarehouseNameByWarehouseId($target);
            $warehouseIdFrom = $source;
            $warehouseIdTo = $target;
        } elseif ($target && $target == 'others') {
            $WarehouseNameFrom = Mage::helper('inventorywarehouse/warehouse')->getWarehouseNameByWarehouseId($source);
            $WarehouseNameTo = 'others';
            $warehouseIdFrom = $source;
            $warehouseIdTo = 0;
        }
        /* get product location */
        $locationFrom = Mage::helper('inventoryplus/warehouse')->getProductLocation($warehouseIdFrom, $productId);
        $locationTo = Mage::helper('inventoryplus/warehouse')->getProductLocation($warehouseIdTo, $productId);
        if (!$locationFrom) {
            $locationFrom = $this->__('N/A Location');
        }if (!$locationTo) {
            $locationTo = $this->__('N/A Location');
        }
        $content = "<span style='color:#00ce78'>$WarehouseNameFrom</span>" . "<br/>" . '(' . $locationFrom . ')' . "<br/>";
        $content .= "<span style='color:#00ce78'>$WarehouseNameTo</span>" . "<br/>" . '(' . $locationTo . ')' . "<br/>";
        return $content;
    }

}
