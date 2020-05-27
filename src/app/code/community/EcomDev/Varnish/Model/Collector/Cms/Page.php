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
 * Collector of the CMS Page render
 */
class EcomDev_Varnish_Model_Collector_Cms_Page 
    extends EcomDev_Varnish_Model_AbstractApplicable
    implements EcomDev_Varnish_Model_CollectorInterface
{
    protected $_applicableClasses = array(
        'Mage_Cms_Block_Page'
    );
    
    /**
     * Collects list of objects in product list
     *
     * @param Mage_Cms_Block_Page $object
     * @return Mage_Cms_Model_Page[]
     */
    public function collect($object)
    {
        return array($object->getPage());
    }
}
