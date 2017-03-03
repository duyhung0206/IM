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
 * @package     Magestore_Inventorybarcode
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorybarcode Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Adminhtml_Inb_PrintbarcodeController extends Magestore_Inventoryplus_Controller_Action {

    
    /*
     * Menu path
     * 
     * @var string
     */
    protected $_menu_path = 'inventoryplus/settings/barcode/barcode_template';
    
    /**
     * select template to print barcode
     *
     * @return Magestore_Inventory_Adminhtml_InventoryController
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->_menu_path);
        return $this;
    }

    /**
     * index action
     */
    public function indexAction() {
        $this->_initAction()
                ->renderLayout();
    }

    public function newAction() {

        $this->_forward('edit');
    }

    public function editAction() {
        $templateId = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventorybarcode/barcodetemplate')->load($templateId);
        if ($templateId) {
            $this->_title($this->__('Inventory'))
                    ->_title($this->__('Add New Barcode Template'));
        }
        if ($model->getId() || $templateId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('barcodeTemplate_data', $model);
            $this->loadLayout()->_setActiveMenu($this->_menu_path);

            $this->getLayout()->getBlock('head')
                    ->addCss('css/magestore/inventorybarcode/hiddenleftslide.css');

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
                    ->removeItem('js', 'mage/adminhtml/grid.js')
                    ->addItem('js', 'magestore/adminhtml/inventory/grid.js');

            $this->_addContent($this->getLayout()->createBlock('inventorybarcode/adminhtml_printbarcode_edit'))
                    ->_addLeft($this->getLayout()->createBlock('inventorybarcode/adminhtml_printbarcode_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventorybarcode')->__('Item does not exist')
            );
            $this->_redirect('*/*/');
        }
    }

    public function saveAction() {
        $data = $this->getRequest()->getPost();
        if ($data) {
            $id = $this->getRequest()->getParam('id');
            $template_name = $data['barcode_template_name'];
            $barcode_unit = $data['barcode_unit'];
            $page_height = $data['page_height'];
            $veltical_distantce = $data['veltical_distantce'];
            $horizontal_distance = $data['horizontal_distance'];
            $attribute_show = $data['attribute_show'];
            $pageWidth = $data['page_width'];
            $top_margin = $data['top_margin'];
            $left_margin = $data['left_margin'];
            $right_margin = $data['right_margin'];
            $bottom_margin = $data['bottom_margin'];
            $barcode_type = $data['barcode_type'];
            $barcode_width = $data['barcode_width'];
            $barcode_height = $data['barcode_height'];
            $font_size = $data['font_size'];
            $status = $data['status'];
            $barcode_per_row = "";
            if ($barcode_type == "1") {
                $barcode_per_row = 1;
            } else {
                $barcode_per_row = $data['barcode_per_row'];
            }
            $model = Mage::getModel('inventorybarcode/barcodetemplate');
            $barcodePerRow = (int) $barcode_per_row;
            $productname_show = 0;
            $sku_show = 0;
            $price_show = 0;
            foreach ($attribute_show as $value) {
                if ($value == '1') {
                    $productname_show = 1;
                }
                if ($value == '2') {
                    $sku_show = 2;
                }
                if ($value == '3') {
                    $price_show = 3;
                }
            }

            if ($id) {
                if ($barcode_type == 0) {
					$template = $model->generateSampleBarcodeTemplateTableHtml($attribute_show);	// Edited by Magnus - 260816
                    $model->setBarcodeTemplateName($template_name)->setHtml($template)->setProductnameShow($productname_show)->setSkuShow($sku_show)->setPriceShow($price_show)
                            ->setBarcodePerRow($barcode_per_row)->setPageHeight($page_height)->setBarcodeUnit($barcode_unit)->setVelticalDistantce($veltical_distantce)->setHorizontalDistance($horizontal_distance)
                            ->setPageWidth($pageWidth)->setTopMargin($top_margin)->setLeftMargin($left_margin)->setRightMargin($right_margin)->setBottomMargin($bottom_margin)->setBarcodeType($barcode_type)
                            ->setBarcodeWidth($barcode_width)->setBarcodeHeight($barcode_height)->setFontSize($font_size)->setStatus($status)->setBarcodeTemplateId($id)->save();
                } else {
					$template5 = $model->generateSampleBarcodeJewelryTemplateHtml();
                    $model->setBarcodeTemplateName($template_name)->setHtml($template5)->setProductnameShow($productname_show)->setSkuShow($sku_show)->setPriceShow($price_show)
                            ->setBarcodePerRow($barcode_per_row)->setPageHeight($page_height)->setBarcodeUnit($barcode_unit)->setVelticalDistantce($veltical_distantce)->setHorizontalDistance($horizontal_distance)
                            ->setPageWidth($pageWidth)->setTopMargin($top_margin)->setLeftMargin($left_margin)->setRightMargin($right_margin)->setBottomMargin($bottom_margin)->setBarcodeType($barcode_type)
                            ->setBarcodeWidth($barcode_width)->setBarcodeHeight($barcode_height)->setFontSize($font_size)->setStatus($status)->setBarcodeTemplateId($id)->save();
                }
            } elseif (!$id) {
                if ($barcode_type == 0) {
					$template = $model->generateSampleBarcodeTemplateTableHtml($attribute_show);
                    $model->setBarcodeTemplateName($template_name)->setHtml($template)->setProductnameShow($productname_show)->setSkuShow($sku_show)->setPriceShow($price_show)
                            ->setBarcodePerRow($barcode_per_row)->setPageHeight($page_height)->setBarcodeUnit($barcode_unit)->setVelticalDistantce($veltical_distantce)->setHorizontalDistance($horizontal_distance)
                            ->setPageWidth($pageWidth)->setTopMargin($top_margin)->setLeftMargin($left_margin)->setRightMargin($right_margin)->setBottomMargin($bottom_margin)
                            ->setBarcodeType($barcode_type)->setBarcodeWidth($barcode_width)->setBarcodeHeight($barcode_height)->setFontSize($font_size)->setStatus($status)->save();
                } else {
					$template5 = $model->generateSampleBarcodeJewelryTemplateHtml();
                    $model->setBarcodeTemplateName($template_name)->setHtml($template5)->setProductnameShow($productname_show)->setSkuShow($sku_show)->setPriceShow($price_show)
                            ->setBarcodePerRow($barcode_per_row)->setPageHeight($page_height)->setBarcodeUnit($barcode_unit)->setVelticalDistantce($veltical_distantce)->setHorizontalDistance($horizontal_distance)
                            ->setPageWidth($pageWidth)->setTopMargin($top_margin)->setLeftMargin($left_margin)->setRightMargin($right_margin)->setBottomMargin($bottom_margin)
                            ->setBarcodeType($barcode_type)->setBarcodeWidth($barcode_width)->setBarcodeHeight($barcode_height)->setFontSize($font_size)->setStatus($status)->save();
                }
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('inventorybarcode')->__('barcode template was successfully saved'));

            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            }
            $this->_redirect('*/*/');
        }
    }

    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $id = $this->getRequest()->getParam('id');
                $model = Mage::getModel('inventorybarcode/barcodetemplate')->setId($id);
                $model->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('Barcode template was successfully deleted')
                );
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /* end Edit by alan - print barcode */

    public function selecttemplateAction() {

        /*  add by Kai - print barcode */
        $checkdata = Mage::getStoreConfig('Inventorybarcode/printbarcode/editdata');

        if (!$checkdata) {
            Mage::getConfig()->saveConfig('Inventorybarcode/printbarcode/editdata', now());

            $model = Mage::getModel('inventorybarcode/barcodetemplate');

            $template1 = '<table style="width:75mm; height:22mm;text-align: center;">
                                <tr>
                                <td style="width:25mm;">
                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>
                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                </td>
                                <td style="width:25mm;">
                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>
                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                </td>
                                <td style="width:25mm;">
                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>
                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                </td>
                                </tr>
                        </table>';

            $template2 = '<table style="width:75mm; height:22mm;text-align: center;">	
                                <tr>		
                                <td style="width:25mm;">				
                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 							
                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                </td>                             		
                                <td style="width:25mm;">  				
                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 			   			
                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                </td>                             		
                                <td style="width:25mm;">				
                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 							
                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                </td>                             	
                                </tr>                             
                        </table>';

            $template3 = '<table style="width:75mm; height:22mm;text-align: center;">	
                                <tr>		
                                        <td style="width:25mm;">				
                                                <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 				
                                                <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Price</span>				
                                                <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                                <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                        </td>                             		
                                        <td style="width:25mm;">  				
                                                <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 				
                                                <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Price</span> 				
                                                <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                                <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                        </td>                             		
                                        <td style="width:25mm;">				
                                                <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 				
                                                <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Price</span>				
                                                <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                                <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                        </td>                             	
                                </tr>                             
                        </table>';

            $template4 = '<table style="width:75mm; height:22mm;text-align: center;">	
                                        <tr>		
                                                <td style="width:25mm;">				
                                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 				
                                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Sku</span>				
                                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                                </td>                             		
                                                <td style="width:25mm;">  				
                                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 				
                                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Sku</span> 				
                                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                                </td>                             		
                                                <td style="width:25mm;">				
                                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span> 				
                                                        <span style="float: left; width: 100%; font-size: 10px; text-align: left; margin-left: 14px;">Product Sku</span>				
                                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>                               				
                                                        <span style="float: left; text-align: center; width: 100%; font-size: 10px;">010091930191421</span>
                                                </td>                             	
                                        </tr>                             
                        </table>';

            $template5 = '<div style="width: 80mm; text-align: center; ">                                                     
                                <table id ="kai" style=" width : 80; height:20; line-height:0.3; ">                                                    
                                        <tr width = 80mm>                                                    
                                                <td id="kai" width = 40mm>                                                    
                                                        <span style="float: left; width: 20mm; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span></br>                                                    
                                                        <span style="float: left; width: 20mm; font-size: 10px; text-align: left; margin-left: 14px;">Product Sku</span> </br>                            
                                                        <span style="float: left; width: 20mm; font-size: 12px; text-align: left; margin-left: 14px;">Price</span>                          
                                                </td>                                                    
                                                <td id="kai"  style="line-height: 0.5; " >                                                    
                                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/></br></br>                                                    
                                                        <span style="float: left; text-align: left; margin-left: 5px;  font-size: 10px;">010091930191421</span>                                                   
                                                </td>                                                    
                                        </tr>                                                    
                                </table>                                                
                        </div>';

            $model->setId(1)->setBarcodeTemplateName("Barcode (3 items per row)")->setHtml($template1)->save();
            $model->setId(2)->setBarcodeTemplateName("Name, Barcode (3 items per row)")->setHtml($template2)->save();
            $model->setId(3)->setBarcodeTemplateName("Name, Price, Barcode (3 items per row)")->setHtml($template3)->save();
            $model->setId(4)->setBarcodeTemplateName("Name, Sku, Barcode (3s item per row)")->setHtml($template4)->save();
            $model->setId(5)->setBarcodeTemplateName("Barcode for jewelry ")->setHtml($template5)->save();
        }
        /* end add by Kai - print barcode */
        $function = Mage::getModel('inventorybarcode/printbarcode_function');
        echo $this->getLayout()->createBlock('inventorybarcode/adminhtml_printbarcode')->setTemplate('inventorybarcode/printbarcode/selecttemplate.phtml')->toHtml();
    }

    public function getimageAction() {

        $params = $this->getRequest()->getParams();
        $type = $params['type'];
        $code = $params['text'];

        if (isset($params['customize']) && $params['customize']) {
            $heigth = $params['heigth_barcode'];
            $barcodeOptions = array('text' => $code,
                'barHeight' => $heigth,
                'fontSize' => $params['font_size'],
                'withQuietZones' => true
            );
        } else {
            $barcodeOptions = array('text' => $code,
                'fontSize' => $params['font_size'],
                'withQuietZones' => true,
                'barHeight' => 22,
            );
        }


        // No required options
        $rendererOptions = array();

        // Draw the barcode in a new image,
        // send the headers and the image
        $imageResource = Zend_Barcode::factory(
                        $type, 'image', $barcodeOptions, $rendererOptions
        );
        Mage::helper('inventoryplus')->imagePng($imageResource->draw(), Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'inventorybarcode' . DS . 'images' . DS . 'barcode' . DS . 'barcode.png');
        $imageResource->render();
    }

    public function printBarcodeAction() {
        $params = $this->getRequest()->getParams();
        $this->loadLayout();
        $this->renderLayout();
    }

    public function massprintBarcodeAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function printViewAction() {
        $barPerRow = $this->getRequest()->getParam('barPerRow');
        $barUnit = $this->getRequest()->getParam('barUnit');
        $pageHeight = $this->getRequest()->getParam('pageHeight');
        $velticalDistantce = $this->getRequest()->getParam('velticalDistantce');
        $horizontalDistance = $this->getRequest()->getParam('horizontalDistance');
        $attributeShow = $this->getRequest()->getParam('attributeShow');
        $pageWidth = $this->getRequest()->getParam('pageWidth');
        $topMargin = $this->getRequest()->getParam('topMargin');
        $leftMargin = $this->getRequest()->getParam('leftMargin');
        $rightMargin = $this->getRequest()->getParam('rightMargin');
        $bottomMargin = $this->getRequest()->getParam('bottomMargin');
        $barType = $this->getRequest()->getParam('barType');
        $barcodeWidth = $this->getRequest()->getParam('barcodeWidth');
        $barcodeHeight = $this->getRequest()->getParam('barcodeHeight');
        $fontSize = $this->getRequest()->getParam('fontSize');
        $attribute_Show = explode(',', $attributeShow);

        $contents = $this->getLayout()->createBlock('inventorybarcode/adminhtml_printbarcode')
                ->setTemplate('inventorybarcode/printbarcode/printview.phtml')
                ->assign('barPerRow', $barPerRow)
                ->assign('barUnit', $barUnit)
                ->assign('pageHeight', $pageHeight)
                ->assign('velticalDistantce', $velticalDistantce)
                ->assign('horizontalDistance', $horizontalDistance)
                ->assign('attributeShow', $attribute_Show)
                ->assign('pageWidth', $pageWidth)
                ->assign('topMargin', $topMargin)
                ->assign('leftMargin', $leftMargin)
                ->assign('rightMargin', $rightMargin)
                ->assign('bottomMargin', $bottomMargin)
                ->assign('barType', $barType)
                ->assign('barcodeWidth', $barcodeWidth)
                ->assign('barcodeHeight', $barcodeHeight)
                ->assign('fontSize', $fontSize);
        echo $contents->toHtml();
    }

    /**
     * mass change status for item(s) action
     */
    public function massStatusAction() {
        $barcodeTemIds = $this->getRequest()->getParam('barcodetemplate');
        if (!is_array($barcodeTemIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($barcodeTemIds as $barcodeTemId) {
                    $model = Mage::getModel('inventorybarcode/barcodetemplate')->load($barcodeTemId);
                    $model->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($timerIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Print barcode in delivery
     * Michael 201602
     */
    public function printBarcodeDeliveryAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

}
