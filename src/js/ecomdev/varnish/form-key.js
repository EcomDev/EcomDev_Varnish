import {getCookie, setCookie} from './cookie'

function generateFormKey() {
    return [...Array(16)]
        .map(function() { return Math.floor(Math.random() * 36).toString(36); })
        .join('');
}

function escapeRegexpValue(value) {
    return value.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
}

function replaceFormKeyInInputs(inputName, formKey) {
    for (let item of document.getElementsByName(inputName)) {
        item.value = formKey;
    }
}

function replaceFormKeyInTheUrl(url, urlKeyParam, formKey) {
    return url.replace(
        new RegExp('(/' + escapeRegexpValue(urlKeyParam) + '/)[^/]+', 'g'),
        '$1' + formKey
    );
}

function substituteFormKeyInFunctionCall(functionName, urlKeyParam, formKeyGetter) {
    const originalFunction = window[functionName];

    window[functionName] = function () {
        const args = [... arguments];
        const url = replaceFormKeyInTheUrl(args.shift(), urlKeyParam, formKeyGetter());
        args.unshift(url);

        return originalFunction.apply(window, args);
    };
}

function replaceFormKeyInLinks(linkSelector, urlKeyParam, formKey) {
    for (let item of document.querySelectorAll(linkSelector)) {
        let linkUrl = item.getAttribute("href");
        if (!linkUrl) {
            continue;
        }

        item.setAttribute(
            "href",
            replaceFormKeyInTheUrl(linkUrl, urlKeyParam, formKey)
        );
    }
}

export default class {
    constructor() {
        this.cookieName = '';
        this.cookieOptions = {};
        this.linkCssSelectors = [];
        this.inputName = '';
        this.urlKeyParam = '';
        this.variableExports = [];
    }

    formKey() {
        if (!this.cookieName) {
            return '';
        }

        const formKey = getCookie(this.cookieName) || generateFormKey();

        setCookie(this.cookieName, formKey, this.cookieOptions);

        return formKey;
    }


    processConfig(config) {
        this.cookieName = config.cookieName;
        this.cookieOptions = config.cookieOptions;
        this.inputName = config.inputFieldName;
        this.urlKeyParam = config.urlKeyParam;

        if (Array.isArray(config.observedCssRules)) {
            this.linkCssSelectors = config.observedCssRules;
        }

        if (config.locationFunction && config.urlKeyParam) {
            substituteFormKeyInFunctionCall(
                config.locationFunction, config.urlKeyParam, () => this.formKey()
            );
        }
    }

    processExport(config) {
        this.variableExports.push(config.variableName);
    }

    replaceValues() {
        const formKey = this.formKey();

        if (this.inputName) {
            replaceFormKeyInInputs(this.inputName, formKey);
        }

        const urlKeyParam = this.urlKeyParam;

        if (urlKeyParam) {
            for (let item of document.querySelectorAll('form[action*="' + urlKeyParam + '"]')) {
                item.action = replaceFormKeyInTheUrl(item.action, urlKeyParam, formKey);
            }
        }

        if (this.linkCssSelectors.length) {
            for (let linkSelector of this.linkCssSelectors) {
                replaceFormKeyInLinks(linkSelector, urlKeyParam, formKey);
            }
        }

        for (let variableName of this.variableExports) {
            window[variableName] = formKey;
        }
    }
}