<?php 
class Magestore_Inventoryplus_Block_Adminhtml_Warehouse_Edit_Tab_Renderer_Fieldchanged
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) 
    {
        $warehouseHistoryId = $row->getWarehouseHistoryId();
        $fields = Mage::getResourceModel('inventoryplus/warehouse_historycontent')->getFields($warehouseHistoryId);
        $content = implode($fields, '<br/>');
        return $content;
    }
}