<?php

class EcomDev_Varnish_Model_Message
{
    const XML_PATH_MESSAGE_TYPES = 'varnish/message';
    const COOKIE_NAME = 'varnish_messages';

    /**
     * Message blocks
     *
     * @var EcomDev_Varnish_Block_MessagesInterface[]
     */
    protected $messageBlocks = array();

    /**
     * Message types
     *
     * @var string[]
     */
    protected $messageTypes;

    /**
     * Message types
     *
     * @var string[]
     */
    protected $messageTypeByStorageType;

    /**
     * Scheduled messages
     *
     * @var string[]
     */
    protected $scheduledMessages;

    /**
     * Returns message types for varnish cache
     *
     * @return string[]
     */
    public function getMessageTypes()
    {
        if ($this->messageTypes === null) {
            $this->messageTypes = array();
            $messageTypes = Mage::getConfig()->getNode(self::XML_PATH_MESSAGE_TYPES);
            if ($messageTypes) {
                foreach ($messageTypes->children() as $typeCode => $classAlias) {
                    $this->messageTypes[$typeCode] = (string)$classAlias;
                }
            }
        }

        return $this->messageTypes;
    }

    /**
     * Returns message type by storage
     *
     * @param string $storageType
     * @return string|bool
     */
    public function getMessageTypeByStorage($storageType)
    {
        if ($this->messageTypeByStorageType === null) {
            $types = $this->getMessageTypes();
            if ($types) {
                $this->messageTypeByStorageType = array_combine(
                    array_values($types),
                    array_keys($types)
                );
            } else {
                $this->messageTypeByStorageType = array();
            }
        }

        if (isset($this->messageTypeByStorageType[$storageType])) {
            return $this->messageTypeByStorageType[$storageType];
        }

        return false;
    }

    /**
     * Returns storage by message type
     *
     * @param string $messageType
     * @return Mage_Core_Model_Session_Abstract|bool
     */
    public function getStorageByMessageType($messageType)
    {
        if ($this->messageTypes === null) {
            $this->getMessageTypes();
        }

        if (!isset($this->messageTypes[$messageType])) {
            return false;
        }

        return Mage::getSingleton($this->messageTypes[$messageType]);
    }

    /**
     * Adds message block instance
     *
     * @param EcomDev_Varnish_Block_MessagesInterface $block
     * @return $this
     */
    public function addMessageBlock($block)
    {
        $this->messageBlocks[spl_object_hash($block)] = $block;
        return $this;
    }

    /**
     * Adds messages for a later use
     *
     * @param Mage_Core_Model_Message_Collection|Mage_Core_Model_Message_Abstract[] $messages
     * @param string $storageType
     * @return $this
     */
    public function addMessages($messages, $storageType)
    {
        if ($messages instanceof Mage_Core_Model_Message_Collection) {
            $messages = $messages->getItems();
        }

        $this->scheduledMessages[$storageType] = $messages;
        return $this;
    }

    /**
     * Returns all messages from types passed in argument
     *
     * @return Mage_Core_Model_Message_Collection[]
     */
    public function getMessages($types)
    {
        $result = array();

        foreach ($types as $type) {
            $storage = $this->getStorageByMessageType($type);
            if ($storage) {
                $result[] = $storage->getMessages(true);
            }
        }

        return $result;
    }

    /**
     * Applies changes values stored in message block into session instances
     *
     * @return $this
     */
    public function apply()
    {
        if (!isset($_SESSION) || empty($_SESSION)) {
            return $this;
        }


        foreach ($this->messageBlocks as $messageBlock) {
            foreach ($messageBlock->getMessagesByStorage() as $storageType => $messages) {
                $this->addMessagesByStorageType($storageType, $messages);
            }
        }

        foreach ($this->scheduledMessages as $storageType => $messages) {
            $this->addMessagesByStorageType($storageType, $messages);
        }

        $types = array_keys($this->getMessageTypes());
        $updatedStorages = [];

        foreach ($types as $type) {
            $storage = $this->getStorageByMessageType($type);
            if ($storage && $storage->getMessages(false)->getItems()) {
                $updatedStorages[] = $type;
            }
        }


        if ($updatedStorages) {
            Mage::getSingleton('ecomdev_varnish/cookie')->set(
                self::COOKIE_NAME, implode(',', $updatedStorages)
            );
        }

        return $this;
    }

    /**
     * Adds messages
     *
     * @param string $storageType
     * @param Mage_Core_Model_Message_Abstract[] $messages
     * @return $this
     */
    private function addMessagesByStorageType($storageType, $messages)
    {
        $type = $this->getMessageTypeByStorage($storageType);
        if ($type) {
            $storage = $this->getStorageByMessageType($type);
            if ($storage) {
                $storage->addMessages($messages);
            }
        }

        return $this;
    }
}
