<?php
/**
 * Varnish extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement for EcomDev Premium Extensions.
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.ecomdev.org/license-agreement
 *
 * @category   EcomDev
 * @package    EcomDev_Varnish
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://www.ecomdev.org/license-agreement  End User License Agreement for EcomDev Premium Extensions.
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Varnish Module Helper
 * 
 * Contains methods for storing some data 
 * between recording object load and sending response to customer
 * 
 * 
 */
class EcomDev_Varnish_Helper_Data extends Mage_Core_Helper_Abstract
{
    const HEADER_TTL = 'X-Cache-Ttl';
    const HEADER_OBJECTS = 'X-Cache-Objects';
    const HEADER_SEGMENT = 'X-Cache-Segment';
    const HEADER_STORE = 'X-Cache-Store';
    const HEADER_GZIP = 'X-Cache-Gzip';

    const HEADER_OBJECTS_ITEMS = 100;

    const COOKIE_TOKEN = 'varnish_token';
    const COOKIE_TOKEN_CHECKSUM = 'varnish_token_checksum';

    const OBJECT_TAG_FORMAT = ':%s:';
    
    const XML_PATH_ALLOWED_PAGES = 'varnish/pages';
    const XML_PATH_OBJECTS = 'varnish/objects';
    const XML_PATH_PAGE_TTL = 'varnish/pages/%s_time';
    const XML_PATH_ACTIVE = 'varnish/settings/active';
    const XML_PATH_ESI_KEY = 'varnish/settings/esi_key';
    const XML_PATH_SEGMENT_STORE = 'varnish/settings/store_segment';
    const XML_PATH_GZIP = 'varnish/settings/gzip';
    const XML_PATH_DEBUG = 'varnish/settings/debug';
    
    /**
     * List of tag names for varnish storage
     * 
     * @var string[]
     */
    protected $_objectTags = array();

    /**
     * List of custom headers to be send for varnish 
     * 
     * @var string[]
     */
    protected $_varnishHeaders = array();

    /**
     * List of TTL for the page, 
     * that are specified in configuration 
     * or in custom entities list 
     * 
     * @var int[]
     */
    protected $_ttlList = array();

    /**
     * List of cookies to send to browser
     * 
     * @var string[]
     */
    protected $_cookies = array();

    /**
     * List of allowed pages that are retrieved from
     * config xml file definitions 
     * 
     * @var string[]
     */
    protected $_allowedPages;

    /**
     * The current page full action name
     * 
     * @var string
     */
    protected $_currentPage;

    /**
     * Check if ESI is allowed
     * 
     * @var bool
     */
    protected $_isEsiAllowed = false;

    /**
     * Check if ESI is used
     * 
     * @var bool
     */
    protected $_isEsiUsed = false;

    /**
     * Is internal action
     *
     * @var bool
     */
    protected $_isInternal = false;

    /**
     * Url for ajax reload
     * 
     * @var string
     */
    protected $_ajaxReloadUrl;

    /**
     * Returns a url for reloading
     * 
     * @return string
     */
    public function getAjaxReloadUrl()
    {
        if ($this->_ajaxReloadUrl === null) {
            $this->_ajaxReloadUrl = parse_url(
                $this->_getUrl('varnish/ajax/reload'), 
                PHP_URL_PATH
            );
        }
        
        return $this->_ajaxReloadUrl;
    }
    
    /**
     * Checks if extension is active
     *
     * @return mixed
     */
    public function isActive()
    {
        return Mage::getStoreConfig(self::XML_PATH_ACTIVE);
    }
    
    /**
     * Init default varnish headers
     * 
     * @return $this
     */
    protected function _initDefaultVarnishHeaders()
    {
        // Add TTL header if any of them have been specified
        if ($this->_ttlList) {
            $this->setVarnishHeader(self::HEADER_TTL, min($this->_ttlList) . 's');
        }

        // Add object tags header only if it is not added
        if ($this->_objectTags && !$this->hasVarnishHeader(self::HEADER_OBJECTS)) {
            $objects = array_map(
                array($this, 'formatObjectTag'),
                $this->_objectTags
            );
            
            $headerValue = array();
            
            foreach (array_chunk($objects, self::HEADER_OBJECTS_ITEMS) as $objectSubset) {
                $headerValue[] = implode(',', $objectSubset);
            }
            
            $this->setVarnishHeader(
                self::HEADER_OBJECTS, 
                $headerValue
            );
        }
        
        if (!$this->hasVarnishHeader(self::HEADER_SEGMENT)) {
            $this->setVarnishHeader(self::HEADER_SEGMENT, $this->hashData($this->getCustomerSegment()));
        }
        
        if (!$this->hasVarnishHeader(self::HEADER_STORE)) {
            $this->setVarnishHeader(self::HEADER_STORE, Mage::app()->getStore()->getCode());
        }

        if (Mage::getStoreConfigFlag(self::XML_PATH_GZIP)
            && !$this->hasVarnishHeader(self::HEADER_GZIP)) {
            $this->setVarnishHeader(self::HEADER_GZIP, '1');
        }

        return $this;
    }

    /**
     * Returns current page info
     * 
     * 
     */
    public function getCurrentPageInfo()
    {
        $result = array(
            'handle' => $this->getCurrentPage()
        );
        
        if (Mage::registry('current_category') instanceof Mage_Catalog_Model_Category) {
            $result['category'] = Mage::registry('current_category')->getId();
        }

        if (Mage::registry('current_product') instanceof Mage_Catalog_Model_Product) {
            $result['product'] = Mage::registry('current_product')->getId();
        }
        
        return $result;
    }
    
    /**
     * Set ESI is allowed flag
     * 
     * @param bool $flag
     * @return $this
     */
    public function setIsEsiAllowed($flag)
    {
        $this->_isEsiAllowed = $flag;
        return $this;
    }

    /**
     * Get ESI is allowed flag
     *
     * @return $this
     */
    public function getIsEsiAllowed()
    {
        return $this->_isEsiAllowed;
    }

    /**
     * Set ESI is used flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setIsEsiUsed($flag)
    {
        $this->_isEsiUsed = $flag;
        return $this;
    }

    /**
     * Get ESI is used flag
     *
     * @return $this
     */
    public function getIsEsiUsed()
    {
        return $this->_isEsiUsed;
    }
    
    /**
     * Returns list of varnish headers
     * 
     * @return array[]
     */
    public function getVarnishHeaders()
    {
        $this->_initDefaultVarnishHeaders();
        return $this->_varnishHeaders;
    }

    /**
     * Formats object tag
     * 
     * @param string $tag
     * @return string
     */
    public function formatObjectTag($tag)
    {
        return sprintf(self::OBJECT_TAG_FORMAT, $tag);
    }

    /**
     * Adds varnish header
     * 
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addVarnishHeader($name, $value)
    {
        $header = $value;
        
        if ($this->hasVarnishHeader($name)) {
            $header = $this->getVarnishHeader($name);
            
            if (!is_array($header)) {
                $header = array($header);
            }
            
            $header[] = $value;
        } 
        
        $this->setVarnishHeader($name, $header);
        
        return $this;
    }

    /**
     * Returns varnish header values
     * 
     * @param string $name
     * @return bool|string|string[]
     */
    public function getVarnishHeader($name)
    {
        if (!$this->hasVarnishHeader($name)) {
            return false;
        }
        
        return $this->_varnishHeaders[$name];
    }

    /**
     * Add a cookie
     * 
     * @param string $name
     * @param string $value
     * @param bool $httpOnly
     * @return $this
     */
    public function addCookie($name, $value, $httpOnly = false)
    {
        Mage::getSingleton('ecomdev_varnish/cookie')->set($name, $value, $httpOnly);
        return $this;
    }

    /**
     * Return list of cookies that should be sent to browser
     * 
     * @return array
     */
    public function getCookies()
    {
        return Mage::getSingleton('ecomdev_varnish/cookie')->getAll();
    }

    /**
     * Sets (overrides) varnish header
     * 
     * @param string $name
     * @param array $value
     * @return $this
     */
    public function setVarnishHeader($name, $value)
    {
        $this->_varnishHeaders[$name] = $value;
        return $this;
    }

    /**
     * Unset varnish header from list
     * 
     * @param string $name
     * @return $this
     */
    public function unsVarnishHeader($name)
    {
        if ($this->hasVarnishHeader($name)) {
            unset($this->_varnishHeaders[$name]);
        }
        
        return $this;
    }

    /**
     * Checks existence of the header in the list
     * 
     * @param string $name
     * @return bool
     */
    public function hasVarnishHeader($name)
    {
        return isset($this->_varnishHeaders[$name]);
    }
    
    /**
     * Add a new object tag to the list of available ones
     * 
     * @param string $tag
     * @return $this
     */
    public function addObjectTag($tag)
    {
        $this->_objectTags[$tag] = $tag;
        return $this;
    }

    /**
     * Adds a set of tags. 
     * 
     * Passed array should be associative.
     *
     * @param string[] $tags
     * @return $this
     */
    public function addObjectTags($tags)
    {
        $this->_objectTags += $tags;
        return $this;
    }

    /**
     * Returns true if object tag exists
     * 
     * @param string $tag
     * @return bool
     */
    public function hasObjectTag($tag)
    {
        return isset($this->_objectTags[$tag]);
    }

    /**
     * Unsets object tag
     * 
     * @param string $tag
     * @return $this
     */
    public function unsObjectTag($tag)
    {
        if ($this->hasObjectTag($tag)) {
            unset($this->_objectTags[$tag]);
        }
        
        return $this;
    }

    /**
     * Adds time to leave for the page
     * 
     * @param int $ttl
     * @return $this
     */
    public function addTtl($ttl)
    {
        $this->_ttlList[] = $ttl;
        return $this;
    }

    /**
     * Init allowed pages from configuration
     * 
     * @return $this
     */
    protected function _initAllowedPages()
    {
        if ($this->_allowedPages === null) {
            $this->_allowedPages = array();
            
            foreach (Mage::getConfig()->getNode(self::XML_PATH_ALLOWED_PAGES)->children() as $page => $info) {
                $module = ($info->getAttribute('module') ? $info->getAttribute('module') : 'ecomdev_varnish');
                $this->_allowedPages[$page] = Mage::helper($module)->__((string)$info->label);
            }
        }
        
        return $this;
    }

    /**
     * Returns allowed pages list
     * 
     * @return string[]
     */
    public function getAllowedPages()
    {
        $this->_initAllowedPages();
        return $this->_allowedPages;
    }

    /**
     * Checks if current page is not allowed for varnish cache
     * 
     * @return bool
     */
    public function isAllowedCurrentPage()
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->_initAllowedPages();
        
        if (!$this->getCurrentPage()) {
            return false;
        }
        
        return isset($this->_allowedPages[$this->getCurrentPage()]);
    }

    /**
     * Sets the current page
     * 
     * @param string $fullActionName
     * @return $this
     */
    public function setCurrentPage($fullActionName)
    {
        $this->_currentPage = $fullActionName;
        return $this;
    }

    /**
     * Returns currently used page name
     * 
     * @return string
     */
    public function getCurrentPage()
    {
        return $this->_currentPage;
    }

    /**
     * Returns current page TTL in seconds
     * 
     * @return bool|int
     */
    public function getCurrentPageTtl()
    {
        if ($this->getCurrentPage()) {
            $cacheTtl = (int)Mage::getStoreConfig(sprintf(self::XML_PATH_PAGE_TTL, $this->getCurrentPage()));
            if ($cacheTtl)  {
                return $cacheTtl*60;
            }
        }
        
        return false;
    }

    /**
     * Returns ESI key
     * 
     * @return string
     */
    public function getEsiKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_ESI_KEY);
    }

    /**
     * Is Debug
     *
     * @return string
     */
    public function isDebug()
    {
        return Mage::getStoreConfig(self::XML_PATH_DEBUG);
    }


    /**
     * Returns true if current action is internal
     * 
     * @return bool
     */
    public function getIsInternal()
    {
        return $this->_isInternal;
    }

    /**
     * Sets is internal action flag
     * 
     * @param bool $flag
     * @return $this
     */
    public function setIsInternal($flag)
    {
        $this->_isInternal = $flag;
        return $this;
    }

    /**
     * Returns customer segment data, used as cache segment
     * 
     * @return string[]
     */
    public function getCustomerSegment()
    {
        $segment = new Varien_Object();
        $segment->setCustomerGroupId(Mage::getSingleton('customer/session')->getCustomerGroupId());

        if ($segment->getData()) {
            $segment->setStoreId(Mage::app()->getStore()->getId());
        }

        Mage::dispatchEvent('ecomdev_varnish_customer_segment', array('segment' => $segment));
        
        return $segment->getData();
    }

    /**
     * Hashes data for varnish
     *
     * @param string $hashData
     * @param bool $addDeviceType
     * @return string
     */
    public function hashData($hashData, $addDeviceType = true)
    {
        if (is_array($hashData)) {
            $hashData = serialize($hashData);
        }

        if ($addDeviceType) {
            // Add device type
            $hashData .= $this->_getRequest()->getServer('HTTP_X_UA_DEVICE');
        }

        return md5($hashData);
    }
    
    /**
     * Calls a helper depending on internal level of cache of the page 
     * 
     * @param string $helper
     * @param string $method
     * @param mixed $cachedValue
     * @param array $args
     * @return mixed
     */
    public function cachedHelperCall($helper, $method, $cachedValue, $args = array())
    {
        if ($this->isActive() && !$this->getIsInternal()) {
            return $cachedValue;
        }
        
        $helper = Mage::helper($helper);
        
        if (!empty($args)) {
            return call_user_func_array(array($helper, $method), $args);
        }
        
        return $helper->$method();
    }

    /**
     * Returns checksum for supplied data array
     *
     * @param array $data
     * @return string
     */
    public function getChecksum($data)
    {
        $data['salt'] = $this->getEsiKey();
        ksort($data);
        return md5(json_encode($data));
    }

    /**
     * Validates checksum of esi request
     *
     * @param array $data
     * @return bool
     */
    public function validateChecksum($data)
    {
        if (!isset($data['checksum']) || !$this->getEsiKey()) {
            return false;
        }

        $suppliedChecksum = $data['checksum'];
        unset($data['checksum']);
        return $suppliedChecksum === $this->getChecksum($data);
    }

    /**
     * Generates CSRF token for a user
     *
     * @return $this
     */
    public function generateToken()
    {
        $token = Mage::helper('core')->getRandomString(16);
        $this->addCookie(self::COOKIE_TOKEN, $token);
        $this->addCookie(self::COOKIE_TOKEN_CHECKSUM, $this->getChecksum(array('token' => $token)));
        return $this;
    }

    /**
     * Validates a token value
     *
     * @param string $token
     * @return bool
     */
    public function validateToken($token)
    {
        $tokenChecksum = Mage::getSingleton('ecomdev_varnish/cookie')->get(self::COOKIE_TOKEN_CHECKSUM);
        if ($tokenChecksum === $this->getChecksum(array('token' => $token))) {
            return true;
        }

        return false;
    }
}
