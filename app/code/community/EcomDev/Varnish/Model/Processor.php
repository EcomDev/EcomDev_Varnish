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
 * Processor facade
 */
class EcomDev_Varnish_Model_Processor
    extends EcomDev_Varnish_Model_AbstractFacade
{
    const XML_PATH_PROCESSORS = 'varnish/object/processors';
    
    protected $_itemsXmlPath = self::XML_PATH_PROCESSORS;
    
    public function __construct()
    {
        $this->_requiredInterfaces[] = 'EcomDev_Varnish_Model_ProcessorInterface';
    }

    /**
     * Retrieves list of tags for specified object
     *
     * @param object $object
     * @return string[]
     */
    public function getTags($object)
    {
        return $this->walk('getTags', $object, $object);
    }

    /**
     * Performs before save routines on each processor
     * 
     * @param object $object
     * @return array
     */
    public function beforeSave($object)
    {
        return $this->walk('beforeSave', $object, $object);
    }

    /**
     * Performs after save routines on each processor
     * 
     * @param object $object
     * @return array
     */
    public function afterSave($object)
    {
        return $this->walk('afterSave', $object, $object);
    }

    /**
     * Returns array of all tags that was collected by processors for banning
     * them in Varnish
     * 
     * @return array
     */
    public function getTagsToBan()
    {
        return $this->walk('getTagsToBan');
    }

}
