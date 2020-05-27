import {setCookie, getCookie} from "./cookie";
import setHtmlValue from "./set-html";
class Loader {
    constructor(container, messageTypes, url, fetchFunction) {
        this.container = container;
        this.messageTypes = messageTypes;
        this.fetchFunction = fetchFunction;

        this.matchHashMap = messageTypes.reduce(
            (types, type) => {
                types[type] = true;
                return types;
            },
            {}
        );

        this.url = url;
    }

    claimTypes(typesToMatch) {
        return this.messageTypes.reduce(
            ([loadTypes, remainingTypes], type) => {
                const typeIndex = remainingTypes.indexOf(type);
                if (typeIndex !== -1) {
                    loadTypes.push(type);
                    remainingTypes.splice(typeIndex, 1);
                }

                return [loadTypes, remainingTypes];
            },
            [[], typesToMatch]
        )
    }

    loadMessages(messageTypes) {
        const formData = new FormData();
        formData.append('storage', messageTypes.join(','));

        return this.fetchFunction(
            new Request(this.url, {method: 'POST', body: formData})
        ).then((response) => {
            return response.text();
        }).then((html) => {
            setHtmlValue(this.container, html);
        });
    }
}

export default class {
    constructor(fetchFunction) {
        this.fetchFunction = fetchFunction;
        this.loaders = [];
        this.cookieName = '';
        this.cookieOptions = {};
    }

    registerLoader(config) {
        this.loaders.push(new Loader(
            config.container,
            config.types,
            config.url,
            this.fetchFunction
        ));

        if (config.cookieName) {
            this.cookieName = config.cookieName;
        }

        if (typeof config.cookieOptions === 'object') {
            this.cookieOptions = config.cookieOptions;
        }
    }

    processMessages() {
        if (!getCookie(this.cookieName)) {
            return Promise.resolve(0);
        }

        const messageTypes = getCookie(this.cookieName).split(',').reduce((types, value) => {
            value = value.trim();
            if (value.length) {
                types.push(value);
            }

            return types;
        }, []);

        const loadersToProcess = [];

        const remainingTypes = this.loaders.reduce((types, loader) => {
            const [loadTypes, remainingTypes] = loader.claimTypes(types);

            if (loadTypes.length) {
                loadersToProcess.push([loader, loadTypes]);
            }

            return remainingTypes;
        }, messageTypes);

        const expires = new Date();
        expires.setHours(expires.getHours() + 2);

        setCookie(this.cookieName, remainingTypes.join(','), Object.assign(
            {}, this.cookieOptions, {expires}
        ));

        return Promise.all(
                loadersToProcess.map(([loader, messageTypes]) => loader.loadMessages(messageTypes))
            )
            .then((updatedTypes) => updatedTypes.length)
            .catch(() => 0);
    }
}