# Customization
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

## Page lifetimes

## Collectors

## Processors