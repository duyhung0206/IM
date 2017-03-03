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
 * Inventorybarcode Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Model_Barcodetemplate extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('inventorybarcode/barcodetemplate');
    }
	
	/* Added by Magnus - 250816 */
	public function attributeShownLabel(){
		return array(1 => 'Product Name',
					2 => 'Sku',
					3 => 'Price',
					'sku' => 'Sku',
					'product_name' => 'Product Name',
					'price' => 'Price',
					'barcode' => 'Barcode');
	}
	
	/* Added by Magnus - 250816 */
	public function generateSampleBarcodeTemplateHtml($attribute_show){
		if(in_array(1,$attribute_show) && in_array(2,$attribute_show) && in_array(3,$attribute_show)){	/* All attributes are selected = Product Name + Sku + Price */
			$html = '<div style="width: 220px; text-align: center;"><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="font-size: 10px; float: left; text-align: center; width: 100%;">010091930191421</span><div style="width: 50%; float: left; text-align: left;\"><ul style="float: left; list-style: outside none none; margin: 0px 0px 0px -26px;"><li>Product Name</li><li>SKU</li></ul></div><div style="width: 50%; float: left; text-align: left;"><span style="text-align: right; float: right; font-size: 30px; margin-right: 13px; margin-top: 11px;">Price</span></div></div>';
		}elseif(in_array(1,$attribute_show) && in_array(2,$attribute_show)){/* All attributes are selected = Product Name + Sku */
			$html = '<div style="width: 220px; text-align: center;"><span style="float: left; font-size: 17px; text-align: left; width: 47%; margin-left: 13px;">Product Name</span><span style="font-size: 17px; float: left; text-align: left; margin-left: 55px; width: 20%;">SKU</span><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}elseif(in_array(1,$attribute_show) && in_array(3,$attribute_show)){/* Attributes are selected = Product Name  + Price */
			$html = '<div style="width: 220px; text-align: center;"><span style="float: left; font-size: 17px; text-align: left; width: 47%; margin-left: 13px;">Product Name</span><span style="font-size: 17px; float: left; text-align: left; margin-left: 55px; width: 20%;">Price</span><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}elseif(in_array(2,$attribute_show) && in_array(3,$attribute_show)){/* Attributes are selected = Sku + Price */
			$html = '<div style="width: 220px; text-align: center;"><span style="float: left; font-size: 17px; text-align: left; width: 47%; margin-left: 13px;">SKU</span><span style="font-size: 17px; float: left; text-align: left; margin-left: 55px; width: 20%;">Price</span><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}elseif(in_array(1,$attribute_show)){/* Attribute is selected = Product Name */
			$html = '<div style="width: 220px; text-align: center;"><span style="float: left; width: 100%; font-size: 17px; text-align: left; margin-left: 14px;">Product Name</span><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}elseif(in_array(2,$attribute_show)){/* Attribute is selected = Sku */
			$html = '<div style="width: 220px; text-align: center;"><span style="float: left; width: 100%; font-size: 17px; text-align: left; margin-left: 14px;">SKU</span><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}elseif(in_array(3,$attribute_show)){/* Attribute is selected =  Price */
			$html = '<div style="width: 220px; text-align: center;"><span style="float: left; width: 100%; font-size: 17px; text-align: left; margin-left: 14px;">Price</span><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}else{
			$html = '<div style="width: 220px; text-align: center;"><img style="width: 200px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/><span style="float: left; text-align: center; width: 100%; font-size: 17px;">010091930191421</span></div>';
		}

		return $html;
	}
	
	/* Added by Magnus - 250816 */
	public function generateSampleBarcodeTemplateTableHtml($attribute_show){
		$html = '<table style="width:55mm; height:40mm;text-align: center;margin-top:10px;">
			<tr>
				<td style="width:55mm;">';
		
		if(in_array(1,$attribute_show) && in_array(2,$attribute_show) && in_array(3,$attribute_show)){	/* All attributes are selected = Product Name + Sku + Price */
			$html .= '<span style="float: left; width: 100%; font-size: 11px; text-align: center;">Product Name</span>';
			$html .= '<span style="float: left; width: 100%; font-size: 11px; text-align: center;">SKU</span>';
			$html .= '<span style="float: left; width: 100%; font-size: 11px; text-align: center;">Price</span>';
		}elseif(in_array(1,$attribute_show) && in_array(2,$attribute_show)){/* All attributes are selected = Product Name + Sku */
			$html .= '<span style="float: left; width: 100%; font-size: 12px; text-align: center;">Product Name</span>';
			$html .= '<span style="float: left; width: 100%; font-size: 12px; text-align: center;">SKU</span>';
		}elseif(in_array(1,$attribute_show) && in_array(3,$attribute_show)){/* Attributes are selected = Product Name  + Price */
			$html .= '<span style="float: left; width: 100%; font-size: 12px; text-align: center;">Product Name</span>';
			$html .= '<span style="float: left; width: 100%; font-size: 12px; text-align: center;">Price</span>';
		}elseif(in_array(2,$attribute_show) && in_array(3,$attribute_show)){/* Attributes are selected = Sku + Price */
			$html .= '<span style="float: left; width: 100%; font-size: 12px; text-align: center;">Sku</span>';
			$html .= '<span style="float: left; width: 100%; font-size: 12px; text-align: center;">Price</span>';
		}elseif(in_array(1,$attribute_show)){/* Attribute is selected = Product Name */
			$html .= '<span style="float: left; width: 100%; font-size: 13px; text-align: center;">Product Name</span>';
		}elseif(in_array(2,$attribute_show)){/* Attribute is selected = Sku */
			$html .= '<span style="float: left; width: 100%; font-size: 13px; text-align: center;">SKU</span>';
		}elseif(in_array(3,$attribute_show)){/* Attribute is selected =  Price */
			$html .= '<span style="float: left; width: 100%; font-size: 13px; text-align: center;">Price</span>';
		}else{
			$html .= '';
		}
		$html .= '<img style="width: 100%;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/>
					<span style="float: left; text-align: center; width: 100%; font-size: 13px;">010091930191421</span>
					</td>
			</tr>
		</table>';
		
		return $html;
	}
	
	/* Added by Magnus - 250816 */
	public function generateSampleBarcodeJewelryTemplateHtml(){
		$template = '<div style="width: 80mm; text-align: center; ">                                                     
						<table id ="kai" style=" width : 80; height:20; line-height:0.6; ">
							<tr width = 80mm>                                                    
								<td id="kai" width = 40mm>                                                    
									<span style="float: left; width: 20mm; font-size: 10px; text-align: left; margin-left: 14px;">'.Mage::helper('inventorybarcode')->__('Product Name').'</span></br>
									<span style="float: left; width: 20mm; font-size: 10px; text-align: left; margin-left: 14px;">'.Mage::helper('inventorybarcode')->__('Product Sku').'</span> </br>
									<span style="float: left; width: 20mm; font-size: 12px; text-align: left; margin-left: 14px;">'.Mage::helper('inventorybarcode')->__('Price').'</span>
								</td>                                                    
								<td id="kai"  style="line-height: 0.5; " >                                                    
									<img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/></br></br>                                                    
									<span style="float: left; text-align: left; margin-left: 5px;  font-size: 10px;">010091930191421</span>                                                   
								</td>                                                    
							</tr>                                                    
						</table>                                                
					</div>';
		return $template;
	}
	
}