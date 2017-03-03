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
 * Inventory Edit Form Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Block_Adminhtml_Printbarcode_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {


    /**
     * prepare tab form's information
     *
     * @return Magestore_Inventory_Block_Adminhtml_Purchaseorder_Edit_Tab_Form
     */
    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('barcodeTemplate_edit', array('legend' => Mage::helper('inventorybarcode')->__('Template information')));

        $data = '';
        if (Mage::getSingleton('adminhtml/session')->getBarcodeTemplateData()) {
            $data = Mage::getSingleton('adminhtml/session')->getBarcodeTemplateData();
            Mage::getSingleton('adminhtml/session')->setBarcodeTemplateData(null);
        } elseif (Mage::registry('barcodeTemplate_data')) {
            $data = Mage::registry('barcodeTemplate_data')->getData();
            if (empty($data)) {
                $data['veltical_distantce']=0;
                $data['horizontal_distance']=0;
                $data['top_margin']=0;
                $data['left_margin']=0;
                $data['right_margin']=0;
                $data['bottom_margin']=0;
                $data['font_size']=2.4;
                $data['status']=1;
            }
            $data['attribute_show'] = array();
            if (isset($data['productname_show']) && $data['productname_show'] == 1) {
                array_push($data['attribute_show'], 1);
            }
            if (isset($data['sku_show']) && $data['sku_show'] == 2) {
                array_push($data['attribute_show'], 2);
            }
            if (isset($data['price_show']) && $data['price_show'] == 3) {
                array_push($data['attribute_show'],3);
            }
            
        }

        $fieldset->addField('barcode_template_name', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Template Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'barcode_template_name',
        ));
        
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('inventorybarcode')->__('Status'),
            'name' => 'status',
            'values' => Mage::getSingleton('inventorybarcode/status')->getOptionHash(),
        ));

        $fieldset->addField('barcode_unit', 'select', array(
            'label' => Mage::helper('inventorybarcode')->__('Unit'),
            'class' => 'required-entry',
            'name' => 'barcode_unit',
            'values' => Mage::getSingleton('inventorybarcode/barcodeUnit')->getOptionHash(),
        ));

        $fieldset->addField('barcode_type', 'select', array(
            'label' => Mage::helper('inventorybarcode')->__('Barcode Type'),
            'class' => 'required-entry',
            'name' => 'barcode_type',
            'values' => Mage::getSingleton('inventorybarcode/barcodetypetemplate')->getOptionHash(),
            'onchange' => 'checkSelectedItem()',
            'after_element_html' => '<script type="text/javascript">
                $("barcodeTemplate_edit").insert({
                     bottom: new Element("img", {style:"float: right;margin-right: 100px;margin-top: -700px;width: 500px;", src: "'. Mage::getBaseUrl('media').'/inventorybarcode/source/3.png'.'"})
                });
                function checkSelectedItem(){ 
                    var barcode_type = $("barcode_type").value;
                    if(barcode_type==1){
                        $("barcode_per_row").value=1;
                        $("barcode_per_row").disabled = true;
                    }else{
                        $("barcode_per_row").disabled = false;
                    }
                    return false;
                }
               
            </script>'
        ));

        $fieldset->addField('barcode_per_row', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Labels per row'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'barcode_per_row',
            'after_element_html' => '<script type="text/javascript">
                var barcode_type = $("barcode_type").value;
                if(barcode_type==1){
                    $("barcode_per_row").disabled = true;
                }
            </script>'
        ));

        $fieldset->addField('page_width', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Paper width'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'page_width'
        ));


        $fieldset->addField('page_height', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Paper height'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'page_height',
        ));
        $fieldset->addField('barcode_width', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Barcode width'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'barcode_width',
        ));
        $fieldset->addField('barcode_height', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Barcode height'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'barcode_height',
        ));
        $fieldset->addField('font_size', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Font size'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'font_size',
        ));
        $fieldset->addField('top_margin', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Top margin'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'top_margin',
        ));
        $fieldset->addField('left_margin', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Left margin'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'left_margin',
        ));
        $fieldset->addField('right_margin', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Right margin'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'right_margin',
        ));
        $fieldset->addField('bottom_margin', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Bottom margin'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'bottom_margin',
        ));



        $fieldset->addField('horizontal_distance', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Space between rows'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'horizontal_distance',
        ));

        $fieldset->addField('veltical_distantce', 'text', array(
            'label' => Mage::helper('inventorybarcode')->__('Space between columns'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'veltical_distantce',
        ));

        $fieldset->addField('attribute_show', 'multiselect', array(
            'label' => Mage::helper('inventorybarcode')->__('Attributes Shown'),
            'name' => 'attribute_show',
            'values' => array(array('value' => '1', 'label' => 'Product Name'), array('value' => '2', 'label' => 'Sku'), array('value' => '3', 'label' => 'Price')),
        ));

        $form->setValues($data);
        return parent::_prepareForm();
    }

    public function getContinueUrl() {
        return $this->getUrl('*/*/saveTemplate', array(
                    '_current' => true,
        ));
    }

}
