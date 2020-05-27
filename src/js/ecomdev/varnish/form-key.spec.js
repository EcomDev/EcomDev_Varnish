import FormKey from './form-key';
import {setCookie, getCookie} from "./cookie";

function nodeListAsArray(nodeList) {
    const result = [];
    for (let i = 0; i < nodeList.length; i ++) {
        result.push(nodeList[i]);
    }

    return result;
}

function allLinkUrls() {
    return nodeListAsArray(document.querySelectorAll('a')).map((link) => {
        return link.getAttribute("href");
    });
}

function allForms() {
    return nodeListAsArray(document.querySelectorAll('form')).reduce((data, form) => {
        data[form.getAttribute('id')] = {
            url: form.getAttribute('action'),
            fields: nodeListAsArray(form.querySelectorAll('input')).reduce((data, input) => {
                data[input.name] = input.value;
                return data;
            }, {})
        };

        return data;
    }, {});
}

let formKey;

beforeEach(() => {
    window.setLocation = function (url) {
        return url;
    }

    window.setLocationWithMoreArguments = function (url, arg1, arg2) {
        return [url, arg1, arg2];
    }

    formKey = new FormKey();

    document.body.innerHTML = `
        <div>
            <a class="some-link" href="http://some-url.com/product/4/form_key/1/"></a>
        
            <div>
                <a class="link-one" href="http://some-url.com/product/1/form_key/value/"></a>
                <a class="link-two" href="http://some-url.com/product/2/some_form_key/value"></a>
                <a class="link-three" href="http://some-url.com/product/3/form_key/value/"></a>
                <a class="link-one" href="http://some-url.com/product/4"></a>
            </div>
            
            <form id="form1">
                <input name="form_key_1" type="hidden" value="111">
                <input name="name" value="Some Name" type="text" />
            </form>
            
            <form id="form2" action="http://some-url.com/some_form_key/value/">
            </form>
            
            <form id="form3">
                <input name="form_key_1"  type="hidden"  value="333">
            </form>
            
            <form id="form4" action="http://some-url.com/form_key/value/">
            </form>
        </div>
    `;
})

describe("Incomplete generation", () => {
    it('returns empty form key', () => {
        expect(formKey.formKey()).toEqual('');
    });

    it('keeps links in tact', () => {
        formKey.replaceValues();

        expect(allLinkUrls()).toEqual([
            'http://some-url.com/product/4/form_key/1/',
            'http://some-url.com/product/1/form_key/value/',
            'http://some-url.com/product/2/some_form_key/value',
            'http://some-url.com/product/3/form_key/value/',
            'http://some-url.com/product/4',
        ]);
    })

    it('keeps forms in tact', () => {
        formKey.replaceValues();

        expect(allForms()).toEqual({
            form1: {
                fields: {form_key_1: "111", name: "Some Name"},
                url: null
            },
            form2: {
                fields: {},
                url: "http://some-url.com/some_form_key/value/"
            },
            form3: {
                fields: {form_key_1: "333"},
                url: null
            },
            form4: {
                fields: {},
                url: "http://some-url.com/form_key/value/"
            }
        });
    })
});

describe("Form key generation", () => {
    beforeEach(() => {
        formKey.processConfig({cookieName: 'cookie1', cookieOptions: {path: '/'}});
    })
    afterEach(() => {
        setCookie('cookie1', '', {expires: new Date(1)});
    })

    it('generates adequate form key', () => {
        expect(formKey.formKey()).toHaveLength(16);
    });

    it('stores generated form key in the cookie', () => {
        const generatedKey = formKey.formKey();

        expect(getCookie('cookie1')).toEqual(generatedKey);
    });

    it('reuses stored value in cookie for new form keys', () => {
        const generatedKey = formKey.formKey();

        expect(formKey.formKey()).toEqual(generatedKey);
    });

    it('uses value from cookie if one present', () => {
        setCookie('cookie1', 'aforcedformkey00');

        expect(formKey.formKey()).toEqual('aforcedformkey00');
    })
});

describe("Configuration with urlKey and inputFieldName", () => {
    beforeEach(() => {
        setCookie('cookie1', 'form_key_value_1')
        formKey.processConfig({
            cookieName: 'cookie1',
            inputFieldName: 'form_key_1',
            urlKeyParam: 'some_form_key'
        });
    })

    it('replaces form inputs and actions with custom value', () => {
        formKey.replaceValues();

        expect(allForms()).toEqual({
            form1: {
                fields: {form_key_1: "form_key_value_1", name: "Some Name"},
                url: null
            },
            form2: {
                fields: {},
                url: "http://some-url.com/some_form_key/form_key_value_1/"
            },
            form3: {
                fields: {form_key_1: "form_key_value_1"},
                url: null
            },
            form4: {
                fields: {},
                url: "http://some-url.com/form_key/value/"
            }
        });
    })

    it('keeps links in tact', () => {
        formKey.replaceValues();

        expect(allLinkUrls()).toEqual([
            'http://some-url.com/product/4/form_key/1/',
            'http://some-url.com/product/1/form_key/value/',
            'http://some-url.com/product/2/some_form_key/value',
            'http://some-url.com/product/3/form_key/value/',
            'http://some-url.com/product/4',
        ]);
    })

    it ('keeps setLocation intact', () => {
        formKey.replaceValues();

        expect(window.setLocation('/product/2/some_form_key/value/')).toBe('/product/2/some_form_key/value/');
    });
});

describe("With urlKey and simple locationFunction", () => {
    beforeEach(() => {
        setCookie('cookie1', 'form_key_value_1')
        formKey.processConfig({
            cookieName: 'cookie1',
            urlKeyParam: 'some_form_key',
            locationFunction: 'setLocation'
        });
    });

    it ('replaces value in the beginning of the relative url', () => {
        expect(window.setLocation('/some_form_key/value/form_key/value/'))
            .toBe('/some_form_key/form_key_value_1/form_key/value/')
    });

    it ('replaces value in the middle of the relative url', () => {
        expect(window.setLocation('/catalog/view/some_form_key/value/end/'))
            .toBe('/catalog/view/some_form_key/form_key_value_1/end/')
    });

    it ('replaces value in the end of the relative url', () => {
        expect(window.setLocation('/catalog/view/some_form_key/value'))
            .toBe('/catalog/view/some_form_key/form_key_value_1')
    });
});

describe("With urlKey and multi-argument locationFunction", () => {
    beforeEach(() => {
        setCookie('cookie1', 'form_key_value_1')
        formKey.processConfig({
            cookieName: 'cookie1',
            urlKeyParam: 'some_form_key',
            locationFunction: 'setLocationWithMoreArguments'
        });
    });

    it ('replaces value and preserves other arguments', () => {
        expect(
            window.setLocationWithMoreArguments(
                '/url/some_form_key/value/form_key/value/',
                'value1',
                'value2'
            )
        ).toEqual([
            '/url/some_form_key/form_key_value_1/form_key/value/',
            'value1',
            'value2'
        ])
    });
});

describe("With urlKey and observedCssRules", () => {
    beforeEach(() => {
        setCookie('cookie1', 'form_key_value_1')
        formKey.processConfig({
            cookieName: 'cookie1',
            urlKeyParam: 'form_key',
            observedCssRules: ['.link-one', '.link-two', '.link-three']
        });

    });

    it ('replaces value and preserves other arguments', () => {
        formKey.replaceValues();

        expect(allLinkUrls()).toEqual([
            'http://some-url.com/product/4/form_key/1/',
            'http://some-url.com/product/1/form_key/form_key_value_1/',
            'http://some-url.com/product/2/some_form_key/value',
            'http://some-url.com/product/3/form_key/form_key_value_1/',
            'http://some-url.com/product/4',
        ])
    });
});

describe("Variable export", () => {
    beforeEach(() => {
        setCookie('cookie1', 'form_key_value_1')
        formKey.processConfig({
            cookieName: 'cookie1',
            urlKeyParam: 'form_key',
            observedCssRules: ['.link-one', '.link-two', '.link-three']
        });
        formKey.processExport({
            variableName: 'someFormKeyVariable1'
        });
        formKey.processExport({
            variableName: 'someFormKeyVariable2'
        });
        formKey.processExport({
            variableName: 'someFormKeyVariable3'
        });
        formKey.replaceValues();
    });

    it('exports value into first variable', () => {
        expect(window.someFormKeyVariable1).toBe(formKey.formKey());
    })

    it('exports value into second variable', () => {
        expect(window.someFormKeyVariable2).toBe(formKey.formKey());
    });

    it('exports value into third variable', () => {
        expect(window.someFormKeyVariable3).toBe(formKey.formKey());
    });
});

