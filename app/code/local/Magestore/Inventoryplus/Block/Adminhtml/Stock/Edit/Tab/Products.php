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
class Magestore_Inventoryplus_Block_Adminhtml_Stock_Edit_Tab_Products extends Mage_Adminhtml_Block_Widget_Grid {

    protected $_isAllWarehouse = true;
    protected $_isEditable = false;

    public function __construct() {
        parent::__construct();
        $this->setId('productGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setVarNameFilter('filter');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $warehouse = Mage::helper('inventoryplus/stock')->getWarehouse();
        if ($warehouse) {
            $warehouseId = $warehouse->getId();
            $adminId = Mage::getSingleton('admin/session')->getUser()->getId();
            if ($this->getRequest()->getActionName() != 'exportCsv' && $this->getRequest()->getActionName() != 'exportXml') {
                $this->_isEditable = Mage::helper('inventoryplus/warehouse')->canEdit($adminId, $warehouseId);
            }
            $this->setDefaultFilter(array('in_products' => 1));
        }
        if (isset($warehouse) && $warehouse->getId() && $warehouse->getId() > 0) {
            $this->_isAllWarehouse = false;
        } else {
            $this->_isAllWarehouse = true;
        }
    }

    protected function _addColumnFilterToCollection($column) {
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds))
                $productIds = 0;
            if ($column->getFilter()->getValue())
                $this->getCollection()->addFieldToFilter('entity_id', array('in' => $productIds));
            elseif ($productIds)
                $this->getCollection()->addFieldToFilter('entity_id', array('nin' => $productIds));
            return $this;
        }
        return parent::_addColumnFilterToCollection($column);
    }

    protected function _prepareCollection() {
        $resource = Mage::getModel('core/resource');
        $warehouse = Mage::helper('inventoryplus/stock')->getWarehouse();
        $supplier_id = Mage::getModel('admin/session')->getData('stock_supplier_id');
        switch ($supplier_id){
            case -2:
                /*All supplier*/
                $supplier_products = Mage::getModel('inventorypurchasing/supplier_product')->getCollection();
                break;
            case -1:
                /*No supplier*/
                $supplier_products = Mage::getModel('inventorypurchasing/supplier_product')->getCollection();
                break;
            case 0:

                break;
            default:
                $supplier_products = Mage::getModel('inventorypurchasing/supplier_product')->getCollection()
                    ->addFieldToFilter('supplier_id', $supplier_id);
                break;
        }
        $arrayProductSupplier = array();
        foreach ($supplier_products as $supplier_product){
            if(!in_array($supplier_product->getProductId(), $arrayProductSupplier)){
                $arrayProductSupplier[] = $supplier_product->getProductId();
            }

        }
        if (!$warehouse) {
            return parent::_prepareCollection();
        }
        $warehouseId = $warehouse->getId();
        if (!$warehouse->getId()) {
            $warehouseId = 0;
        }
        $collection = Mage::getResourceModel('inventoryplus/product_collection');
        $collection->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('fnsku')
                ->addAttributeToSelect('upc')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('attribute_set_id')
                ->addAttributeToSelect('type_id')
                ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        switch ($supplier_id){
            case -2:
                /*All supplier*/
                $collection->addFieldToFilter('entity_id', array('in' => $arrayProductSupplier));
                break;
            case -1:
                /*No supplier*/
                $collection->addFieldToFilter('entity_id', array('nin' => $arrayProductSupplier));
                break;
            case 0:

                break;
            default:
                $collection->addFieldToFilter('entity_id', array('in' => $arrayProductSupplier));
                break;
        }
        if ($this->_isAllWarehouse == true) {
            $collection->getSelect()
                    ->join(
                            array('warehouse_product' => $collection->getTable('inventoryplus/warehouse_product')), 'e.entity_id=warehouse_product.product_id', array('total_qty', 'available_qty')
            );
        } else {
            $collection->joinField('total_qty', 'inventoryplus/warehouse_product', 'total_qty', 'product_id=entity_id', "{{table}}.warehouse_id=$warehouseId", 'right');
            $collection->joinField('available_qty', 'inventoryplus/warehouse_product', 'available_qty', 'product_id=entity_id', "{{table}}.warehouse_id=$warehouseId", 'right');
            $collection->joinField('product_location', 'inventoryplus/warehouse_product', 'product_location', 'product_id=entity_id', "{{table}}.warehouse_id=$warehouseId", 'left');
//            $collection->joinField('fnsku', 'inventoryplus/warehouse_product', 'fnsku', 'product_id=entity_id', "{{table}}.warehouse_id=$warehouseId", 'left');
//            $collection->joinField('upc', 'inventoryplus/warehouse_product', 'upc', 'product_id=entity_id', "{{table}}.warehouse_id=$warehouseId", 'left');
        }
        $collection->getSelect()->group('e.entity_id');
        if ($this->_isAllWarehouse == true) {
            $collection->getSelect()->columns(array(
                'total_physical_qty' => 'SUM(warehouse_product.total_qty)',
                'total_available_qty' => 'SUM(warehouse_product.available_qty)'
            ));
            $sort = $this->getRequest()->getParam('sort');
            $dir = $this->getRequest()->getParam('dir');
            if ($sort == 'total_physical_qty') {
                $collection->getSelect()->order('SUM(warehouse_product.total_qty) ' . $dir);
            } elseif ($sort == 'total_available_qty') {
                $collection->getSelect()->order('SUM(warehouse_product.available_qty)' . $dir);
            }
            $collection->setResetHaving(true);
        }
        $collection->setIsGroupCountSql(true);
        //echo $collection->getSelect()->__toString();die();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $warehouse = Mage::helper('inventoryplus/stock')->getWarehouse();
        if (!$warehouse)
            return parent::_prepareColumns();
        $warehouseId = $warehouse->getId();
        $adminId = Mage::getSingleton('admin/session')->getUser()->getId();

        if (!$this->_isExport) {
            if ($this->_isAllWarehouse == false) {
                $this->addColumn('in_products', array(
                    'header_css_class' => 'a-center',
                    'type' => 'checkbox',
                    'name' => 'in_products',
                    'values' => $this->_getSelectedProducts(),
                    'align' => 'center',
                    'index' => 'entity_id',
                    'editable' => $this->_isEditable,
                    'disabled_values' => $this->_getDisabledProducts()
                ));
            }
        }

        $this->addColumn('entity_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'sortable' => true,
            'width' => '60',
            'type' => 'number',
            'index' => 'entity_id',
        ));

        $this->addColumn('product_name', array(
            'header' => Mage::helper('catalog')->__('Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $this->addColumn('product_sku', array(
            'header' => Mage::helper('catalog')->__('SKU'),
            'width' => '80px',
            'index' => 'sku'
        ));
        if (!$this->_isExport) {
            $this->addColumn('product_image', array(
                'header' => Mage::helper('catalog')->__('Image'),
                'width' => '90px',
                'renderer' => 'inventoryplus/adminhtml_renderer_productimage',
                'index' => 'product_image',
                'filter' => false
            ));
        }
        $this->addColumn('product_status', array(
            'header' => Mage::helper('catalog')->__('Status'),
            'width' => '90px',
            'index' => 'status',
            'type' => 'options',
            'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));

        $this->addColumn('product_price', array(
            'header' => Mage::helper('catalog')->__('Price'),
            'type' => 'currency',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
            'index' => 'price'
        ));
        if ($this->_isAllWarehouse == false) {
            $this->addColumn('total_qty', array(
                'header' => Mage::helper('catalog')->__('Phys. Qty'),
                'width' => '80px',
                'index' => 'total_qty',
                'editable' => $this->_isEditable,
                'type' => 'number',
                'default' => 0
            ));
            $this->addColumn('available_qty', array(
                'header' => Mage::helper('catalog')->__('Avail. Qty'),
                'width' => '80px',
                'type' => 'number',
                'index' => 'available_qty',
            ));
            if ($this->_isExport) {
                $this->addColumn('product_location', array(
                    'header' => Mage::helper('catalog')->__('Product Location'),
                    'width' => '150px',
                    'index' => 'product_location',
                    'editable' => false,
                    'edit_only' => false,
                    'filter' => false,
                ));
                $this->addColumn('fnsku', array(
                    'header' => Mage::helper('catalog')->__('FNSKU'),
                    'width' => '150px',
                    'index' => 'fnsku',
                    'editable' => false,
                    'edit_only' => false,
                    'filter' => false,
                ));
                $this->addColumn('upc', array(
                    'header' => Mage::helper('catalog')->__('UPC'),
                    'width' => '150px',
                    'index' => 'upc',
                    'editable' => false,
                    'edit_only' => false,
                    'filter' => false,
                ));
            } else {
                $this->addColumn('product_location', array(
                    'header' => Mage::helper('catalog')->__('Product Location'),
//                    'width' => '150px',
                    'index' => 'product_location',
                    'editable' => true,
                    'edit_only' => true,
                    'filter' => false,
                    'renderer' => 'inventoryplus/adminhtml_renderer_productlocation'
                ));
                $this->addColumn('fnsku', array(
                    'header' => Mage::helper('catalog')->__('FNSKU'),
//                    'width' => '150px',
                    'index' => 'fnsku',
                    'editable' => true,
                    'edit_only' => true,
                    'filter' => false,
                    'renderer' => 'inventoryplus/adminhtml_renderer_fnsku'
                ));
                $this->addColumn('upc', array(
                    'header' => Mage::helper('catalog')->__('UPC'),
//                    'width' => '150px',
                    'index' => 'upc',
                    'editable' => true,
                    'edit_only' => true,
                    'filter' => false,
                    'renderer' => 'inventoryplus/adminhtml_renderer_upc'
                ));
            }
        }
        if ($this->_isAllWarehouse == true) {
            $this->addColumn('total_physical_qty', array(
                'header' => Mage::helper('catalog')->__('Total Phys. Qty'),
                'width' => '80px',
                'type' => 'number',
                'default' => 0,
                'index' => 'total_physical_qty',
                'filter_index' => "SUM(warehouse_product.total_qty)",
                'filter_condition_callback' => array($this, '_filterTotalPhysQtyCallback')
            ));
            $this->addColumn('total_available_qty', array(
                'header' => Mage::helper('catalog')->__('Total Avail. Qty'),
                'width' => '80px',
                'type' => 'number',
                'index' => 'total_available_qty',
                'filter_index' => 'SUM(warehouse_product.available_qty)',
                'filter_condition_callback' => array($this, '_filterTotalAvailQtyCallback'),
            ));

            $this->addColumn('warehouse_id', array(
                'header' => Mage::helper('catalog')->__('Warehouse') .' '. Mage::helper('catalog')->__('(Phys. / Avail. Qty)'),
                'type' => 'options',
                'sortable' => false,
                'filter' => false,
                'options' => Mage::helper('inventoryplus/warehouse')->getAllWarehouseName(),
                'renderer' => 'inventoryplus/adminhtml_stock_renderer_warehouse',
                'align' => 'left',
                'width' => '100px',
            ));
            $this->addColumn('product_location', array(
                'header' => Mage::helper('catalog')->__('Product Location'),
                'type' => 'options',
                'sortable' => false,
                'filter' => false,
                'options' => Mage::helper('inventoryplus/warehouse')->getAllWarehouseName(),
                'renderer' => 'inventoryplus/adminhtml_stock_renderer_productlocation',
                'align' => 'left'
            ));
            $this->addColumn('fnsku', array(
                'header' => Mage::helper('catalog')->__('FNSKU'),
                'type' => 'options',
                'sortable' => false,
                'filter' => false,
                'options' => Mage::helper('inventoryplus/warehouse')->getAllWarehouseName(),
                'renderer' => 'inventoryplus/adminhtml_stock_renderer_fnsku',
                'align' => 'left'
            ));
            $this->addColumn('upc', array(
                'header' => Mage::helper('catalog')->__('UPC'),
                'type' => 'options',
                'sortable' => false,
                'filter' => false,
                'options' => Mage::helper('inventoryplus/warehouse')->getAllWarehouseName(),
                'renderer' => 'inventoryplus/adminhtml_stock_renderer_upc',
                'align' => 'left'
            ));
        }

        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
            $this->addColumn('supplier_id', array(
                'header' => Mage::helper('catalog')->__('Supplier'),
                'index' => 'supplier_id',
                'type' => 'options',
                'options' => Mage::helper('inventorypurchasing/supplier')->getAllSupplierName(),
                'renderer' => 'inventoryplus/adminhtml_stock_renderer_supplier',
                'align' => 'left',
                'sortable' => false,
                'filter' => false,
            ));
        }
        $this->addExportType('*/*/exportCsv', Mage::helper('inventoryplus')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('inventoryplus')->__('XML'));
        return parent::_prepareColumns();
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/productsGrid', array(
                    '_current' => true
        ));
    }

    public function getRowUrl($row) {
        return false;
    }

    protected function _filterTotalPhysQtyCallback($collection, $column) {
        $filter = $column->getFilter()->getValue();
        if (isset($filter['from']) && $filter['from']) {
            $collection->getSelect()->having('SUM(warehouse_product.total_qty) >= ?', $filter['from']);
        }
        if (isset($filter['to']) && $filter['to']) {
            $collection->getSelect()->having('SUM(warehouse_product.total_qty) <= ?', $filter['to']);
        }
        $filterCollection = clone $collection;
        $filterCollection->clear();
        $filterCollection->setPageSize(false);
        $_stt = 0;
        foreach ($filterCollection as $col) {
            $_stt++;
        }
        $collection->setSize($_stt);
    }

    public function _filterTotalAvailQtyCallback($collection, $column) {
        $filter = $column->getFilter()->getValue();
        if (isset($filter['from']) && $filter['from']) {
            $collection->getSelect()->having('SUM(warehouse_product.available_qty) >= ?', $filter['from']);
        }
        if (isset($filter['to']) && $filter['to']) {
            $collection->getSelect()->having('SUM(warehouse_product.available_qty) <= ?', $filter['to']);
        }
        $filterCollection = clone $collection;
        $filterCollection->clear();
        $filterCollection->setPageSize(false);
        $_stt = 0;
        foreach ($filterCollection as $col) {
            $_stt++;
        }
        $collection->setSize($_stt);
    }

    public function _getDisabledProducts() {
        $warehouse = Mage::helper('inventoryplus/stock')->getWarehouse();
        $products = array();
        if (!$warehouse)
            return $products;
        $productCollection = Mage::getResourceModel('inventoryplus/warehouse_product_collection')
                ->addFieldToFilter('warehouse_id', $warehouse->getId());
        if (count($productCollection)) {
            foreach ($productCollection as $product) {
                if ($product->getTotalQty() > 0)
                    $products[$product->getProductId()] = array('total_qty' => $product->getQty());
            }
        }

        return array_keys($products);
    }

    public function _getSelectedProducts() {
        $productArrays = $this->getProducts();
        $products = '';
        $warehouseProducts = array();
        if ($productArrays) {
            $products = array();
            foreach ($productArrays as $productArray) {
                Mage::helper('inventoryplus')->parseStr(urldecode($productArray), $warehouseProducts);
                if (count($warehouseProducts)) {
                    foreach ($warehouseProducts as $pId => $enCoded) {
                        $products[] = $pId;
                    }
                }
            }
        }
        if (!is_array($products)) {
            $products = array_keys($this->getSelectedProducts());
        }
        return $products;
    }

    public function getSelectedProducts() {
        $warehouse = Mage::helper('inventoryplus/stock')->getWarehouse();
        $products = array();
        if (!$warehouse)
            return $products;
        $productCollection = Mage::getResourceModel('inventoryplus/warehouse_product_collection')
                ->addFieldToFilter('warehouse_id', $warehouse->getId());
        if (count($productCollection)) {
            foreach ($productCollection as $product) {
                $products[$product->getProductId()] = array('total_qty' => $product->getQty(), 'product_location' => $product->getProductLocation());
            }
        }
        return $products;
    }

    public function getWarehouse() {
        $adminId = Mage::getSingleton('admin/session')->getUser()->getId();
        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorywarehouse')) {
            $warehouseId = Mage::getModel('admin/session')->getData('stock_warehouse_id');
            if ($warehouseId) {
                if (Mage::helper('inventoryplus/warehouse')->canEdit($adminId, $warehouseId))
                    return Mage::getModel('inventoryplus/warehouse')
                                    ->load($warehouseId);
            }else {
                $allWarehouseEnable = Mage::helper('inventoryplus/warehouse')->getWarehouseEnable();
                if ($allWarehouseEnable) {
                    foreach ($allWarehouseEnable as $warehouseId) {
                        Mage::getModel('admin/session')->setData('stock_warehouse_id', $warehouseId);
                        return Mage::getModel('inventoryplus/warehouse')
                                        ->load($warehouseId);
                    }
                } else {
                    return false;
                }
            }
          
        } else {
            return Mage::getModel('inventoryplus/warehouse')->getCollection()->setPageSize(1)->setCurPage(1)->getFirstItem();
        }
        return false;
    }

}
