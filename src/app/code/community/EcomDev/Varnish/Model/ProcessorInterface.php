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
 * Processor interface
 */
interface EcomDev_Varnish_Model_ProcessorInterface
    extends EcomDev_Varnish_Model_ApplicableInterface
{
    /**
     * Returns list of object tags for varnish
     *
     * @param Mage_Core_Model_Abstract $object
     * @return string[]
     */
    public function getTags($object);

    /**
     * Returns list of tags that should be banned in Varnish
     *
     * Arrays of tags should be associative one
     *
     * @return string[]
     */
    public function getTagsToBan();

    /**
     * Processes before save of the model
     * 
     * @param Mage_Core_Model_Abstract $object
     * @return string
     */
    public function beforeSave($object);

    /**
     * Processes after save of the model
     * 
     * @param Mage_Core_Model_Abstract $object
     * @return mixed
     */
    public function afterSave($object);
}
