<?php

/**
 * Wrapper for cookie values
 * 
 */
class EcomDev_Varnish_Model_Cookie
{
    protected $_cookies = array();

    /**
     * @var Mage_Core_Controller_Request_Http
     */
    protected $_request;

    /**
     * Stores a cookie for scheduled set cookie calls
     * 
     * @param string $cookie
     * @param string $value
     * @param bool $httpOnly
     * @return $this
     */
    public function set($cookie, $value, $httpOnly = false)
    {
        $this->_cookies[$cookie] = array('value' => $value, 'http_only' => $httpOnly);
        return $this;
    }

    /**
     * Returns true only if cookie has been implicitly specified via set() method 
     * 
     * @param $cookie
     * @return bool
     */
    public function has($cookie)
    {
        return isset($this->_cookies[$cookie]);
    }

    /**
     * Retrieves a cookie value from scheduled list or request cookie
     * 
     * @param string $cookie
     * @return mixed|null
     */
    public function get($cookie)
    {
        if (!$this->has($cookie)) {
            if ($this->getRequest()) {
                return $this->getRequest()->getCookie($cookie);
            }
            
            return null;
        }
        
        return $this->_cookies[$cookie]['value'];
    }
    
    public function getAll()
    {
        return $this->_cookies;
    }

    /**
     * Current page request
     * 
     * @return Mage_Core_Controller_Request_Http|null
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Sets current page request
     * 
     * @param Mage_Core_Controller_Request_Http $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Applies cookies to be set
     */
    public function apply()
    {
        Mage::dispatchEvent('ecomdev_varnish_cookie_apply_before', array('cookie' => $this));
        
        foreach ($this->getAll() as $cookieName => $data) {
            Mage::getSingleton('core/cookie')->set(
                $cookieName, 
                $data['value'],
                null, 
                null, 
                null, 
                null, 
                $data['http_only']
            );
        }

        Mage::dispatchEvent('ecomdev_varnish_cookie_apply_after', array('cookie' => $this));
    }
}
