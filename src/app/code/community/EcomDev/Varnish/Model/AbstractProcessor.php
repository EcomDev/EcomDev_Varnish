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
 * Implements basic methods for processor
 * 
 */
abstract class EcomDev_Varnish_Model_AbstractProcessor 
    extends EcomDev_Varnish_Model_AbstractApplicable
    implements EcomDev_Varnish_Model_ProcessorInterface
{
    protected $_tagsToBan = array();

    /**
     * Is for update flag
     *
     * @var bool
     */
    protected $_isForUpdate = false;
    
    /**
     * Collects list of objects that can be used by object retrievers
     *
     * @param Mage_Core_Model_Abstract $object
     * @return string[]
     */
    public function getTags($object)
    {
        $tags = $this->_collectTags($object);
        
        if (is_string($tags)) {
            $tags = array(
                $tags => $tags
            );
        } elseif (!is_array($tags)) {
            return array();
        }
        
        return $tags;
    }

    /**
     * Should return list of tags to clean
     * 
     * @param Varien_Object $object
     * @return string[]|string
     */
    abstract protected function _collectTags($object);

    /**
     * Returns list of tags that should be banned in Varnish
     *
     * Arrays of tags should be associative one
     *
     * @return string[]
     */
    public function getTagsToBan()
    {
        return $this->_tagsToBan;
    }

    /**
     * Processes before save of the model
     *
     * @param Mage_Core_Model_Abstract $object
     * @return string
     */
    public function beforeSave($object)
    {
        $this->_isForUpdate = true;
        return $this;
    }

    /**
     * Processes after save of the model
     *
     * @param Mage_Core_Model_Abstract $object
     * @return mixed
     */
    public function afterSave($object)
    {
        $this->_tagsToBan += $this->getTags($object);
        return $this;
    }
}
