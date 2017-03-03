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
 * @package     Magestore_Inventorybarcode
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorybarcode Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Edit_Renderer_Qty extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $warehouse_id = $row->getWarehouseWarehouseId();
        $product_id = $row->getEntityId();
        $qty = Mage::helper('inventorybarcode')->checkQtyBarcode($product_id);
        if($qty == 0){
            $MaxQtyToCreate = 0;
        }else{
            $qtySelectBarcode = Mage::helper('inventorybarcode')->getQtyBarcore($product_id, $warehouse_id);
            $MaxQtyToCreate = $qtySelectBarcode;
        }
        $name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
        $html = '';
        $html .= '<input onchange="inventorybarcodeCtrl.checkBarcodeQty(this);" type="number" min="0" max="' . floatval($MaxQtyToCreate) . '" ';
        $html .= 'id="product-' . $row->getId() . '" class="input-text barcode-qty" value="' . floatval($MaxQtyToCreate) . '" name="' . $this->escapeHtml($name) . '">';
        $html .= ' / <span class="total-qty" id="total-qty-' . $row->getId() . '">' . floatval($MaxQtyToCreate) . '</span> ';

        return $html;
    }
}