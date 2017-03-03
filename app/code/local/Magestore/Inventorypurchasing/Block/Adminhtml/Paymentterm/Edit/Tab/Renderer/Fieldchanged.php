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
 * Warehouse Edit Form Content Tab Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Block_Adminhtml_Paymentterm_Edit_Tab_Renderer_Fieldchanged
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) 
    {
        $paymenttermHistoryId = $row->getPaymentTermHistoryId();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $sql = 'SELECT distinct(`field_name`) from ' . $resource->getTableName("erp_inventory_payment_term_history_content") . ' WHERE (payment_term_history_id = '.$paymenttermHistoryId.')';
        $results = $readConnection->fetchAll($sql);
        $content = '';
        foreach ($results as $result) {
            $content .= $result['field_name'].'<br />';
        }
        return $content;
    }
}