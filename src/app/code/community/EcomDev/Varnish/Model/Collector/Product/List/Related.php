<?php
/**
 * Varnish extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_Varnish
 * @copyright  Copyright (c) 2020 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Collector of the related products
 * 
 */
class EcomDev_Varnish_Model_Collector_Product_List_Related
    extends EcomDev_Varnish_Model_AbstractApplicable
    implements EcomDev_Varnish_Model_CollectorInterface
{
    protected $_applicableClasses = array(
        'Mage_Catalog_Block_Product_List_Related'
    );

    /**
     * Collects list of objects in product list
     *
     * @param Mage_Catalog_Block_Product_List_Related $object
     * @return Mage_Catalog_Model_Product[]
     */
    public function collect($object)
    {
        $result = array();
        $products = $object->getItems();
        foreach ($products as $product) {
            $result[] = $product;
        }
        return $result;
    }
}