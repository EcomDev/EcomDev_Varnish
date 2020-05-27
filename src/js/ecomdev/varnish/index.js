import Page from './page';
import DynamicBlockManager from'./dynamic-block';
import CacheStorage from './cache';
import FormKey from './form-key';
import Messages from './messages';

function initializeLibrary() {
    const page = new Page('ecomdev/varnish-element');
    const block = new DynamicBlockManager(CacheStorage, fetch.bind(window));
    const formKey = new FormKey();
    const messages = new Messages(fetch.bind(window));
    const pageInfo = {};

    page.elementType('block', block.registerBlock.bind(block));
    page.elementType('block-loader', block.registerLoader.bind(block));
    page.elementType('messages', messages.registerLoader.bind(messages));
    page.elementType('form-key', formKey.processConfig.bind(formKey));
    page.elementType('form-key-export', formKey.processExport.bind(formKey));
    page.elementType('page-info',  (info) => {
        Object.assign(pageInfo, info);
    })

    function processDynamicPageUpdate() {
        block.processBlocks().then(() => {
            formKey.replaceValues();
            return messages.processMessages();
        });
    }

    (function(open) {
        XMLHttpRequest.prototype.open = function(XMLHttpRequest) {
            this.addEventListener("readystatechange", function() {
                if (this.responseText.length > 0 && this.readyState === 4) {
                    setTimeout(processDynamicPageUpdate, 50);
                }
            }, false);
            open.apply(this, arguments);
        };
    })(XMLHttpRequest.prototype.open);

    document.addEventListener('DOMContentLoaded', function () {
        page.processDocument();
        processDynamicPageUpdate();
    });
    return {
        CacheStorage,
        Page: page,
        DynamicBlock: block,
        FormKey: formKey,
        Messages: messages,
        PageInfo: pageInfo,
        update: processDynamicPageUpdate
    }
}

export default initializeLibrary();
