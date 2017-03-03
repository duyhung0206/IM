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
class Magestore_Inventoryplus_Block_Adminhtml_Warehouse_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $warehouseId = $row->getId();
        $adminId = Mage::getModel('admin/session')->getUser()->getId();
        $canEdit = 0;
        $assignment = Mage::getModel('inventoryplus/warehouse_permission')->getCollection()
                ->addFieldToFilter('warehouse_id', $warehouseId)
                ->addFieldToFilter('admin_id', $adminId)
                ->addFieldToFilter('can_edit_warehouse', 1)
                ->setPageSize(1)->setCurPage(1)
                ->getFirstItem();
        if ($assignment->getId())
            $canEdit = 1;
        if ($canEdit == 1) {
            $html = '<a href="' . $this->getUrl('adminhtml/inp_warehouse/edit', array('id' => $warehouseId)) . '">' . Mage::helper('inventoryplus')->__('Edit') . '</a>';
        } else {
            $html = '<a href="' . $this->getUrl('adminhtml/inp_warehouse/edit', array('id' => $warehouseId)) . '">' . Mage::helper('inventoryplus')->__('View') . '</a>';
        }
        return $html;
    }

}
