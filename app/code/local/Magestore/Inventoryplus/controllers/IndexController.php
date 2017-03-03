<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Magestore_Inventoryplus_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        $requeststock = Mage::getModel('inventorywarehouse/requeststock')->load(7);
        $requeststockProducts = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
            ->addFieldToFilter('warehouse_requeststock_id', 7);

        $complete = true;
        $processing = false;
        Zend_Debug::dump($requeststockProducts->getData());
        foreach ($requeststockProducts as $requeststockProduct){
            if($requeststockProduct->getQty() != $requeststockProduct->getTotalDelivery())
                $complete = false;
            if($requeststockProduct->getTotalDelivery() != 0)
                $processing = true;
        }

        if($processing)
            $requeststock->setStatus(4)->save();
        if ($complete)
            $requeststock->setStatus(1)->save();
        echo $requeststock->getStatus();
    }
}