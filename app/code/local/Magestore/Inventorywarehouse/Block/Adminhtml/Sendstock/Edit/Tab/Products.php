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
 * Inventorywarehouse Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Edit_Tab_Products 
    extends Mage_Adminhtml_Block_Widget_Grid 
    implements Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('sendstockproductGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        if (Mage::getModel('admin/session')->getData('sendstock_product_import'))
            $this->setDefaultFilter(array('in_products' => 1));
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
        $warehouse = $this->getRequest()->getParam('source');
        $id = $this->getRequest()->getParam('id');
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        if (!$id) {
            $collection->joinField('warehouse_qty', 'inventoryplus/warehouse_product', 'total_qty', 'product_id=entity_id', "{{table}}.warehouse_id=$warehouse", 'inner');
            $collection->addFieldToFilter('warehouse_qty', array('gt' => 0));
        } else {
            $collection->joinField('qty', 'inventorywarehouse/sendstock_product', 'qty', 'product_id=entity_id', "{{table}}.warehouse_sendstock_id = $id", 'inner');
        }
        
        /* add scanned result to colelction */
		if(Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')){
            $currentAdminId = Mage::getSingleton('admin/session')->getUser()->getUserId();
			$collection->getSelect()->joinLeft(
						array(
							'scanitem' => $collection->getTable('inventorybarcode/barcode_scanitem')
						), 
						"e.entity_id = scanitem.product_id "
                        . " AND scanitem.user_id = '" . $currentAdminId . "'"
						. "AND scanitem.action = '".Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Scanbarcode::SCAN_ACTION."'", 
						array('scan_qty' => new Zend_Db_Expr('IFNULL(scanitem.scan_qty, 0)'),
							  'last_scanned_at' => 'last_scanned_at')
					);
			$collection->getSelect()->order('scanitem.last_scanned_at DESC');        
		}
		$collection->getSelect()->group('e.entity_id');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $this->addColumn('in_products', array(
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'use_index' => true,
            ));
        }
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'sortable' => true,
            'width' => '60',
            'index' => 'entity_id'
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

        $this->addColumn('product_image', array(
            'header' => Mage::helper('catalog')->__('Image'),
            'width' => '90px',
            'index' => 'product_image',
            'filter' => false,
            'renderer' => 'inventoryplus/adminhtml_renderer_productimage'
        ));

        $this->addColumn('product_location', array(
            'header' => Mage::helper('catalog')->__('Product Location'),
            'width' => '150px',
            'index' => 'product_location',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'inventorywarehouse/adminhtml_sendstock_renderer_proLocationSendstock'
        ));

        $editable = false;
        if (!$id) {
            $this->addColumn('warehouse_qty', array(
                'header' => Mage::helper('catalog')->__('Total Qty in Source Warehouse'),
                'width' => '80px',
                'index' => 'warehouse_qty',
                'type' => 'number'
            ));
            $editable = true;
        }
        if ($id) {
            $this->addColumn('qty', array(
                'header' => Mage::helper('catalog')->__('Qty. Sent'),
                'width' => '80px',
                'index' => 'qty',
                'editable' => false,
                'type' => 'number',
            ));
        } else {
            $this->addColumn('qty', array(
                'header' => Mage::helper('catalog')->__('Qty. Sent'),
                'width' => '80px',
                'index' => 'scan_qty',
                'editable' => $editable,
                'type' => 'number',
                'renderer' => 'inventoryplus/adminhtml_renderer_input',
                'filter' => false,
                'sortable' => false,
            ));
        }
        return parent::_prepareColumns();
    }

    public function _getSelectedProducts() {
        $products = $this->getProducts();
        if (!is_array($products)) {
            $products = array_keys($this->getSelectedProducts());
        }
        return $products;
    }

    public function getSelectedProducts() {
        $sendStock = $this->getSendstock();
        $products = array();
        $productCollection = Mage::getResourceModel('inventorywarehouse/sendstock_product_collection')
                ->addFieldToFilter('warehouse_sendstock_id', $sendStock->getId());
        if (count($productCollection)) {
            foreach ($productCollection as $product) {
                $products[$product->getProductId()] = array('qty' => $product->getQty());
            }
        }
        if ($sendStockProductImports = Mage::getModel('admin/session')->getData('sendstock_product_import')) {
            $productModel = Mage::getModel('catalog/product');
            $productSKU = array();
            $productQty = array();
            foreach ($sendStockProductImports as $productImport) {
                $productSKU[] = $productImport['SKU'];
                $productQty[$productImport['SKU']] = $productImport['QTY'];
//                $productId = $productModel->getIdBySku($productImport['SKU']);
//                if ($productId)
//                    $products[$productId] = array('qty' => floatval($productImport['QTY']));
            }
            $productCollection = Mage::getResourceModel('catalog/product_collection')
                ->addAttributeToSelect('sku')
                ->addAttributeToFilter('sku', array('in' => $productSKU));
            foreach ($productCollection as $product) {
                $products[$product->getId()] = array('qty' => floatval($productQty[$product->getSku()]));
            }
            return $products;
        }
        if(Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')){
			/* add scanned items to list */
			$scanItems = Mage::getModel('inventorybarcode/barcode_scanitem')
								->getItems(array(), Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Scanbarcode::SCAN_ACTION);
			if(count($scanItems)){
				foreach($scanItems as $scanItem) {
					if(!($this->getProducts())) {
						$products[$scanItem->getProductId()]['qty'] = floatval($scanItem->getScanQty());
					} else {
						if(isset($products[$scanItem->getProductId()]))
							$products[$scanItem->getProductId()]['qty'] = floatval($scanItem->getScanQty());
					}
				}
			}        
        }
        return $products;
    }

    public function getWarehouse() {
        return Mage::getModel('inventoryplus/warehouse')
                        ->load($this->getRequest()->getParam('warehouse_id'));
    }

    public function getSendstock() {
        return Mage::getModel('inventorywarehouse/sendstock')
                        ->load($this->getRequest()->getParam('id'));
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/productsGrid', array(
                    '_current' => true,
                    'id' => $this->getRequest()->getParam('id'),
                    'store' => $this->getRequest()->getParam('store')
        ));
    }

    public function getStore() {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    public function getRowUrl($row) {
        return false;
    }

    public function getRowClass($row) {
        return 'row-'. $row->getEntityId();
    }

}
