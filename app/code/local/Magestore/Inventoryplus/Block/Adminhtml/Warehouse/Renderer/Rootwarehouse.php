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
class Magestore_Inventoryplus_Block_Adminhtml_Warehouse_Renderer_Rootwarehouse extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $html = '';
        $isRoot = $row->getIsRoot();

        if ($isRoot) {
            $html .= '<div style="text-transform: uppercase;font-size:10px;background-color:#3CB861;color:#fff;width:100%;height:100%"> ' . $this->__('yes') . ' </div>';
        } else {
            $html .= '<div style="text-transform: uppercase;font-size:10px"> ' . $this->__('no') . ' </div>';
        }
        return $html;
    }

    public function renderExport(Varien_Object $row) {
        $isRoot = $row->getIsRoot();
        if ($isRoot) {
            $html = $this->__('yes');
        } else {
            $html = $this->__('no');
        }
        return $html;
    }

}
