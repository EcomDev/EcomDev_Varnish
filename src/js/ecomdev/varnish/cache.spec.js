'use strict';

import CacheStorage from './cache';
import {setCookie} from "./cookie";

beforeEach(() => {
    window.localStorage.clear();
})

describe("Cache storage with default options", () => {
    const storage = new CacheStorage('some-id', 'required-cookie');

    it('should ignore cache entries if cookie is missing', () => {
        expect(storage.isIgnored()).toBeTruthy();
    });

    it('should not ignore cache entries if cookie is set', () => {
        document.cookie = 'required-cookie=1';
        expect(storage.isIgnored()).toBeFalsy();
    });

    it('should ignore storage if cookie value is empty', () => {
        document.cookie = 'required-cookie=';
        expect(storage.isIgnored()).toBeTruthy();
    });

    it('should load uncached data from provided resolver',  async () => {
        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value'))).toBe('dynamic_value');
    })

    it('should return data from cache on second call', async () => {
        await storage.cachedValue(() => Promise.resolve('cached_value'));

        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value1'))).toBe('cached_value');
    })

    it('should not invoke multiple fetch calls if one was scheduled before', async () => {
        const value = storage.cachedValue(() => Promise.resolve('requested_value'));

        storage.cachedValue(() => Promise.resolve('next_value1'));
        storage.cachedValue(() => Promise.resolve('next_value2'));

        expect(await storage.cachedValue(() => Promise.resolve('next_value3'))).toBe('requested_value');
    })

    it('should invalidate record if cookie value changed', async () => {
        await storage.cachedValue(() => Promise.resolve('cached_value1'));
        document.cookie = 'required-cookie=2'
        await storage.cachedValue(() => Promise.resolve('cached_value2'));

        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value'))).toBe('cached_value2');
    })
});

describe("Cache storage with additional cookies", () => {
    const storage = new CacheStorage(
        'some-id',
        'required-cookie',
        ['additional-cookie1', 'additional-cookie2']
    );

    beforeEach(() => {
        setCookie('required-cookie', 3);
        setCookie('additional-cookie1', 1);
        setCookie('additional-cookie2', 2);
    })

    it('should invalidate record if additional cookie value has changed', async () => {
        await storage.cachedValue(() => Promise.resolve('cached_value1'));

        setCookie('additional-cookie1', 2);

        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value'))).toBe('dynamic_value');
    })

    it('should return data from cache on second call', async () => {
        await storage.cachedValue(() => Promise.resolve('cached_value'));

        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value1'))).toBe('cached_value');
    })

});

describe("Cache storage with ttl", () => {
    const storage = new CacheStorage(
        'some-id',
        'required-cookie',
        [],
        1
    );

    it('should invalidate storage if record has expired', async () => {
        await storage.cachedValue(() => Promise.resolve('cached_value1'));

        await new Promise((resolve) => {
            setTimeout(resolve, 1000);
        })

        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value'))).toBe('dynamic_value');
    })

    it('should return data from cache on second call', async () => {
        await storage.cachedValue(() => Promise.resolve('cached_value'));

        expect(await storage.cachedValue(() => Promise.resolve('dynamic_value1'))).toBe('cached_value');
    })

    it ('should return empty value on promise rejection', async () => {
        expect(await storage.cachedValue(() => Promise.reject('epic_fail'))).toBe('');
    })
});