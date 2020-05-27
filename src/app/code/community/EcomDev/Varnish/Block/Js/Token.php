<?php

/**
 * Token block for setting up a token script params
 *
 */
class EcomDev_Varnish_Block_Js_Token
    extends Mage_Core_Block_Template
{
    /**
     * List of observed css rules
     *
     * @var string[]
     */
    protected $observedCssRules = array();

    /**
     * Returns url for tokens
     *
     * @return string
     */
    public function getTokenUrl()
    {
        return $this->getUrl(
            'varnish/ajax/token',
            array('_secure' => Mage::app()->getStore()->isCurrentlySecure())
        );
    }

    /**
     * Sets token input name
     *
     * @param string|null $tokenInputName
     * @return $this
     */
    public function setTokenInputName($tokenInputName = null)
    {
        if ($tokenInputName === null) {
            $tokenInputName = 'form_key';
        }

        $this->setData('token_input_name', $tokenInputName);
        return $this;
    }

    /**
     * Returns token input name
     *
     * @return string
     */
    public function getTokenInputName()
    {
        if (!$this->hasData('token_input_name')) {
            $this->setTokenInputName();
        }
        return $this->_getData('token_input_name');
    }

    /**
     * Adds observed css rule
     *
     * @param string $rule
     * @return $this
     */
    public function addObservedCssRule($rule)
    {
        $this->observedCssRules[$rule] = $rule;
        return $this;
    }

    /**
     * Removes observed css rule
     *
     * @param string $rule
     * @return $this
     */
    public function removeObservedCssRule($rule)
    {
        if (isset($this->observedCssRules[$rule])) {
            unset($this->observedCssRules[$rule]);
        }

        return $this;
    }

    /**
     * Returns css rule list
     *
     * @return string[]
     */
    public function getObservedCssRules()
    {
        return array_values($this->observedCssRules);
    }

    /**
     * Returns url key param name
     *
     * @return string
     */
    public function getUrlKeyParam()
    {
        if (!$this->hasData('url_key_param')) {
            $this->setUrlKeyParam();
        }

        return $this->_getData('url_key_param');
    }

    /**
     * Sets url key param name
     *
     * @param string|null $urlKeyParam
     * @return $this
     */
    public function setUrlKeyParam($urlKeyParam = null)
    {
        if ($urlKeyParam === null) {
            $urlKeyParam = $this->getTokenInputName();
        }

        $this->setData('url_key_param', $urlKeyParam);
        return $this;
    }

    /**
     * Returns cookie name for token retrieval
     *
     * @return string
     */
    public function getCookieName()
    {
        if (!$this->hasData('cookie_name')) {
            $this->setCookieName();
        }

        return $this->_getData('cookie_name');
    }

    /**
     * Sets cookie name for token retrieval
     *
     * @param string|null $cookieName
     * @return $this
     */
    public function setCookieName($cookieName = null)
    {
        if ($cookieName === null) {
            $cookieName = EcomDev_Varnish_Helper_Data::COOKIE_TOKEN;
        }

        $this->setData('cookie_name', $cookieName);
        return $this;
    }

    /**
     * Returns json for JavaScript class
     *
     * @return string
     */
    public function getJson()
    {
        $json = [
            'urlKeyParam' => $this->getUrlKeyParam(),
            'inputFieldName' => $this->getTokenInputName(),
            'observedCssRules' => $this->getObservedCssRules(),
            'cookieName' => $this->getCookieName(),
            'locationFunction' => 'setLocation',
            'cookieOptions' => [
                'path' => Mage::getSingleton('core/cookie')->getPath(),
                'domain' => Mage::getSingleton('core/cookie')->getDomain()
            ]
        ];

        return json_encode($json);
    }
}
