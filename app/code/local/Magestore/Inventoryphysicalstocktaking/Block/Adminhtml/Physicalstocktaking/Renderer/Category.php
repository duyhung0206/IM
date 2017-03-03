<?php
class Magestore_Inventoryphysicalstocktaking_Block_Adminhtml_Physicalstocktaking_Renderer_Category extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $product_id = $row->getEntityId();
        $content = '';

        $product = Mage::getModel('catalog/product')->load($product_id);
        $categoryIds = $product->getCategoryIds();
        $i = 0;
        $numItems = count($categoryIds);
        $result = '';
        $categorycollection = Mage::getModel('catalog/category')->getCollection();
        if(!empty($categoryIds)){
            $categorycollection->addAttributeToSelect('name')->addFieldToFilter('entity_id',array('in'=>array($categoryIds)));
        }else{
            $categorycollection->addAttributeToSelect('name')->addFieldToFilter('entity_id',array('in'=>null));
        }

        foreach($categorycollection as $category){
            $result .= $category->getName().',';
            if(++$i === $numItems) {
                $result = substr($result, 0, -1);
            }
        }
        $content .= '<span>'.$result.'</span>';
        return $content;
    }

}
