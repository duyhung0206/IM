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
 * Inventory Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventoryplus
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Adminhtml_Inp_WarehouseController extends Magestore_Inventoryplus_Controller_Action {

    /**
     * Menu Path
     * 
     * @var string 
     */
    protected $_menu_path = 'inventoryplus/warehouse';

    /**
     * Warehouse Permission
     * 
     * @var array
     */
    protected $_changePermissions = array();

    /**
     *
     * @var bool
     */
    protected $_isNewManager = false;

    /**
     * @var array
     */
    protected $_warehouseProductDeleteds = array();

    /**
     * @var array
     */
    protected $_changeProductQtys = array();

    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Inventory_Adminhtml_InventoryController
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->_menu_path)
                ->_addBreadcrumb(
                        Mage::helper('adminhtml')->__('Manage Warehouses'), Mage::helper('adminhtml')->__('Manage Warehouses')
        );
        return $this;
    }

    /**
     * index action
     */
    public function indexAction() {
        if (Mage::getModel('admin/session')->getData('warehouseaddmore_product_import'))
            Mage::getModel('admin/session')->setData('warehouseaddmore_product_import', null);
        if (Mage::getModel('admin/session')->getData('null_warehouseaddmore_product_import'))
            Mage::getModel('admin/session')->setData('null_warehouseaddmore_product_import', 0);

        $this->_title($this->__('Inventory'))
                ->_title($this->__('Manage Warehouses'));

        if (!Mage::helper('core')->isModuleEnabled('Magestore_Inventorywarehouse')) {
            $warehouse = Mage::getModel('inventoryplus/warehouse')->getCollection()
                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
            if ($warehouse->getId()) {
                $this->_redirect('*/*/edit', array('id' => $warehouse->getId()));
                return;
            }
        }

        $this->_initAction()
                ->renderLayout();
    }

    /**
     * view and edit item action
     */
    public function editAction() {
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Manage Warehouses'));

        $warehouseId = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventoryplus/warehouse')->load($warehouseId);

        if ($model->getId() || $warehouseId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('warehouse_data', $model);

            $this->loadLayout()->_setActiveMenu($this->_menu_path);

            $this->_addBreadcrumb(
                    Mage::helper('adminhtml')->__('Manage Warehouses'), Mage::helper('adminhtml')->__('Manage Warehouses')
            );
            $this->_addBreadcrumb(
                    Mage::helper('adminhtml')->__('Warehouse'), Mage::helper('adminhtml')->__('Warehouse')
            );

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
                    ->removeItem('js', 'mage/adminhtml/grid.js')
                    ->addItem('js', 'magestore/adminhtml/inventory/grid.js');
            $this->_addContent($this->getLayout()->createBlock('inventoryplus/adminhtml_warehouse_edit'))
                    ->_addLeft($this->getLayout()->createBlock('inventoryplus/adminhtml_warehouse_edit_tabs', 'warehouse_edit_tabs'));
            Mage::dispatchEvent('warehouse_controller_index', array('warehouse_controler' => $this));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Warehouse does not exist!')
            );
            $this->_redirect('*/*/');
        }
    }

    public function newAction() {
        $this->_forward('edit');
    }

    protected function _savePOSUser($data) {
        if (Mage::helper('inventoryplus')->isWebPOS20Active()) {
            $data['selected_user'] = isset($data['selected_user']) ? $data['selected_user'] : '';
            if (isset($data['selected_user'])) {
                $data['selected_user'] = explode('&', str_replace('&on', '', $data['selected_user']));
                $existingWebposUserIds = array();
                $uncheckedWebposUserIds = array();
                $existingWebposUsersInWarehouse = Mage::getModel('inventorywebpos/webposuser')->getCollection()
                        ->addFieldToFilter('warehouse_id', array('eq' => $this->getRequest()->getParam('id')));
                foreach ($existingWebposUsersInWarehouse as $user) {
                    array_push($existingWebposUserIds, $user->getUserId());
                }
                $checkedWebposUserIds = $data['selected_user'];

                $allWebposUsers = Mage::getModel('webpos/user')->getCollection();
                foreach ($allWebposUsers as $allWebposUser) {
                    if (!in_array($allWebposUser->getUserId(), $data['selected_user'])) {
                        array_push($uncheckedWebposUserIds, $allWebposUser->getUserId());
                    }
                }

                foreach ($uncheckedWebposUserIds as $uncheckedWebposUserId) {
                    $existingRecord = Mage::getModel('inventorywebpos/webposuser')->getCollection()
                                    ->addFieldToFilter('user_id', $uncheckedWebposUserId)
                                    ->addFieldToFilter('warehouse_id', $this->getRequest()->getParam('id'))
                                    ->setPageSize(1)->setCurPage(1)->getFirstItem();
                    if ($existingRecord->getId()) {
                        try {
                            $existingRecord->delete();
                        } catch (Exception $e) {
                            Mage::log($e->getMessage(), null, 'inventory_management.log');
                        }
                    }
                }
                if ($checkedWebposUserIds) {
                    foreach ($checkedWebposUserIds as $checkedWebposUserId) {
                        if (!$checkedWebposUserId)
                            continue;
                        $webposUser = Mage::getModel('inventorywebpos/webposuser')->load($checkedWebposUserId, 'user_id');
                        if ($webposUser->getId()) {
                            $webposUser->setWarehouseId($this->getRequest()->getParam('id'));
                        } else {
                            $webposUser = Mage::getModel('inventorywebpos/webposuser');
                            $webposUser->setWarehouseId($this->getRequest()->getParam('id'));
                            $webposUser->setUserId($checkedWebposUserId);
                            $webposUser->setCanCreateShipment(1);
                        }
                        $webposUser->save();
                    }
                }
            }
        }

        Mage::dispatchEvent('save_xpos_user_in_warehouse', array('xpos_data' => $data, 'warehouse_id' => $this->getRequest()->getParam('id')));
    }

    protected function _checkRootWarehouse($data) {
        if (isset($data['is_root']) && $data['is_root']) {
            $current_root = Mage::getModel('inventoryplus/warehouse')->getCollection()
                            ->addFieldToFilter('is_root', '1')
                            ->addFieldToFilter('warehouse_id', array('neq' => $this->getRequest()->getParam('id')))
                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
            try {
                if ($current_root->getId()) {
                    $current_root->setData('is_root', 0)->save();
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'inventory_management.log');
            }
        }
    }

    protected function _preparePermissionData($model, &$data, $oldManager, $manager) {
        $admin = Mage::getSingleton('admin/session')->getUser();
        //save permission
        $edits = array();
        if (isset($data['edit']) && is_array($data['edit'])) {
            $edits = $data['edit'];
        }

        $adjusts = array();
        if (isset($data['adjust']) && is_array($data['adjust'])) {
            $adjusts = $data['adjust'];
        }

        $managerId = Mage::getModel('admin/user')->loadByUsername($manager)->getUserId();
        $oldManagerId = Mage::getModel('admin/user')->loadByUsername($oldManager)->getUserId();
        $owner = $model->getCreatedBy();
        $ownerId = Mage::getModel('admin/user')->loadByUsername($owner)->getUserId();
        $this->_isNewManager = false;
        //remove old manager permission
        $oldassignment = Mage::getModel('inventoryplus/warehouse_permission')->loadByWarehouseAndAdmin($model->getId(), $oldManagerId);
        $newassignment = Mage::getModel('inventoryplus/warehouse_permission')->loadByWarehouseAndAdmin($model->getId(), $managerId);
        $ownerassignment = Mage::getModel('inventoryplus/warehouse_permission')->loadByWarehouseAndAdmin($model->getId(), $ownerId);

        //check old manager and new manager
        $isDiffMng = false;
        if ($managerId && $managerId != $oldManagerId) {
            $isDiffMng = true;
        }
        $isNewMng = false;
        if (!$oldManagerId || !isset($oldManagerId)) {
            $isNewMng = true;
        }

        //add permission for warehouse owner
        $ownerassignment->setCanEditWarehouse(1)
                ->setCanAdjust(1)
                ->setCanPhysical(1)
                ->setCanSendRequestStock(1)
                ->setCanPurchaseProduct(1)
                ->setAdminId($ownerId)
                ->setWarehouseId($model->getId())
                ->setId($ownerassignment->getId())
                ->save()
        ;

        // add permission for warehouse manager
        if ((($oldassignment->getId() && $isDiffMng) || $isNewMng)) {

            if ($oldManagerId && $oldManagerId != $ownerId) {
                $oldassignment->delete();
            }
            //save data for new manager
            if ($managerId && $managerId != $ownerId) {
                $newassignment->setCanEditWarehouse(1)
                        ->setCanAdjust(1)
                        ->setCanPhysical(1)
                        ->setCanSendRequestStock(1)
                        ->setCanPurchaseProduct(1)
                        ->setAdminId($managerId)
                        ->setWarehouseId($model->getId())
                        ->save()
                ;
                $this->_isNewManager = true;
            }
        }

        return array('manager' => $managerId, 'owner' => $ownerId, 'adjust_ids' => $adjusts);
    }

    protected function _savePermission($model, &$data, $oldManager, $manager) {
        if (!isset($manager) || !$manager) {
            return;
        }
        $admin = Mage::getSingleton('admin/session')->getUser();

        /* prepare permission data */
        $ownership = $this->_preparePermissionData($model, $data, $oldManager, $manager);
        $managerId = $ownership['manager'];
        $ownerId = $ownership['owner'];
        $adjusts = $ownership['adjust_ids'];

        $edits = array();
        if (isset($data['edit']) && is_array($data['edit'])) {
            $edits = $data['edit'];
        }
        if (in_array($admin->getId(), array($managerId, $ownerId))) {
            $admins = Mage::getModel('admin/user')->getCollection()
                    ->addFieldToFilter('user_id', array('nin' => array($ownerId, $managerId)))
                    ->getAllIds();
            foreach ($admins as $adminId) {
                $assignment = Mage::getModel('inventoryplus/warehouse_permission')->loadByWarehouseAndAdmin($model->getId(), $adminId);
                if ($assignment->getId()) {
                    $oldEditWarehouse = $assignment->getCanEditWarehouse();
                    $oldAdjust = $assignment->getCanAdjust();
                }
                $assignment->setWarehouseId($model->getId());
                $assignment->setAdminId($adminId);

                if (in_array($adminId, $edits)) {
                    if ($assignment->getId()) {
                        if ($oldEditWarehouse != 1) {
                            $this->_changePermissions[$adminId]['old_edit'] = Mage::helper('inventoryplus')->__('Cannot edit Warehouse');
                            $this->_changePermissions[$adminId]['new_edit'] = Mage::helper('inventoryplus')->__('Can edit Warehouse');
                        }
                    } else {
                        $this->_changePermissions[$adminId]['old_edit'] = '';
                        $this->_changePermissions[$adminId]['new_edit'] = Mage::helper('inventoryplus')->__('Can edit Warehouse');
                    }
                    $assignment->setData('can_edit_warehouse', 1);
                } else {
                    if ($assignment->getId()) {
                        if ($oldEditWarehouse != 0) {
                            $this->_changePermissions[$adminId]['old_edit'] = Mage::helper('inventoryplus')->__('Can edit Warehouse');
                            $this->_changePermissions[$adminId]['new_edit'] = Mage::helper('inventoryplus')->__('Cannot edit Warehouse');
                        }
                    } else {
                        $this->_changePermissions[$adminId]['old_edit'] = '';
                        $this->_changePermissions[$adminId]['new_edit'] = Mage::helper('inventoryplus')->__('Cannot edit Warehouse');
                    }
                    $assignment->setData('can_edit_warehouse', 0);
                }

                if (in_array($adminId, $adjusts)) {
                    if ($assignment->getId()) {
                        if ($oldAdjust != 1) {
                            $this->_changePermissions[$adminId]['old_adjust'] = Mage::helper('inventoryplus')->__('Cannot adjust Warehouse');
                            $this->_changePermissions[$adminId]['new_adjust'] = Mage::helper('inventoryplus')->__('Can adjust Warehouse');
                        }
                    } else {
                        $this->_changePermissions[$adminId]['old_adjust'] = '';
                        $this->_changePermissions[$adminId]['new_adjust'] = Mage::helper('inventoryplus')->__('Can adjust Warehouse');
                    }
                    $assignment->setData('can_adjust', 1);
                } else {
                    if ($assignment->getId()) {
                        if ($oldAdjust != 0) {
                            $this->_changePermissions[$adminId]['old_adjust'] = Mage::helper('inventoryplus')->__('Can adjust Warehouse');
                            $this->_changePermissions[$adminId]['new_adjust'] = Mage::helper('inventoryplus')->__('Cannot adjust Warehouse');
                        }
                    } else {
                        $this->_changePermissions[$adminId]['old_adjust'] = '';
                        $this->_changePermissions[$adminId]['new_adjust'] = Mage::helper('inventoryplus')->__('Cannot adjust Warehouse');
                    }
                    $assignment->setData('can_adjust', 0);
                }

                Mage::dispatchEvent('inventory_adminhtml_add_more_permission', array('permission' => $assignment, 'data' => $data, 'admin_id' => $adminId, 'change_permssions' => $this->_changePermissions));

                try {
                    if ($assignment)
                        $assignment->save();
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'inventory_management.log');
                }
            }
        }
        //save permission to session
        Mage::helper('inventoryplus')->saveSessionPermission();
    }

    protected function _saveHistory($model, $changeData, $changeArray, $oldManager, $manager) {
        $admin = Mage::getModel('admin/session')->getUser()->getUsername();
        if (!$this->getRequest()->getParam('id')) {
            $warehouseHistory = Mage::getModel('inventoryplus/warehouse_history');
            $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
            $warehouseHistory->setData('warehouse_id', $model->getId())
                    ->setData('time_stamp', now())
                    ->setData('create_by', $admin)
                    ->save();
            $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistory->getId())
                    ->setData('field_name', Mage::helper('inventoryplus')->__('%s created this warehouse.', $admin))
                    ->save();
            return;
        }

        if ($changeData == 1 || $this->_warehouseProductDeleteds || count($this->_changeProductQtys) || count($this->_changePermissions)) {
            $warehouseHistory = Mage::getModel('inventoryplus/warehouse_history');
            $warehouseHistory->setData('warehouse_id', $model->getId())
                    ->setData('time_stamp', now())
                    ->setData('create_by', $admin)
                    ->save();
            $warehouseHistoryId = $warehouseHistory->getId();

            if ($this->_isNewManager) {
                $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                        ->setData('field_name', Mage::helper('inventoryplus')->__('Set %s to be this warehouse manager', $manager))
                        ->setData('old_value', 'Old manager: ' . $oldManager)
                        ->setData('new_value', 'New manager: ' . $manager)
                        ->save();
            }

            if (count($this->_changePermissions)) {
                foreach ($this->_changePermissions as $key => $value) {
                    $admin = Mage::getModel('admin/user')->load($key)->getUsername();
                    $newValue = '';
                    $oldValue = '';
                    if (isset($value['new_edit']))
                        $newValue .= '| ' . $value['new_edit'] . ' |';
                    if (isset($value['new_adjust']))
                        $newValue .= '| ' . $value['new_adjust'] . ' |';
                    if (isset($value['new_purchase']))
                        $newValue .= '| ' . $value['new_purchase'] . ' |';
                    if (isset($value['old_edit']))
                        $oldValue .= '| ' . $value['old_edit'] . ' |';
                    if (isset($value['old_adjust']))
                        $oldValue .= '| ' . $value['old_adjust'] . ' |';
                    if (isset($value['old_purchase']))
                        $newValue .= '| ' . $value['old_purchase'] . ' |';
                    $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                    $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                            ->setData('field_name', Mage::helper('inventoryplus')->__('Changed permission of %s for this warehouse.', $admin))
                            ->setData('old_value', $oldValue)
                            ->setData('new_value', $newValue)
                            ->save();
                }
            }
            if (count($this->_changeProductQtys)) {
                foreach ($this->_changeProductQtys as $key => $value) {
                    $productSku = Mage::helper('inventoryplus/warehouse')->getProductSkuByProductId($key);
                    $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                    $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                            ->setData('field_name', Mage::helper('inventoryplus')->__('%s changed quantity of product(s) with the following SKU(s): %s.', $admin, $productSku))
                            ->setData('old_value', $value['old_qty'])
                            ->setData('new_value', $value['new_qty'])
                            ->save();
                }
            }
            if ($this->_warehouseProductDeleteds) {
                $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                        ->setData('field_name', Mage::helper('inventoryplus')->__('%s removed product(s) from this warehouse.', $admin))
                        ->setData('new_value', Mage::helper('inventoryplus')->__('%s removed product(s) with the following SKU(s): %s', $admin, $this->_warehouseProductDeleteds))
                        ->save();
            }

            $this->_saveChangedField($warehouseHistoryId, $changeData, $changeArray);
        }
    }

    protected function _saveChangedField($warehouseHistoryId, $changeData, $changeArray) {
        if ($changeData == 1) {
            foreach ($changeArray as $field => $filedValue) {
                $fileTitle = $this->getTitleByField($field);
                if ($field == 'status') {

                    $statusArray = Mage::getSingleton('inventoryplus/status')->getOptionHash();

                    if (isset($statusArray[$filedValue['old']]))
                        $filedValue['old'] = $statusArray[$filedValue['old']]['value'];
                    else
                        $filedValue['old'] = $statusArray[0]['value'];

                    if (isset($statusArray[$filedValue['new']]))
                        $filedValue['new'] = $statusArray[$filedValue['new']]['value'];
                    else
                        $filedValue['new'] = $statusArray[0]['value'];
                } elseif ($field == 'country_id') {
                    $countryArray = array();
                    $countryArrays = Mage::helper('inventoryplus/warehouse')->getCountryListHash();
                    foreach ($countryArrays as $country) {
                        $countryArray[$country['value']] = $country['label'];
                    }
                    $filedValue['old'] = '';
                    $filedValue['new'] = '';
                    if (isset($countryArray[$filedValue['old']]))
                        $filedValue['old'] = $countryArray[$filedValue['old']];
                    if (isset($countryArray[$filedValue['new']]))
                        $filedValue['new'] = $countryArray[$filedValue['new']];
                } elseif ($field == 'state_id') {
                    $oldRegion = Mage::getModel('directory/region')->load($filedValue['old']);
                    $oldRegionName = $oldRegion->getName();
                    $newRegion = Mage::getModel('directory/region')->load($filedValue['new']);
                    $newRegionName = $newRegion->getName();
                    $filedValue['old'] = $oldRegionName;
                    $filedValue['new'] = $newRegionName;
                }
                $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                        ->setData('field_name', $fileTitle)
                        ->setData('old_value', $filedValue['old'])
                        ->setData('new_value', $filedValue['new'])
                        ->save();
            }
        }
    }

    protected function _updateCatalogProduct($pId, $new_qty) {
        $stock_item = Mage::getModel('cataloginventory/stock_item')
                        ->getCollection()
                        ->addFieldToFilter('product_id', $pId)
                        ->setPageSize(1)->setCurPage(1)->getFirstItem();
        $stock_item_qty = $stock_item->getQty();

        $manageStock = $stock_item->getManageStock();
        if ($stock_item->getUseConfigManageStock()) {
            $manageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock', Mage::app()->getStore()->getStoreId());
        }
        if ($manageStock) {
            $backorders = $stock_item->getBackorders();
            $useConfigBackorders = $stock_item->getUseConfigBackorders();
            if ($useConfigBackorders) {
                $backorders = Mage::getStoreConfig('cataloginventory/item_options/backorders', Mage::app()->getStore()->getStoreId());
            }
            $stock_item->setQty($new_qty);
            $minToChangeStatus = Mage::getStoreConfig('cataloginventory/item_options/min_qty');
            if ($new_qty > $minToChangeStatus) {
                $stock_item->setData('is_in_stock', 1);
            } else if (!$backorders) {
                $stock_item->setData('is_in_stock', 0);
            }
            $stock_item->save();
        }
    }

    protected function _saveWarehouseProducts($model, &$data) {
        if (!isset($data['warehouse_products'])) {
            return;
        }
        $sqlNews = array();
        $sqlOlds = '';
        $countSqlOlds = 0;
        $warehouseProducts = array();
        $warehouseProductsExplodes = explode('&', urldecode($data['warehouse_products']));
        if (count($warehouseProductsExplodes) <= 900) {
            Mage::helper('inventoryplus')->parseStr(urldecode($data['warehouse_products']), $warehouseProducts);
        } else {
            foreach ($warehouseProductsExplodes as $warehouseProductsExplode) {
                $warehouseProduct = '';
                Mage::helper('inventoryplus')->parseStr($warehouseProductsExplode, $warehouseProduct);
                $warehouseProducts = $warehouseProducts + $warehouseProduct;
            }
        }

        if (!count($warehouseProducts)) {
            return;
        }

        $deletes = array_keys($warehouseProducts);
        $this->_warehouseProductDeleteds = Mage::helper('inventoryplus/warehouse')->deleteWarehouseProducts($model, $deletes);

        $productIds = array();

        foreach ($warehouseProducts as $pId => $enCoded) {
            $codeArr = array();
            Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);

            $warehouseProductsItem = Mage::getModel('inventoryplus/warehouse_product')
                            ->getCollection()
                            ->addFieldToFilter('warehouse_id', $model->getId())
                            ->addFieldToFilter('product_id', $pId)
                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
            if ($warehouseProductsItem->getId()) {
                $countSqlOlds++;
                if (isset($codeArr['total_qty']) && $codeArr['total_qty'] == $warehouseProductsItem->getTotalQty()) {
                    if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsItem->getProductLocation())
                        continue;
                    else {
                        $warehouseProductsItem->setWarehouseId($model->getId())
                                ->setProductLocation($codeArr['product_location'])
                                ->save();
                        continue;
                    }
                }
                if (isset($codeArr['total_qty']) && !is_numeric($codeArr['total_qty'])) {
                    if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsItem->getProductLocation())
                        continue;
                    else {
                        $warehouseProductsItem->setWarehouseId($model->getId())
                                ->setProductLocation($codeArr['product_location'])
                                ->save();
                        continue;
                    }
                }
                $current_warehouse_qty = $warehouseProductsItem->getTotalQty();
                $this->_changeProductQtys[$pId]['old_qty'] = $current_warehouse_qty;
                $this->_changeProductQtys[$pId]['new_qty'] = $codeArr['total_qty'];
                $oldQtyAvailable = $warehouseProductsItem->getAvailableQty();
                $newQtyAvailable = $oldQtyAvailable + ($codeArr['total_qty'] - $warehouseProductsItem->getTotalQty());
                $warehouseProductsItem
                        ->setWarehouseId($model->getId())
                        ->setTotalQty($codeArr['total_qty'])
                        ->setAvailableQty($newQtyAvailable)
                        ->setProductLocation($codeArr['product_location'])
                        ->save();

                /* update qty of product in Catalog */
                $new_qty = (int) $stock_item_qty + (int) $codeArr['total_qty'] - $current_warehouse_qty;
                $this->_updateCatalogProduct($pId, $new_qty);
            } else {
                $warehouseProductsNew = Mage::getModel('inventoryplus/warehouse_product');
                $countSqlOlds++;
                if ($codeArr['total_qty'] == '')
                    $codeArr['total_qty'] = 0;
                if (isset($codeArr['total_qty']) && !is_numeric($codeArr['total_qty'])) {
                    if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsNew->getProductLocation())
                        continue;
                    else {
                        $warehouseProductsNew->setWarehouseId($model->getId())
                                ->setProductLocation($codeArr['product_location'])
                                ->save();
                        continue;
                    }
                }
                $warehouseProductsNew->setWarehouseId($model->getId())
                        ->setProductId($pId)
                        ->setTotalQty($codeArr['total_qty'])
                        ->setAvailableQty($codeArr['total_qty'])
                        ->setProductLocation($codeArr['product_location'])
                        ->save();
                /* update qty of product in Catalog */
                $new_qty = (int) $stock_item_qty + (int) $codeArr['total_qty'];
                $this->_updateCatalogProduct($pId, $new_qty);
            }
            $productIds[] = $pId;
        }
    }

    /**
     * save item action
     */
    public function saveAction() {
        $admin = Mage::getSingleton('admin/session')->getUser();
        if ($data = $this->getRequest()->getPost()) {
            /* save POS users */
            $this->_savePOSUser($data);
            if (isset($data['manager']) && $data['manager']) {
                $manager = $data['manager'];
                $managerEmail = Mage::getModel('admin/user')->loadByUsername($manager)->getEmail();
                $managerName = Mage::getModel('admin/user')->loadByUsername($manager)->getFirstname() . ' ' . Mage::getModel('admin/user')->loadByUsername($manager)->getLastname();
                $data['manager_email'] = $managerEmail;
                $data['manager_name'] = $managerName;
            }
            //remove root warehouse if this warehouse is root
            $this->_checkRootWarehouse($data);

            $model = Mage::getModel('inventoryplus/warehouse')->load($this->getRequest()->getParam('id'));
            //get current manager
            $oldManager = $model->getManager();

            //save warehouse data
            $model->addData($data);

            //check field changed
            if ($this->getRequest()->getParam('id')) {
                $oldData = Mage::getModel('inventoryplus/warehouse')->load($this->getRequest()->getParam('id'));
                $changeArray = array();
                $changeData = 0;
                foreach ($data as $key => $value) {
                    if (!in_array($key, $this->getFiledSaveHistory()))
                        continue;
                    if ($oldData->getData($key) != $value) {
                        $changeArray[$key]['old'] = $oldData->getData($key);
                        $changeArray[$key]['new'] = $value;
                        $changeData = 1;
                    }
                }
            }
            if (!$this->getRequest()->getParam('id')) {
                $model->setCreatedBy($admin->getUsername());
            } elseif (!$model->getCreatedBy()) {
                $model->setCreatedBy($admin->getUsername());
            }
            try {
                /* save warehouse data */
                $model->setUpdatedBy($admin->getUserName());
                $model->save();

                /* save products */
                $this->_saveWarehouseProducts($model, $data);

                /* add new product */
                Mage::dispatchEvent('inventory_adminhtml_add_new_product', array('data' => $data, 'warehouse' => $model));

                /* save Warehouse permission */
                $this->_savePermission($model, $data, $oldManager, $manager);

                /* save history change */
                $this->_saveHistory($model, $changeData, $changeArray, $oldManager, $manager);

                /* prepare message */
                $this->_prepareMessages();

                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect('*/*/edit', array('id' => $model->getId()));
                }
                return $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                return $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventoryplus')->__('Unable to find warehouse to save!')
        );
        return $this->_redirect('*/*/');
    }

    protected function _prepareMessages() {
        if (!$this->getRequest()->getParam('id')) {
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('inventoryplus')->__('The warehouse is empty. You can add products by adjusting stock or requesting stock for sending stock to this warehouse.'));
            Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('inventoryplus')->__('The warehouse has been created.')
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('inventoryplus')->__('The warehouse has been saved.')
            );
        }
    }

    /**
     * delete item action
     */
    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('inventoryplus/warehouse');
                $canDelete = Mage::helper('inventoryplus/warehouse')->canDelete($this->getRequest()->getParam('id'));
                if ($canDelete) {
                    $model->setId($this->getRequest()->getParam('id'))
                            ->delete();

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Warehouse was successfully deleted'));
                    $this->_redirect('*/*/');
                } else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Can\'t delete warehouse because it still contains some products.'));
                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * mass delete item(s) action
     */
    public function massDeleteAction() {
        $inventoryIds = $this->getRequest()->getParam('inventoryplus');
        if (!is_array($inventoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($inventoryIds as $inventoryId) {
                    Mage::getModel('inventoryplus/inventory')->setId($inventoryId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($inventoryIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass change status for item(s) action
     */
    public function massStatusAction() {
        $inventoryIds = $this->getRequest()->getParam('inventoryplus');
        if (!is_array($inventoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($inventoryIds as $inventoryId) {
                    Mage::getSingleton('inventoryplus/inventory')
                            ->load($inventoryId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($inventoryIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * export grid item to CSV type
     */
    public function exportCsvAction() {
        $fileName = 'warehouse.csv';
        $content = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_warehouse_grid')
                ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportWarehouseProductCsvAction() {

        $fileName = 'warehouse_product.csv';
        $content = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_warehouse_edit_tab_products')
                ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction() {
        $fileName = 'warehouse.xml';
        $content = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_warehouse_grid')
                ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportWarehouseProductXmlAction() {
        $fileName = 'warehouse_product.xml';
        $content = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_warehouse_edit_tab_products')
                ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    //warehouse products
    public function productsAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('warehouse.edit.tab.products')
                ->setProducts($this->getRequest()->getPost('warehouse_products', null));
        $this->renderLayout();
    }

    public function productsGridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('warehouse.edit.tab.products')
                ->setProducts($this->getRequest()->getPost('warehouse_products', null));
        $this->renderLayout();
    }

    //warehouse permission
    public function permissionAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('warehouse.edit.tab.permission')
                ->setAssignments($this->getRequest()->getPost('rassignments', null));
        $this->renderLayout();
    }

    public function permissionGridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('warehouse.edit.tab.permission')
                ->setAssignments($this->getRequest()->getPost('rassignments', null));
        $this->renderLayout();
    }

    //warehouse change history
    public function historyAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function historyGridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    //show history
    public function showhistoryAction() {
        $form_html = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_warehouse_history')
                ->setTemplate('inventoryplus/warehouse/showhistory.phtml')
                ->toHtml();
        $this->getResponse()->setBody($form_html);
    }

    public function getFiledSaveHistory() {
        return array('name', 'manager_name', 'manager_email', 'telephone', 'street', 'city', 'country_id', 'state', 'stateEl', 'state_id', 'postcode', 'status');
    }

    public function getTitleByField($field) {
        $fieldArray = array(
            'name' => Mage::helper('inventoryplus')->__('Warehouse Name'),
            'manager_name' => Mage::helper('inventoryplus')->__('Manager\'s Name'),
            'manager_email' => Mage::helper('inventoryplus')->__('Manager\'s Email'),
            'telephone' => Mage::helper('inventoryplus')->__('Telephone'),
            'street' => Mage::helper('inventoryplus')->__('Street'),
            'city' => Mage::helper('inventoryplus')->__('City'),
            'country_id' => Mage::helper('inventoryplus')->__('Country'),
            'stateEl' => Mage::helper('inventoryplus')->__('State/Province'),
            'state' => Mage::helper('inventoryplus')->__('State/Province'),
            'state_id' => Mage::helper('inventoryplus')->__('State/Province'),
            'postcode' => Mage::helper('inventoryplus')->__('Zip/Postal Code'),
            'status' => Mage::helper('inventoryplus')->__('Status')
        );
        if (!$fieldArray[$field])
            return $field;
        return $fieldArray[$field];
    }

    public function gridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('inventory_warehouse_grid');
        $this->renderLayout();
    }

}
