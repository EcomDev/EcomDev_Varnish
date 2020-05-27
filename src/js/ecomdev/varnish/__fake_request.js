global.Request = class {
    constructor(url, options) {
        this.url = url;
        this.options = options;
    }

    get body() {
        return this.options.body || new FormData();
    }
}

global.FormData = class {
    constructor(data) {
        this.data = data || {};
    }

    append(name, value) {
        this.set(name, value);
    }

    get(name) {
        return this.data[name] || '';
    }

    has(name) {
        return this.data[name] !== undefined;
    }

    set(name, value) {
        this.data[name] = value;
    }
}

global.Response = class {
    constructor(body, options) {
        this.body = body;
        this.options = options;
    }

    get status() {
        return this.options.status || 500;
    }

    get statusText() {
        return this.options.statusText || 'Internal Server Error';
    }

    json() {
        return Promise.resolve(JSON.parse(this.body));
    }

    text() {
        return Promise.resolve(this.body);
    }
}

/**
 * @param {object} [parametrizedData]
 * @param {string} [parameter]
 */
export function fakeFetch(parametrizedData, parameter) {
    parametrizedData = parametrizedData || {};
    parameter = parameter || '';

    return function (request) {
        const blockResult = request.body.get(parameter).split(',').reduce((data, v) => {
            if (parametrizedData[v]) {
                data[v] = parametrizedData[v];
            }

            return data;
        }, {});

        return Promise.resolve(new Response(JSON.stringify(blockResult), {
            status: 200,
            statusText: "OK"
        }));
    };
}

/**
 * @param {object} [blocks]
 * @param {string} [parameter]
 */
export function spyFetch(blocks, parameter) {
    const fetchRequests = [];
    const fetch = fakeFetch(blocks, parameter);
    return {
        fetch (request) {
            fetchRequests.push(request);
            return fetch(request);
        },
        fetchedRequests() {
            return fetchRequests;
        }
    }
}