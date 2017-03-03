<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Flat sales order grid collection
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Magestore_Inventoryshipment_Model_Mysql4_Order_Grid_Collection extends Mage_Sales_Model_Mysql4_Order_Grid_Collection
{
    protected $_isGroupSql = false;
    public function setIsGroupCountSql($value) {
        $this->_isGroupSql = $value;
        return $this;
    }
    
    public function getSelectCountSql() {
        if ($this->_isGroupSql) {
            $this->_renderFilters();
            $countSelect = clone $this->getSelect();
            $countSelect->reset(Zend_Db_Select::ORDER);
            $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
            $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
            
            $countSelect->reset(Zend_Db_Select::COLUMNS);
            if (count($this->getSelect()->getPart(Zend_Db_Select::GROUP)) > 0) {
                $countSelect->reset(Zend_Db_Select::GROUP);
                $countSelect->distinct(true);
                $group = $this->getSelect()->getPart(Zend_Db_Select::GROUP);
                $countSelect->columns("COUNT(DISTINCT " . implode(", ", $group) . ")");
            } else {
                $countSelect->columns('COUNT(*)');
            }
            return $countSelect;
        }
        return parent::getSelectCountSql();
    }

    public function getWarehouseShipment(){
        $resource = Mage::getSingleton('core/resource');
        $this->getSelect()
            ->joinLeft(array('order' => $resource->getTableName('sales/order')), 'main_table.entity_id=order.entity_id', array('shipping_progress' => 'shipping_progress', 'order_created_at' => 'created_at','order_store_id'=>'store_id','order_base_grand_total'=>'base_grand_total','order_grand_total'=>'grand_total'))
            ->joinLeft(
                array('inventory_shipment' => $resource->getTableName('inventoryplus/warehouse_shipment')), 'main_table.entity_id=inventory_shipment.order_id', array('GROUP_CONCAT(DISTINCT inventory_shipment.warehouse_name) AS names')
            )
            ->where('order.shipping_progress IN (?)', array(0,1,2))
            ->group('order.entity_id');
        return $this;
    }
}
