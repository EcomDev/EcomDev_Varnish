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
 * Abstract facade class
 * 
 * 
 */
abstract class EcomDev_Varnish_Model_AbstractFacade
{
    protected $_requiredInterfaces = array(
        'EcomDev_Varnish_Model_ApplicableInterface'
    );

    /**
     * Path in the configuration to items config
     * 
     * @var string
     */
    protected $_itemsXmlPath;
    
    /**
     * Items container
     * 
     * @var EcomDev_Varnish_Model_ApplicableInterface[]
     */
    protected $_items = array();

    /**
     * Adds an item to facade
     * 
     * @param EcomDev_Varnish_Model_ApplicableInterface $item
     * @return $this
     * @throws RuntimeException
     */
    public function add($item)
    {
        foreach ($this->_requiredInterfaces as $interface) {
            if (!$item instanceof $interface) {
                throw new RuntimeException(
                    sprintf('Item "%s" should implement "%s" interface', get_class($item), $interface)
                );
            }
        }
        
        $this->_items[spl_object_hash($item)] = $item;
        return $this;
    }

    /**
     * Removes items from facade
     * 
     * @param EcomDev_Varnish_Model_ApplicableInterface $item
     * @return $this
     */
    public function remove($item)
    {
        $hash = spl_object_hash($item);
        if (isset($this->_items[$hash])) {
            unset($this->_items[$hash]);
        }
        
        return $this;
    }

    /**
     * Initializes default facade items
     * 
     * @return $this
     * @throws RuntimeException
     */
    protected function _initItems()
    {
        if (!$this->_itemsXmlPath) {
            throw new RuntimeException('XML Path for facade items is not specified');
        }
        
        $config = Mage::getConfig()->getNode($this->_itemsXmlPath)->children();
        
        foreach ($config as $class) {
            $this->add(Mage::getModel($class));
        }
        
        return $this;
    }

    /**
     * Retrieves facade items
     * If object is specified, it will filter out items by isApplicable interface
     *
     * @param null|object $object
     * @return EcomDev_Varnish_Model_ApplicableInterface[]
     */
    public function items($object = null)
    {
        if (!$this->_items) {
            $this->_initItems();
        }
        
        $items = array();
        
        foreach ($this->_items as $item) {
            if ($object !== null && !$item->isApplicable($object)) {
                continue;
            }
            
            $items[] = $item;
        }
        
        return $items;
    }

    /**
     * Invokes method on each facade item 
     * with specified arguments
     * 
     * @param string $method
     * @param null|mixed $arg
     * @param null|object $object
     * @return array
     */
    public function walk($method, $arg = null, $object = null)
    {
        $result = array();
        foreach ($this->items($object) as $item) {
            $itemResult = $item->$method($arg);
            if ($itemResult === $item) {
                continue;
            }
            if (!is_array($itemResult)) {
                $result[] = $itemResult;
            } elseif (isset($itemResult[0])) {
                array_splice($result, count($result), 0, $itemResult);  
            } elseif (count($itemResult)) {
                $result += $itemResult;
            }
        }
        
        return $result;
    }
}