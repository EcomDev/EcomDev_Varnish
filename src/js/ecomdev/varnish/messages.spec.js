import Messages from './messages'
import {spyFetch} from "./__fake_request";
import {getCookie, setCookie} from "./cookie";

function elementContent(id) {
    return document.getElementById('message-container-' + id).innerHTML;
}

/** @type {Messages} */
let messages;
let fetchSpy;

beforeEach(() => {
    fetchSpy = spyFetch({
        checkout: 'Checkout',
        catalog: 'Catalog',
        sales: 'Sales',
        reports: 'Reports'
    }, 'storage');

    messages = new Messages(fetchSpy.fetch);

    document.body.innerHTML = `
        <div id="message-container-1"></div>
        <div id="message-container-2"></div>
        <div id="message-container-3"></div>
    `;
});

afterEach(() => {
    setCookie('message-cookie', '', {expires: new Date(1)});
})

describe('No message block definitions', () => {
    it ('processes no message types', async () => {
        expect(await messages.processMessages()).toEqual(0);
    });

    it('does not invoke any http requests', async() => {
        await messages.processMessages();

        expect(fetchSpy.fetchedRequests()).toEqual([]);
    });

    it('does not update message containers', async () => {
        await messages.processMessages();

        expect(
            [elementContent(1), elementContent(2), elementContent(3)]
        ).toEqual(['', '', ''])
    });
});

describe('Single message container', () => {
    beforeEach(() => {
        messages.registerLoader({
            container: 'message-container-1',
            types: ['checkout', 'sales'],
            url: 'http://some-website.com/messages',
            cookieName: 'message-cookie',
            cookieOptions: {
                path: '/'
            }
        })
    })

    it('sends request to configured url when cookie is set', async () => {
        setCookie('message-cookie', 'sales,checkout,catalog');

        await messages.processMessages();

        expect(fetchSpy.fetchedRequests()).toEqual([
            new Request('http://some-website.com/messages', {
                method: 'POST',
                body: new FormData({
                    storage: 'checkout,sales'
                })
            })
        ]);
    });
    it('removes processed message types from cookie', async () => {
        setCookie('message-cookie', 'sales,checkout,catalog');

        await messages.processMessages();

        expect(getCookie('message-cookie')).toEqual('catalog');
    })

    it('does not send request if message types are not supported', async () => {
        setCookie('message-cookie', 'category,product,reports');

        await messages.processMessages();

        expect(fetchSpy.fetchedRequests()).toEqual([]);
    });

    it('fixes cookie value after processing', async () => {
        setCookie('message-cookie', 'category, ,,reports,');

        await messages.processMessages();

        expect(getCookie('message-cookie')).toEqual('category,reports');
    });

    it('updates message container with result from server', async () => {
        setCookie('message-cookie', 'checkout,catalog');

        await messages.processMessages();

        expect(elementContent(1)).toEqual('{"checkout":"Checkout"}');
    });

    it('reports single message type updated', async () => {
        setCookie('message-cookie', 'checkout,sales,catalog');

        expect(await messages.processMessages()).toBe(1);
    });
});

describe('Multiple message container', () => {
    beforeEach(() => {
        messages.registerLoader({
            container: 'message-container-1',
            types: ['checkout', 'catalog'],
            url: 'http://some-website.com/messages',
            cookieName: 'message-cookie',
            cookieOptions: {
                path: '/'
            }
        });
        messages.registerLoader({
            container: 'message-container-2',
            types: ['sales'],
            url: 'http://some-website.com/messages'
        })
        messages.registerLoader({
            container: 'message-container-3',
            types: ['reports'],
            url: 'http://some-website.com/messages'
        })
    })

    it('sends request to configured url for each matched message type', async () => {
        setCookie('message-cookie', 'sales,checkout,catalog,reports');

        await messages.processMessages();

        expect(fetchSpy.fetchedRequests()).toEqual([
            new Request('http://some-website.com/messages', {
                method: 'POST',
                body: new FormData({
                    storage: 'checkout,catalog'
                })
            }),
            new Request('http://some-website.com/messages', {
                method: 'POST',
                body: new FormData({
                    storage: 'sales'
                })
            }),
            new Request('http://some-website.com/messages', {
                method: 'POST',
                body: new FormData({
                    storage: 'reports'
                })
            })
        ]);
    });
    it('removes processed message types from cookie', async () => {
        setCookie('message-cookie', 'sometype,sales,checkout,catalog,reports');

        await messages.processMessages();

        expect(getCookie('message-cookie')).toBe('sometype');
    });

    it('updates each message container with own message result', async () => {
        setCookie('message-cookie', 'sales,checkout,catalog,reports');

        await messages.processMessages();

        expect([
            elementContent(1),
            elementContent(2),
            elementContent(3)
        ]).toEqual([
            '{"checkout":"Checkout","catalog":"Catalog"}',
            '{"sales":"Sales"}',
            '{"reports":"Reports"}',
        ]);
    });
});