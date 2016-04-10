# Adding Custom Pages
To know what page a certain element is in, this extension adds a "X-Cache-Objects" header.
This header contains tags (TAGNAME + Object ID) that describe the contents of each page.
This allows the extension to ban by tag instead of url and create a "content aware" banning system.

Example:
```
X-Cache-Objects:
:CMSBblock_home_preface:,:P24264:,:P24908:,:P24121:,:P24339:,:P24810:,:CMSBblock_home_postscript:
```
Where P is a product with ID x.

To add this system to your own extension (for example a CRUD), you need to add the following things:

## Pages
By default, the extension caches the following pages:
* Category view & Layered navigation
* Product view
* Cms pages (including home pages)

To add your own page, you need to adjust your config.xml:
```
<config>
    <varnish>
        <pages>
            <page_layout_handle translate="label" module="you_module_name">
                <label>
                    Page Name
                </label>
            </page_layout_handle>
        </pages>
    </varnish>
</config>
```
The page_layout_handle element name is the name of your custom page. (for example blog_index_index)
When in doubt, refer to the first class in your page body and replace the dashes with underscores.

## Page lifetimes
For every page you add, you also need to define a TTL.
If you do not do this, your TTL will be 0 and Varnish will still pass the request.

To add a certain TTL, you need to adjust your config.xml:
```
<config>
    <default>
        <varnish>
            <pages>
                <page_layout_handle_time>360</page_layout_handle_time>
            </pages>
        </varnish>
    </default>
</config>
```

## Collectors
A collector collects objects on page load and extracts tags from those objects.
These tags are added to the "X-Cache-Objects" header.

To create a collector, you need to add a new model and you need to adjust your config.xml.
```
<?php

class Your_Module_Model_Collector
    extends EcomDev_Varnish_Model_AbstractApplicable
    implements EcomDev_Varnish_Model_CollectorInterface
{
    protected $_applicableClasses = array(
        'Your_Module_Block_Object'
    );

    public function collect($block) {
        // Your logic to retrieve objects
        return $objects;
    }
}
```
```
<config>
    <varnish>
        <object>
            <collectors>
                <your_object>
                    your_module/collector
                </your_object>
            </collectors>
        </object>
    </varnish>
</config>
```

## Processors
A processor hooks to a model save event and adds a list of object tags to the ban queue.

To create a processor, you also need to add a new model and adjust your config.xml.
```
<?php

class Your_Module_Model_Processor
    extends EcomDev_Varnish_Model_AbstractProcessor
{
    const TAG_PREFIX = 'YM';

    protected $_applicableClasses = array(
        'Your_Module_Model_Object'
    );

    protected function _collectTags($object)
    {
        return self::TAG_PREFIX . $object->getId();
    }
}
```
```
<config>
    <varnish>
        <object>
            <processors>
                <your_object>
                    your_module/processor
                </your_object>
            </processors>
        </object>
    </varnish>
</config>
```