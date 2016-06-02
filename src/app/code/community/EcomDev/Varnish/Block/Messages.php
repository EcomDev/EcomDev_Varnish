<?php

class EcomDev_Varnish_Block_Messages
    extends Mage_Core_Block_Messages
    implements EcomDev_Varnish_Block_MessagesInterface
{
    /**
     * List of message by storage type
     *
     * @var Mage_Core_Model_Message_Abstract[][]
     */
    protected $storageMessages = array();

    /**
     * We register our messages block to message model,
     * so after page is rendered messages are stored, if there any
     *
     * @return $this
     */
    public function _prepareLayout()
    {
        $this->getVarnishMessageModel()->addMessageBlock($this);
        return $this;
    }

    /**
     * Message model
     *
     * @return EcomDev_Varnish_Model_Message
     */
    public function getVarnishMessageModel()
    {
        return Mage::getSingleton('ecomdev_varnish/message');
    }

    /**
     * Returns JS that is required to load messages
     *
     * @return  string
     */
    public function getGroupedHtml()
    {
        $name = $this->getNameInLayout();
        $htmlId = uniqid($name);

        $types = [];
        foreach ($this->_usedStorageTypes as $type) {
            $type = $this->getVarnishMessageModel()->getMessageTypeByStorage($type);
            if (!$type) {
                continue;
            }
            $types[] = $type;
        }

        return sprintf(
            $this->getMessageContainerFormat(),
            json_encode($htmlId),
            json_encode($this->getUrl('varnish/ajax/message', array(
                '_secure' => Mage::app()->getStore()->isCurrentlySecure()
            ))),
            json_encode($types),
            json_encode(EcomDev_Varnish_Model_Message::COOKIE_NAME),
            json_encode(array(
                'path' => Mage::getSingleton('core/cookie')->getPath(),
                'domain' => Mage::getSingleton('core/cookie')->getDomain()
            ))
        );
    }

    public function getMessageContainerFormat()
    {
        if (!$this->hasData('message_container_format')) {
            $this->setData(
                'message_container_format',
                '<div id=%1$s></div>'
                . '<script type="text/javascript"> '
                . 'ecomdevVarnishScope(function () {'
                . 'new EcomDev.Varnish.Messages(%1$s, %2$s, %3$s, %4$s, %5$s)'
                . '})'
                . '</script>'
            );
        }
        return $this->_getData('message_container_format');
    }

    /**
     * Returns list of
     *
     * @return string[][]
     */
    public function getMessagesByStorage()
    {
        return $this->storageMessages;
    }

    /**
     * Add used storage type
     *
     * @param string $type
     * @return $this
     */
    public function addStorageType($type)
    {
        parent::addStorageType($type);

        if (!is_string($type)) {
            return $this;
        }

        foreach ($this->getMessageCollection()->getItems() as $item) {
            $this->storageMessages[$type][] = $item;
        }

        $this->getMessageCollection()->clear();
        return $this;
    }
}
