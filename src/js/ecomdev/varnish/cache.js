import {getCookie} from "./cookie";

class FallbackStorage {
    constructor() {
        this.data = {};
    }

    getItem(id) {
        return this.data[id] !== undefined ? this.data[id] : null;
    }

    setItem(id, value) {
        this.data[id] = value;
    }
}

function accessStorage() {
    if (window.localStorage) {
        return window.localStorage;
    }
    if (window.sessionStorage) {
        return window.sessionStorage;
    }

    if (!window.fallbackStorage) {
        window.fallbackStorage = new FallbackStorage();
    }

    return window.fallbackStorage;
}

const storage = accessStorage();

function readFromCache(identifier) {
    return storage.getItem(cacheIdentifier(identifier));
}

function writeToCache(identifier, value) {
    storage.setItem(cacheIdentifier(identifier), value);
}

function cacheIdentifier(identifier) {
    return 'cache-storage-' + identifier;
}

function metaIdentifier(identifier) {
    return 'meta-' + cacheIdentifier(identifier);
}

function saveMetadata(identifier, cookieName, additionalCookies, ttl) {
    storage.setItem(
       metaIdentifier(identifier),
       JSON.stringify({
           cookieName,
           expires: (new Date(Date.now() + (ttl * 1000))).toUTCString(),
           additionalCookies: additionalCookies.reduce(
               (values, cookie) => {
                   const cookieValue = getCookie(cookie);
                   values[cookie] = cookieValue ? cookieValue : '';
                   return values;
               },
               {}
           ),
           cookieValue: getCookie(cookieName)
       })
    );
}

function validateMetadata(identifier) {
    const metadata = JSON.parse(storage.getItem(metaIdentifier(identifier)));
    if (typeof metadata !== 'object') {
        return false;
    }

    if (getCookie(metadata.cookieName) !== metadata.cookieValue) {
        return false;
    }

    for (let cookieName in metadata.additionalCookies) {
        if (metadata.additionalCookies[cookieName] !== getCookie(cookieName)) {
            return false;
        }
    }

    if (Date.parse(metadata.expires) < Date.now()) {
        return false;
    }

    return true;
}

export default class CacheStorage {
    constructor(cacheId, requiredCookie, additionalCookies, ttl) {
        this.cacheId = cacheId;
        this.requiredCookie = requiredCookie;
        this.additionalCookies = additionalCookies || [];
        this.ttl = ttl || 20*60;
        this.currentFetchResult = undefined;
    }

    isIgnored() {
        return !getCookie(this.requiredCookie);
    }

    /**
     * Callback definition for cached value method
     * @callback CacheStorage~fetchDynamicData
     * @returns {Promise<string>}
     */

    /**
     *
     * @param {CacheStorage~fetchDynamicData} fetchDynamicData
     * @returns {Promise<string>}
     */
    cachedValue(fetchDynamicData) {
        let value = readFromCache(this.cacheId);

        if (value && validateMetadata(this.cacheId)) {
            return Promise.resolve(value);
        }

        return this._processUncachedData(fetchDynamicData);
    }

    /**
     *
     * @param {CacheStorage~fetchDynamicData} fetchDynamicData
     * @returns {Promise<string>}
     */
    _processUncachedData(fetchDynamicData) {
        if (this.currentFetchResult) {
            return this.currentFetchResult;
        }

        const fetchResult = fetchDynamicData().then(
            (value) => {
                writeToCache(this.cacheId, value);
                saveMetadata(this.cacheId, this.requiredCookie, this.additionalCookies, this.ttl);
                this.currentFetchResult = undefined;
                return value;
            },
            () => {
                return '';
            }
        );

        this.currentFetchResult = fetchResult;

        return fetchResult;
    }

    _generateMetadata() {
        return {
            cookie: getCookie(this.requiredCookie)
        };
    }
}