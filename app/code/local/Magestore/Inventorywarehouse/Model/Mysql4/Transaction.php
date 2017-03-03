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
 * Inventorywarehouse Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Model_Mysql4_Transaction extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('inventorywarehouse/transaction', 'warehouse_transaction_id');
    }

    /**
     * @return array
     */
    public function getTransactionData()
    {
        $read = $this->_getReadAdapter();
        $select = $read->select()
            ->from(array('ts'=>$this->getTable('inventorywarehouse/transaction')),
                array('type', 'warehouse_transaction_id', 'warehouse_name_from', 'warehouse_name_to', 'warehouse_id_from', 'warehouse_id_to'));
        $query = $read->query($select);
        $data = array();
        while ($row = $query->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @param $collection
     * @param $column
     */
    public function filterTransactionObject($collection, $column)
    {
        $read = $this->_getReadAdapter();
        $value = $column->getFilter()->getValue();
        if (strpos($value,'/') === false) {
            $condition1 = $read->prepareSqlCondition('warehouse_name_from', array('like' => '%' . $value . '%'));
            $condition2 = $read->prepareSqlCondition('warehouse_name_to', array('like' => '%' . $value . '%'));
            $collection->getSelect()
                ->where($condition1)
                ->orWhere($condition2);
        } else {
            $finder = explode('/',$value);
            $condition1 = $read->prepareSqlCondition('warehouse_name_from', array('like' => '%'.$finder[0].'%'));
            $condition2 = $read->prepareSqlCondition('warehouse_name_to', array('like' => '%'.$finder[1].'%'));
            $collection->getSelect()
                       ->where($condition1)
                       ->where($condition2);
        }
    }
}