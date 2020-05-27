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
 * Collector of the category page
 * 
 */
class EcomDev_Varnish_Model_Collector_Category_View 
    extends EcomDev_Varnish_Model_AbstractApplicable
    implements EcomDev_Varnish_Model_CollectorInterface
{
    protected $_applicableClasses = array(
        'Mage_Catalog_Block_Category_View'
    );
    
    /**
     * Collects list of objects in product list
     *
     * @param Mage_Catalog_Block_Category_View $object
     * @return Mage_Catalog_Model_Category[]
     */
    public function collect($object)
    {
        return array($object->getCurrentCategory());
    }
}
