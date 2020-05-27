import {getAllCookies, getCookie, setCookie} from './cookie';

describe('When empty cookie storage', () => {
   it('should return list of empty cookies', () => {
       expect(getAllCookies()).toStrictEqual({})
   });

   it('should return undefined value for cookie value', () => {
       expect(getCookie('some-cookie')).toBe(undefined);
   });

   it('cookie with expiration date is not set', () => {
        setCookie('one', 'two', {
            expires: new Date(Date.parse('Fri, 31 Jan 2020 23:00:00 GMT'))
        });

        expect(getAllCookies()).not.toHaveProperty('one');
   });

   it('cookie with expiration date in future is set properly', () => {
       const futureDate = new Date();
       futureDate.setHours(futureDate.getHours()+1);

       setCookie('one', 'two', {
           expires: futureDate
       });

       expect(getAllCookies()).toHaveProperty("one", 'two');
   });

   it ('cookie with path that does not match current url', () => {
       setCookie('two', 'value_incorrect', {
           path: '/some-path/'
       });

       expect(getAllCookies()).not.toHaveProperty('two');
   });

    it ('cookie with path that match current url', () => {
        setCookie('two', 'value_correct', {
            path: '/'
        });

        expect(getAllCookies()).toHaveProperty('two', 'value_correct');
    });

    it ('cookie with domain not related to current page', () => {
        setCookie('three', 'value_incorrect_domain', {
            domain: 'some-wrong.com'
        });

        expect(getAllCookies()).not.toHaveProperty('three');
    });
});

describe('Provided set of various cookie values', () => {
    beforeEach(() => {
        setCookie('cookie-one', 'one');
        setCookie('cookie-two', 'two');
        setCookie('encoded cookie', 'with encoded value');
        setCookie('cookie-three', 'three');
    });

    it('should return list of set cookies', () => {
        const values = getAllCookies();
        expect(values).toHaveProperty("cookie-one", "one");
        expect(values).toHaveProperty("cookie-two", "two");
        expect(values).toHaveProperty("cookie-three", "three");
    });

    it('should return first cookie', () => {
        expect(getCookie('cookie-one')).toBe('one');
    });

    it('should return last cookie', () => {
        expect(getCookie('cookie-three')).toBe('three');
    });

    it('should return encoded cookie', () => {
        expect(getCookie('encoded cookie')).toBe('with encoded value');
    });
});