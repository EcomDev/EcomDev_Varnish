import setHtmlValue from './set-html';

class Block {
    constructor(blockId, container, storage) {
        this.blockId = blockId;
        this.container = container;
        this.storage = storage;
    }

    process(dataLoader) {
        if (this.storage.isIgnored()) {
            return;
        }

        return this.storage.cachedValue(() => {
            return dataLoader(this.blockId);
        }).then((content) => {
            setHtmlValue(this.container, content);
        });
    }
}

export default class {
    constructor(storageClass, fetch) {
        this.blocks = {};
        this.storageClass = storageClass;
        this.fetch = fetch;
        this.loadUrl = false;
    }

    registerBlock(config) {
        if (!Array.isArray(config.additionalCookies)) {
            config.additionalCookies = [];
        }

        this.blocks[config.block] = new Block(
            config.block,
            config.container,
            new this.storageClass(config.block, config.cookie, config.additionalCookies)
        );
    }

    registerLoader(config) {
        this.loadUrl = config.url;
    }

    processBlocks() {
        if (!this.loadUrl) {
            return Promise.resolve(0);
        }

        const blocksToNotify = {};
        const blocksToLoad = [];

        const wait = [];

        for (let blockId in this.blocks) {
            wait.push(this.blocks[blockId].process((id) => {
                blocksToLoad.push(id);
                return new Promise((resolve, reject) => {
                    blocksToNotify[id] = [resolve, reject];
                });
            }));
        }

        if (!blocksToLoad.length) {
            return Promise.all(wait).then(() => blocksToLoad.length);
        }

        const formFields = new FormData();
        formFields.set('blocks', blocksToLoad.join(','));

        const dataLoader = this.fetch(new Request(this.loadUrl, {method: 'POST', body: formFields}))
            .then((response) => {
                if (response.status === 200) {
                    return response.json();
                }
                return {};
            })
            .then((blocks) => {
                for (let blockId in blocksToNotify) {
                    if (blocks[blockId]) {
                        blocksToNotify[blockId][0](blocks[blockId]);
                        continue;
                    }

                    blocksToNotify[blockId][1](blocks[blockId]);
                }
            });

        wait.push(dataLoader);

        return Promise.all(wait).then(() => blocksToLoad.length);
    }
}