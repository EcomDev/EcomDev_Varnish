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
 * Interface for data collector from blocks
 */
interface EcomDev_Varnish_Model_CollectorInterface 
    extends EcomDev_Varnish_Model_ApplicableInterface
{
    /**
     * Collects list of objects that can be used by object retrievers
     * 
     * @param Varien_Object $object
     * @return Mage_Core_Model_Abstract[]
     */
    public function collect($object);
}