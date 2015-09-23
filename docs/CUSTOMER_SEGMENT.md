# Working with Customer Segment
In case if you would like to serve different page variations depending on current visitor environment.

## Customer group based customer segment

This is the most common customer segment and it is enabled by default. 
If you'd like to show the same variation of cached pages for all customer groups, disable `varnish/settings/customer_group_segment` option.

## Store based customer segment

In case if you stores are served from the same url, you need to enable `varnish/settings/store_segment`.

## Currency based customer segment

When you enable `varnish/settings/currency_segment` you must override `core/store` model yourself in order to retrieve value only from cookie, 
as session is not available for cached page visit. 

Recommended overridden method implementation:

```php
/**
 * Returns current currency code from currency cookie instead of session object
 *
 * @return string
 */
public function getCurrentCurrencyCode()
{
    // try to get currently set code among allowed
    $code = Mage::getSingleton('core/cookie')->get('cookie');

    if (empty($code)) {
        $code = $this->getDefaultCurrencyCode();
    }

    if (in_array($code, $this->getAvailableCurrencyCodes(true))) {
        return $code;
    }

    // take first one of allowed codes
    $codes = array_values($this->getAvailableCurrencyCodes(true));
    if (empty($codes)) {
        // return default code, if no codes specified at all
        return $this->getDefaultCurrencyCode();
    }

    return array_shift($codes);
}
```
