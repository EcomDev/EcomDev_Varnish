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
 * CMS page processor
 * 
 */
class EcomDev_Varnish_Model_Processor_Cms_Page
    extends EcomDev_Varnish_Model_AbstractProcessor
{
    const TAG_PREFIX = 'CMSP';

    protected $_applicableClasses = array(
        'Mage_Cms_Model_Page'
    );
    
    /**
     * Should return list of tags to clean
     *
     * @param Mage_Cms_Model_Page $object
     * @return string[]|string
     */
    protected function _collectTags($object)
    {
        return self::TAG_PREFIX . $object->getId();
    }
}
