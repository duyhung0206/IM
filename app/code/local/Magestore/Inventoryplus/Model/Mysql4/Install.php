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
 * Inventory Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Model_Mysql4_Install extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct() {
        $this->_init('inventoryplus/install', 'install_id');
    }
    
    public function getQtyOrdered() {
        $result = array();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getTable('sales/order'), array('total_qty_ordered', 'entity_id', 'status'));

        $query = $adapter->query($select);
        while ($row = $query->fetch()) {
            $result[] =  $row;
        }        
        return $result;    
    }
}