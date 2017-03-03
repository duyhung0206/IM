<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Magestore_Inventoryplus_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $resource = Mage::getResourceModel('inventoryplus/warehouse_product');
        $result = $resource->getCatalogQty(905);
        var_dump($result);
    }
}