<?php

/**
 * Java script wrapper for a template
 */
class EcomDev_Varnish_Block_Js_Wrapper
    extends Mage_Core_Block_Template
{
    /**
     * Sets default template for a placeholder
     *
     */
    protected function _construct()
    {
        $this->setTemplate('ecomdev/varnish/wrapper/placeholder.phtml');
    }

    /**
     * Returns identifier of the javascript block
     *
     * @return string
     */
    public function getWrapperId()
    {
        return $this->getNameInLayout();
    }

    /**
     * Sets block name
     *
     * @param string $blockName
     * @return $this
     */
    public function setBlockName($blockName)
    {
        $this->setData('block_name', $blockName);
        $this->append($this->getLayout()->getBlock($blockName));
        return $this;
    }

    /**
     * Sets a cookie which is a main checksum for an ajax call
     *
     * @param string $cookie
     * @return $this
     */
    public function setCookie($cookie)
    {
        $this->setData('cookie', $cookie);
        return $this;
    }

    /**
     * Returns cookie to check
     *
     * @return bool
     */
    public function getCookie()
    {
        if ($this->hasData('cookie')) {
            return $this->getData('cookie');
        }

        return false;
    }

    /**
     * Sets additional cookie for block
     *
     * @param $cookie
     * @return $this
     */
    public function setAdditionalCookie($cookie)
    {
        $additionalCookie = $this->_getData('_additional_cookie');
        if (!$additionalCookie || !is_array($additionalCookie)) {
            $additionalCookie = array();
        }

        $additionalCookie[$cookie] = true;
        $this->setData('_additional_cookie', $additionalCookie);
        return $this;
    }

    /**
     * Removes additional cookie from block
     *
     * @param string $cookie
     * @return $this
     */
    public function removeAdditionalCookie($cookie)
    {
        $additionalCookie = $this->_getData('_additional_cookie');
        if (!$additionalCookie || !is_array($additionalCookie)) {
            return $this;
        }

        if (isset($additionalCookie[$cookie])) {
            unset($additionalCookie[$cookie]);
        }

        $this->setData('_additional_cookie', $additionalCookie);
        return $this;
    }

    /**
     * Returns additional cookies for js checks
     *
     * @return string[]
     */
    public function getAdditionalCookies()
    {
        $additionalCookie = $this->_getData('_additional_cookie');
        if (!$additionalCookie || !is_array($additionalCookie)) {
            return array();
        }

        return array_keys($additionalCookie);
    }

    /**
     * Checks for supporting block json
     *
     * @return bool
     */
    public function hasBlockJson()
    {
        return true;
    }

    /**
     * Renders child blocks or placeholder template block
     *
     * @return string
     */
    public function renderCachedBlock()
    {
        if ($this->getPlaceholder()) {
            return $this->getLayout()->createBlock('core/template')
                ->setTemplate($this->getPlaceholder())
                ->toHtml();
        }

        return $this->getChildHtml('', false);
    }

    /**
     * Block JSON
     *
     * @return string
     */
    public function getBlockJson()
    {
        return json_encode([
            'container' => $this->getWrapperId(),
            'block' => $this->getBlockName(),
            'cookie' =>  $this->getCookie(),
            'additionalCookies' => $this->getAdditionalCookies()
        ]);
    }
}
