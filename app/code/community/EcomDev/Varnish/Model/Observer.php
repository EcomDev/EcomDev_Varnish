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
 * Observer for Varnish Actions
 * 
 */
class EcomDev_Varnish_Model_Observer
{
    const VARNISH_CACHE_QUEUE = 'varnish_tags_queue';
    
    /**
     * Collected models by collectors
     * 
     * @var Mage_Core_Model_Abstract[]
     */
    protected $_collectedObjects = array();

    /**
     * Check if ESI is allowed for blocks
     * 
     * @var bool
     */
    protected $_allowedEsi = false;

    /**
     * Collector for blocks, should collect objects, 
     * that was used on the page
     * 
     * @return EcomDev_Varnish_Model_Collector
     */
    protected function _getCollector()
    {
        return Mage::getSingleton('ecomdev_varnish/collector');
    }

    /**
     * Processor for models
     *
     * @return EcomDev_Varnish_Model_Processor
     */
    protected function _getProcessor()
    {
        return Mage::getSingleton('ecomdev_varnish/processor');
    }

    /**
     * Returns helper instance for the module
     * 
     * @return EcomDev_Varnish_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('ecomdev_varnish');
    }

    /**
     * Sets the current page on pre dispatch
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function controllerActionPredispatch(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->isActive()) {
            return $this;
        }

        /* @var $controllerAction Mage_Core_Controller_Front_Action */
        $controllerAction = $observer->getControllerAction();

        $this->_getHelper()->setCurrentPage(
            strtolower($controllerAction->getFullActionName('_'))
        );
        
        $surrogateCompatibility = $controllerAction->getRequest()->getServer('HTTP_SURROGATE_CAPABILITY', '');
        
        if (strpos($surrogateCompatibility, 'ESI/') !== false) {
            $this->_getHelper()->setIsEsiAllowed(true);
        }
        
        if ($this->_getHelper()->isAllowedCurrentPage() 
            || $this->_getHelper()->getIsInternal()) {
            // Disable ?__SID=U in urls
            Mage::app()->setUseSessionVar(false);
            Mage::app()->setUseSessionInUrl(false);
        }

        return $this;
    }

    /**
     * Adds ESI to the layout cache
     * 
     */
    public function controllerActionLayoutLoadBefore(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->isActive()) {
            return;
        }
        
        $updateModel = $observer->getLayout()->getUpdate();
        $handles = $updateModel->getHandles();
        $allowedEsi = $this->_getHelper()->getIsEsiAllowed();
        foreach ($handles as $handle) {
            if ($handle == 'customer_logged_in' 
                && !$this->_getHelper()->getIsInternal()) {
                $updateModel->removeHandle($handle);
                // Emulate always guest session
                $updateModel->addHandle('customer_logged_out');
            }
            
            if ($handle == 'default' && !$this->_getHelper()->getIsInternal()) {
                $updateModel->addHandle('default_varnish');
            }
            
            if ($allowedEsi && strtolower($handle) === $handle) {
                // Add handle only if handle is not starting from UPPERCASE code
                $updateModel->addHandle($handle . '_esi');
            }
        }
    }

    /**
     * Adds varnish cookie headers
     *
     * @param Mage_Core_Controller_Front_Action $controllerAction
     * @return $this
     */
    protected function _addCookies($controllerAction)
    {
        foreach ($this->_getHelper()->getCookies() as $cookie => $value) {
            Mage::getSingleton('core/cookie')->set($cookie, $value, null, null, null, null, false);
        }
        
        return $this;
    }
    
    /**
     * Adds varnish response headers
     * 
     * @param Mage_Core_Controller_Front_Action $controllerAction
     * @return $this
     */
    protected function _addResponseHeaders($controllerAction)
    {
        if ($this->_getHelper()->isDebug()) {
            foreach ($controllerAction->getRequest()->getServer() as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = substr($key, 5);
                    $controllerAction->getResponse()->setHeader('X-Requested-' . $header, $value);
                }
            }

            $controllerAction->getResponse()->setHeader('X-Debug', '1');
        }

        if ($this->_getHelper()->getIsEsiUsed()) {
            $controllerAction->getResponse()->setHeader(
                'Surrogate-Control', 'key=ESI/1.0'
            );
        }

        if (!$this->_getHelper()->isAllowedCurrentPage()) {
            $this->performBan();
            return $this;
        }

        if ($ttl = $this->_getHelper()->getCurrentPageTtl()) {
            $this->_getHelper()->addTtl($ttl);
        }

        if ($controllerAction->getResponse()->canSendHeaders()
            && !$controllerAction->getRequest()->isPost()
            && $controllerAction->getResponse()->getHttpResponseCode() == 200) {

            foreach ($this->_collectedObjects as $object) {
                $this->_getHelper()->addObjectTags(
                    $this->_getProcessor()->getTags($object)
                );
            }

            $this->_collectedObjects = array();

            foreach (Mage::helper('ecomdev_varnish')->getVarnishHeaders() as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $controllerAction->getResponse()->setHeader($name, $val);
                    }
                } else {
                    $controllerAction->getResponse()->setHeader($name, $value, true);
                }
            }
        }

        return $this;
    }
    
    /**
     * Observes all controller post dispatches
     * 
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function controllerActionPostdispatch(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->isActive()) {
            return;
        }
        
       

        /* @var $controllerAction Mage_Core_Controller_Front_Action */
        $controllerAction = $observer->getControllerAction();

        if (!Mage::app()->getStore()->isAdmin() 
            && $controllerAction->getResponse()->canSendHeaders(false)) {
            $this->_addCookies($controllerAction)
                ->_addResponseHeaders($controllerAction);
        }
        
        $this->performBan();
        return $this;
    }

    /**
     * Performs Varnish BAN of object tags, if they exists
     */
    public function performBan()
    {
        if (!$this->_getHelper()->isActive()) {
            return $this;
        }
        
        $tagsToBan = array_unique($this->_getProcessor()->getTagsToBan());
        if ($tagsToBan) {
            try {
                $tagsToBan = array_map(
                    array(Mage::helper('ecomdev_varnish'), 'formatObjectTag'),
                    $tagsToBan
                );
                
                $queue = Mage::app()->getCache()->load(self::VARNISH_CACHE_QUEUE, true);
                
                if ($queue) {
                    $queue = unserialize($queue);
                    foreach ($tagsToBan as $tag) {
                        $queue[] = $tag;
                    }
                } else {
                    $queue = $tagsToBan;
                }
                
                $queue = serialize($queue);
                
                Mage::app()->getCache()->save($queue, self::VARNISH_CACHE_QUEUE);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        return $this;
    }

    /**
     * Background clean up of the cache tags
     * 
     */
    public function backgroundBan()
    {
        $queue = Mage::app()->getCache()->load(self::VARNISH_CACHE_QUEUE, true);

        if ($queue) {
            $queue = unserialize($queue);
            // Run cleaning process separately if amount of stored tags too large
            $chunks = array_chunk(
                $queue,
                500
            );
            
            foreach ($chunks as $tags) {
                Mage::getSingleton('ecomdev_varnish/connector')->banTags($tags);
            }

            Mage::app()->getCache()->remove(self::VARNISH_CACHE_QUEUE);
        }
    }
    
    /**
     * Checks what have been rendered via collectors for passing data later on to processors 
     * 
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function coreBlockAbstractToHtmlAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_getHelper()->isAllowedCurrentPage()) {
            return $this;
        }
        
        $block = $observer->getBlock();
        $objects = $this->_getCollector()->collect($block);
        array_splice($this->_collectedObjects, count($this->_collectedObjects), 0, $objects);
        return $this;
    }

    /**
     * Extenral object collect
     *
     * @param Varien_Object $object
     * @return $this
     */
    public function externalCollect($object)
    {
        $this->_collectedObjects[] = $object;
        return $this;
    }

    /**
     * Processes models before save events
     * 
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function modelSaveBefore(Varien_Event_Observer $observer)
    {
        $this->_getProcessor()->beforeSave($observer->getObject());
        return $this;
    }

    /**
     * Processes models after save commit events
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function modelSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $this->_getProcessor()->afterSave($observer->getObject());
        return $this;
    }
}
