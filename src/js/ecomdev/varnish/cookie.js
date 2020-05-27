function readCookies() {
    return document.cookie
        .split(/; ?/)
        .reduce((hash, value) => {
            if (value.indexOf('=') === -1) {
                return hash;
            }
            const [cookieName, cookieValue] = value.split('=');
            hash[decodeURIComponent(cookieName)] = decodeURIComponent(cookieValue);
            return hash;
        }, {});
}

export function setCookie(name, value, options) {
    options = options || {};
    document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value) +
        (options.expires ? '; Expires=' + options.expires.toUTCString() : '' ) +
        (options.path ? '; Path=' + options.path : '' ) +
        (options.domain ? '; Domain=' + options.domain : '' )
    ;
}

export function getCookie(name) {
    const cookies = readCookies();

    return cookies[name] ? cookies[name] : undefined;
}

export function getAllCookies() {
    return readCookies();
}