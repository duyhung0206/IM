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
 * @category     Magestore
 * @package     Magestore_Inventory
 * @copyright     Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventory Adjust Stock Edit Form Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryphysicalstocktaking_Block_Adminhtml_Physicalstocktaking_Edit_Tab_Products extends Mage_Adminhtml_Block_Widget_Grid implements Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('productGrid');
        //$this->setDefaultSort('entity_id');
        //$this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        if (($this->getAdjustStock() && $this->getAdjustStock()->getId()) || Mage::getModel('admin/session')->getData('physicalstocktaking_product_import')) {
            $this->setDefaultFilter(array('in_products' => 1));
        }
    }

    protected function _addColumnFilterToCollection($column)
    {
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

    protected function _getAttributeTableAlias($attributeCode)
    {
        return 'at_' . $attributeCode;
    }

    protected function _prepareCollection()
    {
        $resource = Mage::getSingleton('core/resource');
        $currentAdmin = Mage::getSingleton('admin/session');
        $currentAdminId = $currentAdmin->getUser()->getUserId();
        if ($this->getRequest()->getParam('id')) {
            $id = $this->getRequest()->getParam('id');
            $adjuststock = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking')->load($id);
            $warehouse_id = $adjuststock->getWarehouseId();
            $productIds = array();

            $adjuststockProducts = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking_product')
                    ->getCollection()
                    ->addFieldToFilter('physicalstocktaking_id', $id);
            foreach ($adjuststockProducts as $adjuststockProduct)
                $productIds[] = $adjuststockProduct->getProductId();
            if ($adjuststock->getStatus() != 0) {
                $collection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter('entity_id', array('in' => $productIds));
                $tableStockAlias = $this->_getAttributeTableAlias('stock_qty');
                $tableWarehouseProductAlias = $this->_getAttributeTableAlias('warehouse_qty');
                if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
                    $collection->joinField('stock_qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left');
                }
                $collection->joinField('old_qty', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'old_qty', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
                $collection->joinField('adjust_qty', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'adjust_qty', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
                $collection->joinField('warehouse_qty', 'inventoryplus/warehouse_product', 'total_qty', 'product_id=entity_id', '{{table}}.warehouse_id=' . $warehouse_id, 'left');
                $collection->joinField('product_location', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'product_location', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
                $collection->getSelect()->columns(array('difference' => new Zend_Db_Expr("at_adjust_qty.adjust_qty - at_old_qty.old_qty")));
            } else {
                $collection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
                $collection->joinField('old_qty', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'old_qty', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
                $collection->joinField('adjust_qty', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'adjust_qty', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
                $collection->joinField('warehouse_qty', 'inventoryplus/warehouse_product', 'total_qty', 'product_id=entity_id', '{{table}}.warehouse_id=' . $warehouse_id, 'left');
                $collection->joinField('product_location', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'product_location', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
            }
            $collection->addFieldtoFilter('adjust_qty', array('notnull' => true));
        } else {
            if ($adjustStockProducts = Mage::getModel('admin/session')->getData('physicalstocktaking_product_warehouse')) {
                $warehouse_id = $adjustStockProducts['warehouse_id'];
                $categoryIds = isset($adjustStockProducts['categoryIds']) ? $adjustStockProducts['categoryIds'] : array();
            }
            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
            if (isset($categoryIds) & count($categoryIds)) {
                $collection = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking')->getCatalogProduct($categoryIds);
            }
            $collection->getSelect()->joinLeft(
                    array(
                'warehouse_product' => $resource->getTableName('inventoryplus/warehouse_product')
                    ), 'e.entity_id = warehouse_product.product_id AND warehouse_product.warehouse_id = ' . $warehouse_id, array('qty' => new Zend_Db_Expr('IFNULL(warehouse_product.total_qty, 0)'))
            );
            $collection->joinField('product_location', 'inventoryplus/warehouse_product', 'product_location', 'product_id=entity_id', '{{table}}.warehouse_id=' . $warehouse_id, 'left');
            
            /* add scanned result to colelction */
            if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')) {
                $currentAdminId = Mage::getSingleton('admin/session')->getUser()->getUserId();
                $collection->getSelect()->joinLeft(
                        array(
                    'scanitem' => $collection->getTable('inventorybarcode/barcode_scanitem')
                        ), "e.entity_id = scanitem.product_id "
                        . " AND scanitem.user_id = '" . $currentAdminId . "'"
                        . "AND scanitem.action = '" . Magestore_Inventoryphysicalstocktaking_Block_Adminhtml_Physicalstocktaking_Scanbarcode::SCAN_ACTION . "'", array('scan_qty' => new Zend_Db_Expr('IFNULL(scanitem.scan_qty, 0)'),
                    'last_scanned_at' => 'last_scanned_at')
                );
                $collection->getSelect()->order('scanitem.last_scanned_at DESC');
            }
        }
        
        $collection->getSelect()->group('entity_id');
        
        if ($storeId = $this->getRequest()->getParam('store', 0)) {
            $collection->addStoreFilter($storeId);
        }
        $this->setCollection($collection);
        $this->_sortCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $model = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking')->load($this->getRequest()->getParam('id'));
        $physicalPermission = Mage::helper('inventoryphysicalstocktaking')->getPhysicalWarehouseByAdmin();
        if ((!$this->getRequest()->getParam('id') 
                || $model->getStatus() == Magestore_Inventoryphysicalstocktaking_Model_Physicalstocktaking::STATUS_PENDING)) {
            $this->addColumn('in_products', array(
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'use_index' => true,
                'is_system' => true,
            ));
        }
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'sortable' => true,
            'width' => '60',
            'index' => 'entity_id',
            'is_system' => true,
        ));
        

        $this->addColumn('product_sku', array(
            'header' => Mage::helper('catalog')->__('SKU'),
            'width' => '80px',
            'index' => 'sku'
        ));
        
        $this->addColumn('product_name', array(
            'header' => Mage::helper('catalog')->__('Name'),
            'align' => 'left',
            'index' => 'name',
            'width' => '200px',
        ));

        $this->addColumn('category', array(
            'header' => Mage::helper('catalog')->__('Category'),
            'index' => 'category_name',
            'type' => 'options',
            'width' => '150px',
            'options' => $this->getCategoryOption(),
            'renderer' => 'inventoryphysicalstocktaking/adminhtml_physicalstocktaking_renderer_category',
            'filter_condition_callback' => array($this, 'filterCategory'),
            'is_system' => true,
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
                ->load()
                ->toOptionHash();
        $this->addColumn('set_name', array(
            'header' => Mage::helper('catalog')->__('Attrib. Set Name'),
            'width' => '100px',
            'index' => 'attribute_set_id',
            'type' => 'options',
            'options' => $sets,
            'is_system' => true,
        ));

        if (!$this->_isExport) {
            $this->addColumn('product_image', array(
                'header' => Mage::helper('catalog')->__('Image'),
                'width' => '90px',
                'filter' => false,
                'renderer' => 'inventoryplus/adminhtml_renderer_productimage',
                'is_system' => true,
            ));
        }
        /*
          $this->addColumn('product_status', array(
          'header' => Mage::helper('catalog')->__('Status'),
          'width' => '90px',
          'index' => 'status',
          'type' => 'options',
          'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
          ));
         */
        /*
          $this->addColumn('product_price', array(
          'header' => Mage::helper('catalog')->__('Price'),
          'type' => 'currency',
          'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
          'index' => 'price'
          ));
         */

        if ($this->getRequest()->getParam('id')) {
            if ($model->getStatus() == Magestore_Inventoryphysicalstocktaking_Model_Physicalstocktaking::STATUS_PENDING) {
                $this->addColumn('product_location', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Product Location'),
                    'width' => '150px',
                    'index' => 'product_location',
                    'editable' => true,
                    'edit_only' => true,
                    'filter' => false,
                    'renderer' => 'inventoryplus/adminhtml_renderer_productlocation',
                ));
                $this->addColumn('adjust_qty', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Counted Qty'),
                    'name' => 'adjust_qty',
                    'type' => 'number',
                    'index' => 'adjust_qty',
                    'editable' => true,
                    'edit_only' => true,
                    'filter' => false,
                    'width' => '150px',
                    'renderer' => 'inventoryplus/adminhtml_renderer_input',
                ));
            } else if ($model->getStatus() == Magestore_Inventoryphysicalstocktaking_Model_Physicalstocktaking::STATUS_CANCELED) {
                $this->addColumn('old_qty', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Qty in Warehouse'),
                    'name' => 'old_qty',
                    'type' => 'number',
                    'index' => 'old_qty',
                    'default' => '0',
                ));
            } else if ($model->getStatus() == Magestore_Inventoryphysicalstocktaking_Model_Physicalstocktaking::STATUS_COMPLETED){
                $this->addColumn('product_location', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Product Location'),
                    'width' => '150px',
                    'index' => 'product_location',
                    'name' => 'product_location',
                    'type' => 'text',
                    'align' => 'center',
                ));

                $this->addColumn('old_qty', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Qty in Warehouse'),
                    'name' => 'old_qty',
                    'type' => 'number',
                    'index' => 'old_qty',
                    'default' => '0',
                ));

                $this->addColumn('adjust_qty', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Counted Qty'),
                    'name' => 'adjust_qty',
                    'type' => 'number',
                    'index' => 'adjust_qty',
                    'default' => '0'
                ));

                $this->addColumn('difference', array(
                    'header' => Mage::helper('inventoryphysicalstocktaking')->__('Difference'),
                    'name' => 'difference',
                    'type' => 'number',
                    'index' => 'difference',
                    'default' => '0',
                    'width' => '50px',
                    'filter_condition_callback' => array($this, 'filterDifferenceQty')
                ));
            }
        } else {
            /*
            $this->addColumn('qty', array(
                'header' => Mage::helper('inventoryphysicalstocktaking')->__('Qty'),
                'name' => 'qty',
                'type' => 'number',
                'index' => 'qty'
            ));
            */
            $this->addColumn('product_location', array(
                'header' => Mage::helper('inventoryphysicalstocktaking')->__('Product Location'),
                'width' => '150px',
                'index' => 'product_location',
                'editable' => true,
                'edit_only' => true,
                'filter' => false,
                'renderer' => 'inventoryplus/adminhtml_renderer_productlocation',
            ));

            $this->addColumn('adjust_qty', array(
                'header' => Mage::helper('inventoryphysicalstocktaking')->__('Counted Qty'),
                'name' => 'adjust_qty',
                'type' => 'number',
                'index' => 'scan_qty',
                'editable' => true,
                'edit_only' => true,
                'filter' => false,
                'sortable' => false,
                'width' => '150px',
                'renderer' => 'inventoryplus/adminhtml_renderer_input',
            ));
        }
        
        if($stocktakeId = $this->getRequest()->getParam('id')) {
            $this->addExportType('*/*/exportProductCsv/id/'.$stocktakeId, Mage::helper('inventoryplus')->__('CSV'));
        }
        
    }
    
    public function filterDifferenceQty($collection, $column)
    {
        $filter = $column->getFilter()->getValue();
        if (isset($filter['from']) && $filter['from']) {
            $collection->getSelect()->where('at_adjust_qty.adjust_qty - at_old_qty.old_qty >= ?', $filter['from']);
        }
        if (isset($filter['to']) && $filter['to']) {
            $collection->getSelect()->where('at_adjust_qty.adjust_qty - at_old_qty.old_qty <= ?', $filter['to']);
        }
        return $this;
    }
    
    /**
     * Sort collection
     * 
     * @param collection $collection
     * @return collection
     */
    protected function _sortCollection($collection) {
        $sort = $this->getRequest()->getParam('sort', $this->_defaultSort);
        $dir = $this->getRequest()->getParam('dir', $this->_defaultDir);
        $this->setCollection($collection);
        $field = $sort;
        if ($field) {
            $collection->getSelect()->order("$field $dir");
        }
        return $collection;
    }    

    protected function getCategories()
    {
        $categoryCollection = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('is_active', 1);
        $categories = array();
        $categoryItem = array();

        foreach ($categoryCollection as $category) {
            $numItems = 0;
            $categoryId = $category->getId();
            $coll = $category->getResourceCollection();
            $pathIds = $category->getPathIds();
            array_shift($pathIds);
            array_shift($pathIds);
            $coll->addAttributeToSelect('*');
            $coll->addAttributeToFilter('entity_id', array('in' => $pathIds));
            $result = '';

            foreach ($coll as $value) {
                if ($value) {
                    $numItems +=1;
                }
            }
            $i = 0;
            foreach ($coll as $cat) {
                $result .= $cat->getName() . '_';
                if (++$i == $numItems) {
                    $result = substr($result, 0, -1);
                }
                if ($i == 1) {
                    $catParentName = $cat->getName();
                }
            }
            if ($result) {
                $categoryItem['category_parentname'] = $catParentName;
                $categoryItem['category_id'] = $categoryId;
                $categoryItem['category_name'] = $result;
                array_push($categories, $categoryItem);
            }
        }
        return $categories;
    }

    protected function getGroupCategory()
    {
        $groupCategory = array();
        $groupCategoryItem = array();
        $categories = $this->getCategories();
        $groups = array();
        foreach ($categories as $item) {
            $key = $item['category_parentname'];
            if (!isset($groups[$key])) {
                $groups[$key] = array(
                    'items' => array($item),
                );
            } else {
                $groups[$key]['items'][] = $item;
            }
        }
        foreach ($groups as $group) {
            foreach ($group as $groupItem) {
                foreach ($groupItem as $itemCat) {
                    $groupCategoryItem['category_name'] = $itemCat['category_name'];
                    $groupCategoryItem['category_id'] = $itemCat['category_id'];
                    array_push($groupCategory, $groupCategoryItem);
                }
            }
        }
        return $groupCategory;
    }

    protected function getCategoryOption()
    {
        $categoryOption = array();
        $groupCategory = $this->getGroupCategory();
        foreach ($groupCategory as $item) {
            array_push($categoryOption, $item['category_name']);
        }
        return $categoryOption;
    }

    public function filterCategory($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $groupCategory = $this->getGroupCategory();
        $numberGroupCategory = count($groupCategory);
        for ($i = 0; $i < $numberGroupCategory; $i++) {
            if ($i == $value) {
                $category_id = $groupCategory[$i]['category_id'];
            }
        }
        $collection->joinTable(
                'catalog/category_product', 'product_id=entity_id', array('category_id' => 'category_id'), null, 'left'
        );
        if (!is_null(@$value)) {
            $collection->addFieldToFilter('category_id', $category_id);
        }
        return $this;
    }

    protected function _productFilter($column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $orderitem = Mage::getModel('sales/order_item')->getCollection();
        $orderitem->addFieldToFilter('name', array('like' => '%' . $value . '%'));
        $ids = array();
        foreach ($orderitem as $item) {
            $ids[] = $item->getId();
        }

        $this->getCollection()->addFieldToFilter("id", array("in", $ids));
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/productGrid', array(
                    '_current' => true,
                    'id' => $this->getRequest()->getParam('id'),
                    'store' => $this->getRequest()->getParam('store')
        ));
    }

    protected function _getSelectedProducts()
    {
        $productArrays = $this->getProducts();
        $products = '';
        $adjustProducts = array();
        if ($productArrays) {
            $products = array();
            foreach ($productArrays as $productArray) {
                Mage::helper('inventoryplus')->parseStr(urldecode($productArray), $adjustProducts);
                if (!empty($adjustProducts)) {
                    foreach ($adjustProducts as $pId => $enCoded) {
                        $products[] = $pId;
                    }
                }
            }
        }
        if ((!is_array($products) || Mage::getModel('admin/session')->getData('physicalstocktaking_product_import'))) {
            $products = array_keys($this->getSelectedRelatedProducts());
        }

        return $products;
    }

    public function getProductId($sku)
    {
        $productModel = Mage::getModel('catalog/product')->getCollection();
        $productModel1 = $productModel->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('sku', $sku);
        foreach ($productModel1 as $item) {
            return $productId = $item->getId();
        }
    }

    public function getSelectedRelatedProducts()
    {
        $products = array();
        if ($adjustStockProducts = Mage::getModel('admin/session')->getData('physicalstocktaking_product_import')) {
            foreach ($adjustStockProducts as $adjustStockProduct) {
                $productId = $this->getProductId($adjustStockProduct['SKU']);
                if ($productId) {
                    $products[$productId] = array('adjust_qty' => $adjustStockProduct['QTY'],
                        'product_location' => $adjustStockProduct['LOCATION']);
                }
            }
        } else {
            $products = array();
            if ($this->getRequest()->getParam('id')) {
                $id = $this->getRequest()->getParam('id');
                $adjuststock = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking')->load($id);
                $warehouse_id = $adjuststock->getWarehouseId();
                $productIds = array();
                $adjuststockProducts = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking_product')
                        ->getCollection()
                        ->addFieldToFilter('physicalstocktaking_id', $id);
                foreach ($adjuststockProducts as $adjuststockProduct) {
                    $products[$adjuststockProduct->getProductId()] = array('adjust_qty' => $adjuststockProduct->getAdjustQty(), 'product_location' => $adjuststockProduct->getProductLocation());
                }
            } else {
                /* add scanned items to list */
                if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')) {
                    $scanItems = Mage::getModel('inventorybarcode/barcode_scanitem')
                            ->getItems(array(), Magestore_Inventoryphysicalstocktaking_Block_Adminhtml_Physicalstocktaking_Scanbarcode::SCAN_ACTION);
                    if (count($scanItems)) {
                        foreach ($scanItems as $scanItem) {
                            if (!($this->getProducts())) {
                                $products[$scanItem->getProductId()]['adjust_qty'] = floatval($scanItem->getScanQty());
                            } else {
                                if (isset($products[$scanItem->getProductId()]))
                                    $products[$scanItem->getProductId()]['adjust_qty'] = floatval($scanItem->getScanQty());
                            }
                        }
                    }
                }
            }
        }

        if ($adjustStockProducts = Mage::getModel('admin/session')->getData('physicalstocktaking_product_warehouse')) {
            $categoryIds = isset($adjustStockProducts['categoryIds']) ? $adjustStockProducts['categoryIds'] : array();
            if (count($categoryIds)) {
                $this->_prepareCollection();
                $productIds = $this->getCollection()->getAllIds();
                if (count($productIds)) {
                    foreach ($productIds as $productId) {
                        if (!isset($products[$productId])) {
                            $products[$productId]['adjust_qty'] = 0;
                        }
                    }
                }
            }
        }

        return $products;
    }

    public function getProductSelect()
    {
        if ($this->getRequest()->getParam('id')) {
            $id = $this->getRequest()->getParam('id');
            $adjuststock = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking')->load($id);
            $warehouse_id = $adjuststock->getWarehouseId();
            $productIds = array();
            $adjuststockProducts = Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking_product')
                    ->getCollection()
                    ->addFieldToFilter('physicalstocktaking_id', $id);

            foreach ($adjuststockProducts as $adjuststockProduct)
                $productIds[] = $adjuststockProduct->getProductId();


            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('entity_id', array('in' => $productIds));
            $collection->joinField('old_qty', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'old_qty', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
            $collection->joinField('adjust_qty', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'adjust_qty', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
            $collection->joinField('product_location', 'inventoryphysicalstocktaking/physicalstocktaking_product', 'product_location', 'product_id=entity_id', '{{table}}.physicalstocktaking_id=' . $id, 'left');
        } else {
            $productSkus = array();
            if ($adjustStockProducts = Mage::getModel('admin/session')->getData('physicalstocktaking_product_warehouse')) {
                $warehouse_id = $adjustStockProducts['warehouse_id'];
            }
            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('*');
            $collection->joinField('qty', 'inventoryplus/warehouse_product', 'total_qty', 'product_id=entity_id', '{{table}}.warehouse_id=' . $warehouse_id, 'left');
        }

        if ($storeId = $this->getRequest()->getParam('store', 0))
            $collection->addStoreFilter($storeId);
        $collection->addOrder('entity_id', 'ASC');
        return $collection;
    }

    /**
     * get Current Purchase Order
     *
     * @return Magestore_Inventory_Model_Adjuststock
     */
    public function getAdjustStock()
    {
        return Mage::getModel('inventoryphysicalstocktaking/physicalstocktaking')->load($this->getRequest()->getParam('id'));
    }

    /**
     * get currrent store
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    public function getRowClass($row)
    {
        return 'row-' . $row->getEntityId();
    }

}
