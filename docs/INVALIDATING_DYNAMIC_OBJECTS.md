# Invalidating dynamic objects
Dynamic blocks can be invalidated by matching a cookie value with the previous value stored in client side storage.
This block will have a placeholder in html that gets filled up with javascript.

Below are the steps a dynamic block will go through:
* When a block is loaded for the first time or it gets invalidated, an ajax call will happen.
* After this ajax call, the html of the block will be stored in client side storage.
* On every page that the block is present, the html will be copied from the client side storage to the placeholder using javascript.

The client side storage is either localStorage or sessionStorage.
For browser compatibility, please refer to:
* http://caniuse.com/#search=localstorage for localStorage
* http://caniuse.com/#search=sessionstorage for sessionStorage

The difference between sessionStorage and localStorage lies in persistence.
LocalStorage persists until deleted while sessionStorage exists in a single browser window.

To achieve the functionality that was described above you need to create or leverage a cookie and edit your layout.xml.

## Creating a custom cookie
To create a cookie, you need to adjust your config.xml and create an observer model.
Cookies are added through your own custom events or core events.

```xml
<config>
    <frontend>
        <events>
            <your_model_save_after>
                <observers>
                    <ecomdev_varnish>
                        <class>your_model/observer</class>
                        <method>setDynamicCookie</method>
                    </ecomdev_varnish>
                </observers>
            </your_model_save_after>
        </events>
    </frontend>
</config>
```
```php
<?php

class Your_Module_Model_Observer
    extends EcomDev_Varnish_Model_Customer_Observer
{
    const DYNAMIC_COOKIE = 'dynamic_cookie';

    public function setDynamicCookie($observer)
    {
        $myDynamicData = $observer->getMyDynamicData()->debug();
        $this->setCookies((array(
            self::DYNAMIC_COOKIE => $this->_hashData($myDynamicData)
        )));
    }

}
```
## Adding your block to a layout.xml
```xml
<default_varnish>
    <reference name="parentBlock">
        <action method="unsetChild">
            <block>dynamicBlockAlias</block>
        </action>
        <block as="dynamicBlockAlias" name="dynamicBlockPlaceholder"
               template="ecomdev/varnish/wrapper/placeholder.phtml" type="core/template">
            <action method="append">
                <block>dynamicBlock</block>
            </action>
            <action method="setBlockName">
                <block>dynamicBlock</block>
            </action>
            <action method="setCookie">
                <cookie>dynamic_cookie</cookie>
            </action>
            <action method="setWrapperId">
                <htmlId>elementId</htmlId>
            </action>
        </block>
    </reference>
</default_varnish>
```