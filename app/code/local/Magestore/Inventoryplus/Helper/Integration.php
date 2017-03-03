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
 * Inventory Helper
 *
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Helper_Integration extends Mage_Core_Helper_Abstract
{
    public function isM2eProActive()
    {
        $result = false;
        if (Mage::helper('core')->isModuleEnabled('Ess_M2ePro') && Mage::helper('core')->isModuleEnabled('Magestore_Inventorym2epro')) {
            $result = true;
        }
        return $result;
    }
}