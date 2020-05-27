import Block from './dynamic-block';
import {spyFetch, fakeFetch} from './__fake_request';

function ignoredCache(ignoredIds) {
    const cache = cacheWithValues({});

    return class {
        constructor(cacheId) {
            this.cacheId = cacheId;
            this.fallbackCache = new cache(cacheId);
        }

        isIgnored() {
            return ignoredIds.indexOf(this.cacheId) > -1;
        }

        cachedValue(fetchCallback) {
            if (this.isIgnored()) {
                return Promise.resolve('It should be ignored');
            }

            return this.fallbackCache.cachedValue(fetchCallback);
        }
    };
}

function cacheWithValues(values) {
    return class {
        constructor(cacheId) {
            this.cacheId = cacheId;
        }

        isIgnored() {
            return false;
        }

        cachedValue(fetchData) {
            if (values[this.cacheId]) {
                return Promise.resolve(values[this.cacheId]);
            }

            return fetchData().then((v) => v, () => 'Failed');
        }
    };
}

function spyCacheConstruction() {
    const constructorArguments = [];

    return {
        constructorArguments: () => {
            return constructorArguments;
        },
        cacheClass: class {
            constructor(cacheId, requiredCookie, additionalCookies) {
                constructorArguments.push([cacheId, requiredCookie, additionalCookies]);
            }
        }
    }
}

function elementContent(id) {
    return document.getElementById('element-' + id).innerHTML;
}

beforeEach(() => {
    // Body fixture
    document.body.innerHTML = `
        <div id="element-1">Placeholder 1</div>
        <div id="element-2">Placeholder 2</div>
        <div id="element-3">Placeholder 3</div>
        <div id="element-4">Placeholder 4</div>
    `;
});

/** @type {Block} */
let block;
let storage;

describe('Creation of blocks', () => {
    beforeEach(() => {
        storage = spyCacheConstruction();
        block = new Block(storage.cacheClass, fakeFetch());
    });

    it('registers storage for each detected block', () => {
        block.registerBlock({
            block: 'one',
            container: 'element-1',
            cookie: 'cookie1',
            additionalCookies: []
        });

        block.registerBlock({
            block: 'two',
            container: 'element-1',
            cookie: 'cookie2',
            additionalCookies: ['cookie1']
        });

        expect(storage.constructorArguments()).toEqual([
            ['one', 'cookie1', []],
            ['two', 'cookie2', ['cookie1']]
        ]);
    })

    it('fixes invalid type for additionalCookies', () => {
        block.registerBlock({
            block: 'one',
            container: 'element-1',
            cookie: 'cookie1',
            additionalCookies: {}
        });

        block.registerBlock({
            block: 'two',
            container: 'element-1',
            cookie: 'cookie2'
        });

        expect(storage.constructorArguments()).toEqual([
            ['one', 'cookie1', []],
            ['two', 'cookie2', []]
        ]);
    })
});

describe('Fetch uncached blocks', () => {
    let fetchSpy;

    beforeEach(() => {
        fetchSpy = spyFetch();
        block = new Block(ignoredCache(['block4', 'block2']), fetchSpy.fetch);
        block.registerBlock({block: 'block1', container: 'element-1'});
        block.registerBlock({block: 'block2', container: 'element-2'});
        block.registerBlock({block: 'block3', container: 'element-3'});
        block.registerBlock({block: 'block4', container: 'element-4'});
        block.registerLoader({url: 'http://somewebsite.com/block'});
    });

    it ('nothing is updated when url is not provided', async () => {
        block.registerLoader({url: false});

        expect(await block.processBlocks()).toEqual(0);
    })

    it ('notifies about number of processed blocks', async () => {
        expect(await block.processBlocks()).toEqual(2);
    })

    it ('requests data from backend only for processed blocks', async () => {
        await block.processBlocks();

        expect(fetchSpy.fetchedRequests()).toEqual([
            new Request('http://somewebsite.com/block', {
                method: 'POST',
                body: new FormData({blocks: 'block1,block3'})
            })
        ])
    })

    it ('updates fetched blocks in dom', async () => {
        await block.processBlocks();

        expect(elementContent(1)).toBe('Failed');
        expect(elementContent(3)).toBe('Failed');
    });

    it ('does not update ignored blocks', async () => {
        await block.processBlocks();

        expect(elementContent(2)).toBe('Placeholder 2');
        expect(elementContent(4)).toBe('Placeholder 4');
    })
});

describe('Fetch cached blocks', () => {
    beforeEach(() => {
        block = new Block(
            cacheWithValues({
                block1: 'Cached Block 1',
                block2: 'Cached Block 2',
                block4: 'Cached Block 4'
            }),
            fakeFetch(
                {
                    block1: 'Server Block 1',
                    block2: 'Server Block 2',
                    block3: 'Server Block 3',
                    block4: 'Server Block 4'
                },
                'blocks'
            )
        );

        block.registerBlock({block: 'block1', container: 'element-1'});
        block.registerBlock({block: 'block2', container: 'element-2'});
        block.registerBlock({block: 'block3', container: 'element-3'});
        block.registerBlock({block: 'block4', container: 'element-4'});
        block.registerLoader({url: 'http://somewebsite.com/block'});
    })


    it ('updates cached block from cache', async () => {
        await block.processBlocks();

        expect(elementContent(1)).toBe('Cached Block 1');
        expect(elementContent(2)).toBe('Cached Block 2');
        expect(elementContent(4)).toBe('Cached Block 4');
    })

    it ('updates not-cached block from server', async () => {
        await block.processBlocks();

        expect(elementContent(3)).toBe('Server Block 3');
    })
})