export default class {
    constructor(scriptType) {
        this.elementTypes = {};
        this.scriptType = scriptType;
    }

    elementType(type, factory) {
        this.elementTypes[type] = factory;
    }

    processDocument() {
        const elements = document.getElementsByTagName('script');
        for (let i = 0; i < elements.length; i ++) {
            let element = elements[i];
            let elementType = element.getAttribute('type');

            if (elementType !== this.scriptType
                || !element.dataset.type
                || !this.elementTypes[element.dataset.type]
                || element.pageIsProcessed) {
                continue;
            }

            element.pageIsProcessed = true;

            try {
                let elementArguments = JSON.parse(element.innerHTML);
                this.elementTypes[element.dataset.type](elementArguments);
            } catch (e) {
                notifyError(e);
                notifyError(element.innerHTML);
            }

        }
    }
}

function notifyError(e) {
    if (console && console.warn) {
        console.warn(e);
    }
}