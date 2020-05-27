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
 * Abstract applicable check classes check utility
 */
abstract class EcomDev_Varnish_Model_AbstractApplicable
    implements EcomDev_Varnish_Model_ApplicableInterface
{
    /**
     * List of applicable class names
     * 
     * @var string[]
     */
    protected $_applicableClasses = array();

    /**
     * Returns true if one of the object is an instance of applicable class
     * 
     * @param object $object
     * @return bool
     */
    public function isApplicable($object)
    {
        foreach ($this->_applicableClasses as $className) { 
            if ($object instanceof $className) {
                return true;
            }
        }
        
        return false;
    }
}