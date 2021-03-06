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
 * Inventory Supplier Edit Form Content Tab Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {

    /**
     * prepare tab form's information
     *
     * @return Magestore_Inventory_Block_Adminhtml_Purchaseorder_Edit_Tab_Form
     */
    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $purchaseOrderId = $this->getRequest()->getParam('id');
        $supplierId = $this->getRequest()->getParam('supplier_id');
        $warehouseIds = $this->getRequest()->getParam('warehouse_ids', null);
        if (Mage::getSingleton('adminhtml/session')->getPurchaseorderData()) {
            $data = Mage::getSingleton('adminhtml/session')->getPurchaseorderData();
            Mage::getSingleton('adminhtml/session')->setPurchaseorderData(null);
        } elseif (Mage::registry('purchaseorder_data')) {
            $data = Mage::registry('purchaseorder_data')->getData();
        }
        $fieldset = $form->addFieldset('purchaseorder_form', array(
            'legend' => Mage::helper('inventorypurchasing')->__('General information')
        ));
        
        $id = $this->getRequest()->getParam('id');
        $disabled = false;
        if ($id && $data['status'] != Magestore_Inventorypurchasing_Model_Purchaseorder::PENDING_STATUS) {
            $disabled = true;
        }

        if ($supplierId) {
            $supplierInfo = Mage::helper('inventorypurchasing/supplier')->getSupplierInfoBySupplierId($supplierId);
        }
        if (!$supplierId) {
            $supplierInfo = Mage::helper('inventorypurchasing/purchaseorder')->getSupplierInfoByPurchaseOrderId($purchaseOrderId);
        }
        $warehouseInfo = $this->getWarehouse($warehouseIds);

        if ($this->getRequest()->getParam('warehouse_ids'))
            $data['warehouse_id'] = $this->getRequest()->getParam('warehouse_ids');

        if ($this->getRequest()->getParam('id')) {
            $fieldset->addField('created_by', 'label', array(
                'label' => Mage::helper('inventorypurchasing')->__('Create by'),
            ));
            $purchaseOrder = Mage::getModel('inventorypurchasing/purchaseorder')->load($this->getRequest()->getParam('id'));
        } else {
            // Default data     
            $current_date = Mage::getModel('core/date')->date();
            $default_cancel_date = date('Y-m-d', strtotime($current_date . " +10 days"));
            $data['purchase_on'] = isset($data['purchase_on']) ? $data['purchase_on'] : $current_date;
            $data['bill_name'] = isset($data['bill_name']) ? $data['bill_name'] : ($this->getSupplier()->getId() ? $this->getSupplier()->getContactName() : 'John Doe');
            $data['started_date'] = isset($data['started_date']) ? $data['started_date'] : $current_date;
            $data['canceled_date'] = isset($data['canceled_date']) ? $data['canceled_date'] : $default_cancel_date;
            $data['expected_date'] = isset($data['expected_date']) ? $data['expected_date'] : $current_date;
            $data['payment_date'] = isset($data['payment_date']) ? $data['payment_date'] : $current_date;
            $data['tax_rate'] = isset($data['tax_rate']) ? $data['tax_rate'] : 0;
            $data['shipping_cost'] = isset($data['shipping_cost']) ? $data['shipping_cost'] : 0;
        }

        $fieldset->addField('purchase_on', 'date', array(
            'label' => Mage::helper('inventorypurchasing')->__('Order Created On'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'purchase_on',
            'time' => true,
            'disabled' => $disabled,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT,
            'format' => Mage::app()->getLocale()->getDatetimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));

        $fieldset->addField('supplier_id', 'hidden', array(
            'label' => Mage::helper('inventorypurchasing')->__('Supplier Id'),
            'name' => 'supplier_id',
        ));
        $fieldset->addField('supplier_name', 'link', array(
            'label' => Mage::helper('inventorypurchasing')->__('Supplier'),
            'name' => 'supplier_name',
            'style' => "color:blue",
            'href' => Mage::helper("adminhtml")->getUrl('adminhtml/inpu_supplier/edit', array('id' => Mage::helper('inventorypurchasing/purchaseorder')->getDataByPurchaseOrderId($purchaseOrderId, 'supplier_id'))),
            'after_element_html' => $supplierInfo,
        ));

        $fieldset->addField('bill_name', 'text', array(
            'label' => Mage::helper('inventorypurchasing')->__('Billing Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'bill_name',
        ));
        $fieldset->addField('warehouse_ids', 'label', array(
            'label' => Mage::helper('inventorypurchasing')->__('Warehouse'),
            'class' => 'required-entry required',
            'required' => true,
            'disabled' => true,
            'after_element_html' => $warehouseInfo
        ));

        $fieldset->addField('order_placed', 'select', array(
            'label' => Mage::helper('inventorypurchasing')->__('Order placed via'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'order_placed',
            'values' => Mage::helper('inventorypurchasing/purchaseorder')->getOrderPlaced(),
        ));

        $fieldset->addField('started_date', 'date', array(
            'label' => Mage::helper('inventorypurchasing')->__('Start shipping date'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'started_date',
            'disabled' => $disabled,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));

        $fieldset->addField('canceled_date', 'date', array(
            'label' => Mage::helper('inventorypurchasing')->__('Cancellation date'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'canceled_date',
            'disabled' => $disabled,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT,
            'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
            'note' => Mage::helper('inventorypurchasing')->__('If an "Awaiting delivery" purchase order has no delivery created, you can cancel it before this date.'),
        ));

        $fieldset->addField('expected_date', 'date', array(
            'label' => Mage::helper('inventorypurchasing')->__('Expected delivery date'),
            'required' => true,
            'name' => 'expected_date',
            'disabled' => $disabled,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT,
            'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));
        $fieldset->addField('payment_date', 'date', array(
            'label' => Mage::helper('inventorypurchasing')->__('Payment date'),
            'required' => true,
            'name' => 'payment_date',
            'disabled' => $disabled,
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATETIME_INTERNAL_FORMAT,
            'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));

        $fieldset->addType('select_new', 'Magestore_Inventorypurchasing_Block_Adminhtml_Renderer_Selectnew');

        $fieldset->addField('ship_via', 'select_new', array(
            'label' => Mage::helper('inventorypurchasing')->__('Shipping via'),
            'name' => 'ship_via',
            'values' => Mage::helper('inventorypurchasing/purchaseorder')->getShippingMethod(),
        ));

        $fieldset->addField('payment_term', 'select_new', array(
            'label' => Mage::helper('inventorypurchasing')->__('Payment terms'),
            'name' => 'payment_term',
            'values' => Mage::helper('inventorypurchasing/purchaseorder')->getPaymentTerms(),
        ));

        $fieldset->addField('comments', 'textarea', array(
            'label' => Mage::helper('inventorypurchasing')->__('Comment'),
            'required' => false,
            'style'        => 'height:50px;',
            'name' => 'comments',
        ));

        if ($this->getRequest()->getParam('id')) {
            $currency = $purchaseOrder->getCurrency();
            if (!$currency) {
                $fieldset->addField('currency', 'select', array(
                    'label' => Mage::helper('inventorypurchasing')->__('Currency'),
                    'class' => 'required-entry',
                    // 'required'    => true,
                    'name' => 'currency',
                    'values' => Mage::app()->getLocale()->getOptionCurrencies(),
                    'after_element_html' => '<script type="text/javascript">$("currency").value=\'' . Mage::app()->getStore($storeId)->getBaseCurrencyCode() . '\'</script>',
                ));
            } else {
                $fieldset->addField('currency', 'select', array(
                    'label' => Mage::helper('inventorypurchasing')->__('Currency'),
                    'class' => 'required-entry',
                    'disabled' => true,
                    'name' => 'currency',
                    'values' => Mage::app()->getLocale()->getOptionCurrencies(),
                ));
            }
        } else {
            $fieldset->addField('currency', 'select', array(
                'label' => Mage::helper('inventorypurchasing')->__('Currency'),
                'class' => 'required-entry',
                // 'required'    => true,
                'name' => 'currency',
                'disabled' => true,
                'values' => Mage::app()->getLocale()->getOptionCurrencies(),
                'after_element_html' => '<script type="text/javascript">$("currency").value=\'' . $this->getRequest()->getParam('currency') . '\'</script>',
            ));
        }
        if (!$this->getRequest()->getParam('id')) {
            $fieldset->addField('change_rate', 'label', array(
                'label' => Mage::helper('inventorypurchasing')->__('Currency Exchange Rate'),
                // 'class'        => 'required-entry',
                // 'required'    => true,
                // 'name'        => 'change_rate',				
                'after_element_html' => '<div id="change_rate_comment"></div>
                                            <script type="text/javascript">
                                                    var base_currency = \'' . Mage::app()->getStore()->getBaseCurrencyCode() . '\';
                                                    var select_currency = $("currency").value;
                                                    var comment = "(1 "+ base_currency +" = ' . $this->getRequest()->getParam('change_rate') . ' " +select_currency +")";
                                                    $("change_rate_comment").innerHTML = comment;
                                            </script>',
            ));
        } else {
            if (!isset($data['change_rate']))
                $data['change_rate'] = '';
            $fieldset->addField('change_rate', 'label', array(
                'label' => Mage::helper('inventorypurchasing')->__('Currency Exchange Rate'),
                // 'class'        => 'required-entry',
                // 'required'    => true,
                // 'name'        => 'change_rate',				
                'after_element_html' => '<div id="change_rate_comment"></div>
                                            <script type="text/javascript">
                                                    var base_currency = \'' . Mage::app()->getStore()->getBaseCurrencyCode() . '\';
                                                    var select_currency = $("currency").value;
                                                    var comment = "(1 "+ base_currency +" = ' . $data['change_rate'] . ' " +select_currency +")";
                                                    $("change_rate_comment").innerHTML = comment;
                                            </script>',
            ));
        }

        $fieldset->addField('tax_rate', 'text', array(
            'label' => Mage::helper('inventorypurchasing')->__('Tax Rate'),
            'required' => true,
            'name' => 'tax_rate',
        ));

        if ($this->getRequest()->getParam('id')) {
            $currency = $purchaseOrder->getCurrency();
        } else {
            $currency = $this->getRequest()->getParam('currency');
        }
        $store = $this->_getStore();
        $storeId = $store->getStoreId();
        $fieldset->addField('shipping_cost', 'text', array(
            'label' => Mage::helper('inventorypurchasing')->__('Shipping Cost'),
            'required' => true,
            'name' => 'shipping_cost',
            'after_element_html' => ' <br /><div id="shipping_cost_comment"></div>
                                        <script type="text/javascript">									
                                                var select_currency = $("currency").value;									
                                                $("shipping_cost_comment").innerHTML = select_currency;
                                        </script>',
        ));

        $data['paid'] = isset($data['paid']) ? $data['paid'] : 0;
        
        if($this->getRequest()->getParam('id')) {
            $currency = $purchaseOrder->getCurrency();
            if (!$currency)
                $currency = Mage::app()->getStore($storeId)->getBaseCurrencyCode();
            $changeRate = $purchaseOrder->getChangeRate();
            if (!$changeRate)
                $changeRate = 1;
            $totalBase = $purchaseOrder->getTotalAmount();
            $taxRate = $purchaseOrder->getTaxRate();
            $shippingCost = $purchaseOrder->getShippingCost();
            $totalWithTaxBase = (1 + $taxRate / 100) * ($totalBase);
            $totalCurrency = $totalBase;

            $totalWithTaxCurrency = $totalWithTaxBase;

            /* Michael 201602*/
            $purchaseOrderId = $this->getRequest()->getParam('id');
            $purchaseOrderProduct = Mage::getModel('inventorypurchasing/purchaseorder_product')->getCollection()
                                        ->addFieldToFilter('purchase_order_id', $purchaseOrderId);
            $subtotal = 0;
            $shippingCost = $purchaseOrder->getShippingCost();
            $tax = 0;
            $discountTax = $purchaseOrder->getDiscountTax();
            $shippingTax = $purchaseOrder->getShippingTax();
            foreach ($purchaseOrderProduct as $pProduct) {
                $subtotal += $pProduct->getQty() * $pProduct->getCost() * (1 - $pProduct->getDiscount()/100);
                if($discountTax == 0){
                    $tax += $pProduct->getQty() * $pProduct->getCost() * ($pProduct->getTax()/100);
                }else{
                    $tax += $pProduct->getQty() * $pProduct->getCost() * (1 - $pProduct->getDiscount()/100) * ($pProduct->getTax()/100);
                }
            }
            if($shippingTax == 0){
                $tax += $shippingCost * ($purchaseOrder->getTaxRate()/100);
            }
            $grandTotalExc = $subtotal + $shippingCost;
            $grandTotalInc = $subtotal + $shippingCost + $tax;

        }
        if($this->getRequest()->getParam('id')) {
            if(!$purchaseOrder->getPaidAll()) {
                $fieldset->addField('paid', 'note', array(
                    'label' => Mage::helper('inventorypurchasing')->__('Money Paid'),
                    //            'text' => Mage::app()->getStore($storeId)->getBaseCurrency()->format($data['paid'])
                    'text' => Mage::app()->getStore($storeId)->setCurrentCurrency(Mage::getModel('directory/currency')->load($currency))->formatPrice($data['paid']),
                ));

                $fieldset->addField('paid_more', 'text', array(
                    'label' => Mage::helper('inventorypurchasing')->__('Last paid payment'),
                    'required' => false,
                    'name' => 'paid_more',
                    //            'after_element_html' => ' '.$store->getBaseCurrency()->getCode(),
                    'after_element_html' => ' <br /><div id="paid_more_comment"></div>
                                                <script type="text/javascript">
                                                        var select_currency = $("currency").value;
                                                        $("paid_more_comment").innerHTML = select_currency;
                                                </script>',
                ));
            }else{
                $fieldset->addField('paid', 'note', array(
                    'label' => Mage::helper('inventorypurchasing')->__('Money Paid'),
                    'text' => Mage::getModel('directory/currency')->load($currency)->formatTxt($totalWithTaxCurrency + $shippingCost),
                ));
            }
        }
        $fieldset->addField('delivery_process', 'label', array(
            'label' => Mage::helper('inventorypurchasing')->__('Delivery Process'),
            'required' => false,
            'name' => 'delivery_process',
            'after_element_html' => '%',
        ));
        
        if (!$this->getRequest()->getParam('id')) {
            $fieldset->addField('send_mail', 'checkbox', array(
                'label' => Mage::helper('inventorypurchasing')->__('Send email to supplier'),
                'required' => false,
                'name' => 'send_mail',
            ));
        }

        /* Michael 201602 */

        $fieldset->addType('purchase_status', 'Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Edit_Tab_Renderer_Purchasestatus');
        $fieldset->addField('status', 'purchase_status', array(
            'label' => Mage::helper('inventorypurchasing')->__('Status'),
            'name' => 'status',
            'values' => Mage::helper('inventorypurchasing/purchaseorder')->getReturnOrderStatus(),
            'disabled' => $disabled,
        ));


        /* end Michael 201602 */

        if ($this->getRequest()->getParam('id')) {
            //$purchaseOrder = Mage::getModel('inventory/purchaseorder')->load($this->getRequest()->getParam('id'));

            $fieldset->addField('total', 'label', array(
                'required' => false,
                'class' => 'required-entry',
                'after_element_html' => '
                        <table id="checkout-review-table" class="data-table" style="float:right;">
                            <colgroup>
                                <col>
                                <col width="1">
                                <col width="1">
                                <col width="1">
                            </colgroup>
                            <tfoot>
                                <tr class="first">
                                    <td colspan="3" class="a-right" style="padding-right:10px;">' . Mage::helper('inventorypurchasing')->__('Subtotal (excl. Tax)') . '</td>
                                    <td class="a-right last" style=""><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($subtotal) . '</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="a-right" style="padding-right:10px;">' . Mage::helper('inventorypurchasing')->__('Shipping Cost') . '</td>
                                    <td class="a-right last" style=""><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($shippingCost) . '</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="a-right" style="padding-right:10px;">' . Mage::helper('inventorypurchasing')->__('Tax'). '</td>
                                    <td class="a-right last" style=""><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($tax) . '</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="a-right" style="padding-right:10px;"><strong>' . Mage::helper('inventorypurchasing')->__('Grand Total (excl. Tax)') . '</strong></td>
                                    <td class="a-right last" style=""><strong><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($grandTotalExc) . '</span></strong></td>
                                </tr>
                                <tr class="last">
                                    <td colspan="3" class="a-right" style="padding-right:10px;"><strong>' . Mage::helper('inventorypurchasing')->__('Grand Total (incl. Tax)') . '</strong></td>
                                    <td class="a-right last" style=""><strong><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($grandTotalInc) . '</span></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    ',
                /*'after_element_html' => '
                        <table id="checkout-review-table" class="data-table" style="float:right;">
                            <colgroup>
                                <col>
                                <col width="1">
                                <col width="1">
                                <col width="1">
                            </colgroup>
                            <tfoot>
                                <tr class="first">
                                    <td colspan="3" class="a-right" style="padding-right:10px;">' . Mage::helper('inventorypurchasing')->__('Subtotal') . '</td>
                                    <td class="a-right last" style=""><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($totalCurrency) . '</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="a-right" style="padding-right:10px;">' . Mage::helper('inventorypurchasing')->__('Shipping Cost') . '</td>
                                    <td class="a-right last" style=""><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($shippingCost) . '</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="a-right" style="padding-right:10px;">' . Mage::helper('inventorypurchasing')->__('Tax') . '</td>
                                    <td class="a-right last" style=""><span class="price">' . $purchaseOrder->getTaxRate() . '%</span></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="a-right" style="padding-right:10px;"><strong>' . Mage::helper('inventorypurchasing')->__('Grand Total (excl. Tax)') . '</strong></td>
                                    <td class="a-right last" style=""><strong><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($totalCurrency + $shippingCost) . '</span></strong></td>
                                </tr>
                                <tr class="last">
                                    <td colspan="3" class="a-right" style="padding-right:10px;"><strong>' . Mage::helper('inventorypurchasing')->__('Grand Total (incl. Tax)') . '</strong></td>
                                    <td class="a-right last" style=""><strong><span class="price">' . Mage::getModel('directory/currency')->load($currency)->formatTxt($totalWithTaxCurrency + $shippingCost) . '</span></strong></td>
                                </tr>
                            </tfoot>        
                        </table>
                    ',
                */
            ));
        }

        $form->setValues($data);
        return parent::_prepareForm();
    }

    protected function _getStore() {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    public function getWarehouse($warehouseIds) {
        if (!$warehouseIds) {
            $warehouseIds = Mage::getModel('inventorypurchasing/purchaseorder')->load($this->getRequest()->getParam('id'))->getWarehouseId();
        }
        $warehouseIds = explode(',', $warehouseIds);
        $warehouseNames = '';
        foreach ($warehouseIds as $warehouseId) {
            $warehouseNames .= "<a href='" . Mage::helper("adminhtml")->getUrl('adminhtml/inp_warehouse/edit', array('id' => $warehouseId)) . "'>" . Mage::getModel('inventoryplus/warehouse')->load($warehouseId)->getWarehouseName() . "</a><br />";
        }
        return "<b>" . $warehouseNames . "</b>";
    }

    public function getSupplier() {
        if (!$this->hasData('supplier')) {
            $supplierId = $this->getRequest()->getParam('supplier_id');
            if (!$supplierId) {
                $purchaseOrder = Mage::registry('purchaseorder_data');
                $supplierId = $purchaseOrder->getSupplierId();
            }
            $supplier = Mage::getModel('inventorypurchasing/supplier')->load($supplierId);
            $this->setData('supplier', $supplier);
        }
        return $this->getData('supplier');
    }

}
