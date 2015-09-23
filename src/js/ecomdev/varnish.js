/**
 * Varnish extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_Varnish
 * @copyright  Copyright (c) 2015 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

if (!window.EcomDev) {
    window.EcomDev = {};
}

/**
 * Storage wrapper for session, local storage
 * @type {*}
 */
EcomDev.Storage = Class.create({
    initialize: function (namespace) {
        this.namespace = namespace;
    },
    
    /**
     * Returns false if storage is not available 
     * 
     * @returns {Boolean}
     */
    isAvailable: function () {
        return this.getStorage() !== false;
    },

    /**
     * Returns a storage if it is available,
     * otherwise returns false
     *  
     * @returns {Storage|Boolean}
     */
    getStorage: function () {
        if (window.localStorage) {
            return window.localStorage;
        } else if (window.sessionStorage) {
            return window.sessionStorage;
        }
        
        return false;
    },
    setValue: function (name, value) {
        var isSerialized = '0';
        if (!Object.isString(value)) {
            value = Object.toJSON(value);
            isSerialized = '1';
        }
        
        if (this.isAvailable()) {
            this.getStorage().setItem(
                this.namespace + '_' + name, 
                value
            );
            this.getStorage().setItem(
                this.namespace + '_is_serialized_' + name,
                isSerialized
            )
        } 
        
        return this;
    },

    /**
     * Retrieves a value from storage
     * 
     * @param name
     * @returns {*}
     */
    getValue: function (name) {
        if (this.isAvailable()) {
            var isSerialized = this.getStorage().getItem(
                this.namespace + '_is_serialized_' + name
            );
            
            if (isSerialized !== '' && isSerialized !== undefined) {
                isSerialized = parseInt(isSerialized);
                var value = this.getStorage().getItem(this.namespace + '_' + name);
                if (isSerialized && value) {
                    value = value.evalJSON();
                }
                return value;
            }
        }
        
        return false;
    }
});

EcomDev.Storage.instances = $H({});

EcomDev.Storage.instance = function (namespace) {
    if (!this.instances.has(namespace)) {
        this.instances.set(namespace, new EcomDev.Storage(namespace));
    }

    return this.instances.get(namespace);
};


EcomDev.Varnish = {};

EcomDev.Varnish.AjaxBlock = Class.create({
    /**
     * @type {EcomDev.Storage}
     */
    storage: {},
    initialize: function (config) {
        this.config = config;
        this.storage = new EcomDev.Storage(config.block);
        if ($(config.container)) {
            // Invoke dom initialization directly if possible
            this.initDom();
        } else {
            EcomDev.Varnish.AjaxBlock.addInitCallback(this.initDom.bind(this));
        }
        EcomDev.Varnish.AjaxBlock.addAjaxBlock(config.block, this);
    },
    initDom: function () {
        this.container = $(this.config.container);
        if (this.validate() && this.storage.getValue('html')) {
            this.container.update(this.storage.getValue('html'));
        }
    },
    update: function (content) {
        this.storage.setValue('cookie', this.getCookieValue());
        this.storage.setValue('html', content);
        this.container.update(content);
        if (this.config.callback) {
            if (Object.isFunction(this.config.callback)) {
                this.config.callback();
            } else {
                window[this.config.callback]();
            }
        }
    },
    validate: function () {
        if (!this.config.cookie) {
            return true;
        }
        
        var currentValue = this.getCookieValue();
        
        if (currentValue === false && !this.storage.getValue('cookie')) {
            return true;
        }
        
        return this.storage.getValue('cookie') === currentValue;
    },
    /**
     * Returns cookie value
     * 
     * @returns {Boolean|String}
     */
    getCookieValue: function () {
        if (this.config.cookie) {
            return Mage.Cookies.get(this.config.cookie);
        }
        
        return false;
    }
});

/** Static methods **/
Object.extend(EcomDev.Varnish.AjaxBlock, {
    initCallbacks: [],
    ajaxBlocks: $H({}),
    url: false,
    addInitCallback: function (callback) {
        if (Object.isFunction(callback)) {
            this.initCallbacks.push(callback);
        }
    },
    addAjaxBlock: function (name, block) {
        if (block instanceof EcomDev.Varnish.AjaxBlock) {
            this.ajaxBlocks.set(name, block);
        }
    },
    onDomReady: function () {
        for (var i = 0, l=this.initCallbacks.length; i < l; i ++) {
            this.initCallbacks[i]();
        }
        
        this.processBlocks();
    },
    processBlocks: function () {
        var allBlocks = this.ajaxBlocks.keys();
        var reloadBlocks = [];
        for (var i = 0, l=allBlocks.length; i < l; i ++) {
            var block = this.ajaxBlocks.get(allBlocks[i]);
            if (!block.validate(false)) {
                reloadBlocks.push(allBlocks[i]);
            }
        }
        
        if (reloadBlocks.length > 0 && this.url) {
            new Ajax.Request(this.url, {
                parameters: {
                    blocks: reloadBlocks.join(',')
                },
                method: 'POST',
                onComplete: this.updateBlocks.bind(this)
            });
        }
    },
    updateBlocks: function (response) {
        if (response.responseText.isJSON()) {
            var result = response.responseText.evalJSON();
            var blocks = Object.keys(result);
            for (var i= 0, l=blocks.length; i < l; i ++) {
                var block = this.ajaxBlocks.get(blocks[i]);
                block.update(result[blocks[i]]);
            }
        }
    }
});

EcomDev.Varnish.Esi = Class.create({
    /**
     * @type {EcomDev.Storage}
     */
    storage: {},
    initialize: function (config) {
        this.config = config;
        this.storage = new EcomDev.Storage(this.config.container);
        this.isEmulated = false;
        if ($(this.config.container)) {
            // Invoke dom initialization directly if possible
            this.emulate();
        }
        EcomDev.Varnish.Esi.addInstance(this);  
    },
    emulate: function () {
        if (this.isEmulated) {
            return;
        }

        this.container = $(this.config.container);
        
        if (this.storage.getValue('html')) {
            this.container.update(this.storage.getValue('html'));
        } else {
            new Ajax.Request(this.config.url, {
                onComplete: function (response) {
                    this.storage.setValue('html', response.responseText);
                    this.container.update(response.responseText);
                }
            });
        }
        
        this.isEmulated = true;
    }
});

Object.extend(EcomDev.Varnish.Esi, {
    index: 0,
    instances: [],
    addInstance: function (instance) {
        this.instances.push(instance);
    },
    emulate: function () {
        for (var i= 0, l=this.instances.length; i < l; i ++) {
            this.instances[i].emulate();
        }
    }
});

EcomDev.Varnish.Url = Class.create({
    initialize: function (url, query) {
        this.url = url;
        this.query = query;
        this.params = $H({});
        this.parse();
    },
    parse: function () {
        if (this.query) {
            var params = this.query.substring(1).split('&');
            for (var i = 0, l = params.length; i < l; i++) {
                var parts = params[i].split('=');
                if (parts.length == 2) {
                    this.params.set(decodeURIComponent(parts[0]), decodeURIComponent(parts[1]));
                } else if (parts.length == 1) {
                    this.params.set(decodeURIComponent(parts[0]), true);
                }
            }
        }
        return this;
    },
    reloadWithout: function (param) {
        var query = '';
        this.params.each(function (pair) {
            if (Object.isArray(param) && param.indexOf(pair.key) !== -1) {
                return;
            } else if (pair.key == param) {
                return;
            }
            
            query += encodeURIComponent(pair.key) + '=' + encodeURIComponent(pair.value);
        });
        var url = this.url;
        if (query.length > 0) {
            url += '?' + query;
        } 
        window.location.href = url;
    }
});

/**
 * Token class for form key validation
 *
 * It is used to validate form key validator
 */
EcomDev.Varnish.Token = Class.create({
    initialize: function (config) {
        if (!Object.isArray(config.observedCssRules)) {
            config.observedCssRules = [config.observedCssRules];
        }

        this.urlKeyParam = config.urlKeyParam;
        this.cookieName = config.cookieName;
        this.requestUrl = config.requestUrl;
        this.observedCssRules = config.observedCssRules;
        this.inputFieldName = config.inputFieldName;
        this.overrideSetLocation();
        this.requireTokenValue();
    },
    overrideSetLocation: function () {
        if (window.setLocation && !window.EcomDevOriginalSetLocation) {
            window.EcomDevOriginalSetLocation = window.setLocation;
            window.setLocation = this.setLocation.bind(this);
        }
    },
    getTokenValue: function () {
        return Mage.Cookies.get(this.cookieName);
    },
    requireTokenValue: function () {
        var require = false;
        for (var i = 0; i < this.observedCssRules.length; i++) {
            if ($$(this.observedCssRules[i]).length) {
                require = true;
            }
        }

        if (document.getElementsByName(this.inputFieldName).length) {
            require = true;
        }

        if (require) {
            if (this.getTokenValue()) {
                this.updateFormFields();
            } else {
                new Ajax.Request(
                    this.requestUrl, {
                        method: 'post',
                        onComplete: this.updateFormFields.bind(this)
                    }
                );
            }
        }
    },
    updateFormFields: function () {
        var formFields = document.getElementsByName(this.inputFieldName);
        for (var i = 0; i < formFields.length; i ++) {
            formFields[i].value = this.getTokenValue();
        }
    },
    setLocation: function (url) {
        if (url.match('/' + this.urlKeyParam) + '/') {
            url = url.replace(
                new RegExp('/' + RegExp.escape(this.urlKeyParam) + '/[^/]/', 'g'),
                '/' + this.urlKeyParam + '/' + this.getTokenValue() + '/'
            );
        }

        window.EcomDevOriginalSetLocation(url);
    }
});

EcomDev.Varnish.currentUrl = new EcomDev.Varnish.Url(window.location.pathname, window.location.search);

Object.extend(EcomDev.Varnish, {
    onDomReady: function () {
        var keys = Object.keys(this).without('onDomReady');
        for (var i= 0, l=keys.length; i < l; i ++) {
            if (this[keys[i]].onDomReady) {
                this[keys[i]].onDomReady();
            }
        }
    }
});

document.observe(
    'dom:loaded', 
    EcomDev.Varnish.onDomReady.bind(EcomDev.Varnish)
);

