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
 * @package     Magestore_Inventorysupplyneeds
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventoryreports Adminhtml Block
 *
 * @category    Magestore
 * @package     Magestore_Inventoryreports
 * @author      Magestore Developer
 */
class Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Reportbybestseller_Chart
    extends Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Chart {
    
    protected function _prepareLayout() {
        $this->setTemplate('inventoryreports/content/chart/chart-content/bestseller/chart.phtml');
        return parent::_prepareLayout();
    }
    
    public function getChartColumnData() {
        $data = array();
        $results = $this->getProductReportData();
        $categories = $results['column_categories'];
        $series = $results['column_series'];
        $data['categories'] = $categories;
        $data['series'] = $series;
        return $data;
    }

    public function getChartPieData() {
        $data = array();
        $results = $this->getProductReportData();
        $series = $results['pie_series'];
        $data['series'] = $series;
        return $data;
    }

    public function getProductReportData() {
        return $this->getBestSellerProduct();
    }

    public function getBestSellerProduct(){

        $requestData = $this->getRequestData();
        $productCollection = Mage::helper('inventoryreports/bestseller')
                                ->getBestSellerProductCollection($requestData)
                                ->setOrder('ordered_qty', 'desc');
        $results = array();
        if (count($productCollection)) {
            foreach ($productCollection as $item) {
                $results[] = $item->getData();
            }
        }
        $columnSeries = array();
        $product_name = array();
        $data = array();
        if (count($results) > 0) {
            //pie data
            $pieSeries = $this->getPieSeries($results);
            $data['pie_series'] = $pieSeries;
            //column data
            foreach ($results as $result) {
                $product_name[] = $result['order_items_name'];
                $total_remain[] = ((int) $result['ordered_qty']);
            }
            $i = 0;
            $columnCategories = '[';
            foreach ($product_name as $name_value) {
                if ($i != 0) {
                    $columnCategories .= ',';
                }
                $columnCategories .= '"' . $name_value . '"';
                $i++;
            }
            $columnCategories .= ']';
            $j = 0;
            $columnSeries['inventory_product']['data'] = '[';
            foreach ($total_remain as $number_value) {
                if ($j != 0) {
                    $columnSeries['inventory_product']['data'] .= ',';
                }
                $columnSeries['inventory_product']['data'] .= $number_value;
                $j++;
            }
            $columnSeries['inventory_product']['data'] .= ']';
            $data['column_series'] = $columnSeries;
            $data['column_categories'] = $columnCategories;
        } else {
            $data['pie_series'] = '';
            $data['column_series'] = '';
            $data['column_categories'] = '';
        }
        return $data;
    }

    protected function getPieSeries($results){
        $pieSeries = '';
        $i = 0;
        foreach ($results as $result) {
            if ($i != 0)
                $pieSeries .= ',';
            $pieSeries .= '[\'' . $result['order_items_name'] . ' (' . (int) $result['ordered_qty'] . ' items)\',' . (int) $result['ordered_qty'] . ']';
            $i++;
        }
        return $pieSeries;
    }
}