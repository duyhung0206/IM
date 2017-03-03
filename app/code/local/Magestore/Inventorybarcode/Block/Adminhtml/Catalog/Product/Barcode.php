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
 * @package     Magestore_Inventoryfulfillment
 * @copyright   Copyright (c) 2015 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

class Magestore_Inventorybarcode_Block_Adminhtml_Catalog_Product_Barcode
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Set the template for the block
     *
     */
    protected function _prepareLayout() {
        parent::_prepareLayout();
        $this->setTemplate('inventorybarcode/catalog/product/barcode.phtml');
    }

    /**
     * Add barcode grid to product edit
     */
    protected function _beforeToHtml() {
        $barcodeGrid = $this->getLayout()
            ->createBlock('inventorybarcode/adminhtml_catalog_product_barcode_grid', 'product_barcode_grid');
        $this->setChild('product_barcode_grid', $barcodeGrid);
    }

    /**
     * Retrieve the label used for the tab relating to this block
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__('Product Barcode');
    }

    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('Product Barcode');
    }

    /**
     * Determines whether to display the tab
     * Add logic here to decide whether you want the tab to display
     *
     * @return bool
     */
    public function canShowTab() {
        if ($this->helper('inventorybarcode')->isMultipleBarcode()) {
            return true;
        }
        return false;
    }

    /**
     * Stops the tab being hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * AJAX TAB's
     * If you want to use an AJAX tab, uncomment the following functions
     * Please note that you will need to setup a controller to receive
     * the tab content request
     *
     */
    /**
     * Retrieve the class name of the tab
     * Return 'ajax' here if you want the tab to be loaded via Ajax
     *
     * return string
     */
    public function getTabClass()
    {
        return 'ajax';
    }

    /**
     * Determine whether to generate content on load or via AJAX
     * If true, the tab's content won't be loaded until the tab is clicked
     * You will need to setup a controller to handle the tab request
     *
     * @return bool
     */
    public function getSkipGenerateContent()
    {
        return true;
    }

    /**
     * Retrieve the URL used to load the tab content
     * Return the URL here used to load the content by Ajax
     * see self::getSkipGenerateContent & self::getTabClass
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('adminhtml/inb_barcode/productbarcodetab', array('id' => $this->getRequest()->getParam('id')));
    }
}