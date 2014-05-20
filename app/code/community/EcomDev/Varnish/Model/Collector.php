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
 * @copyright  Copyright (c) 2014 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Collector facade
 * 
 */
class EcomDev_Varnish_Model_Collector
    extends EcomDev_Varnish_Model_AbstractFacade
{
    const XML_PATH_COLLECTORS = 'varnish/object/collectors';
    
    protected $_itemsXmlPath = self::XML_PATH_COLLECTORS;

    public function __construct()
    {
        $this->_requiredInterfaces[] = 'EcomDev_Varnish_Model_CollectorInterface';
    }

    /**
     * Collects list of objects
     * 
     * @param object $object
     * @return object[]
     */
    public function collect($object)
    {
        return $this->walk('collect', $object, $object);
    }
    
}
