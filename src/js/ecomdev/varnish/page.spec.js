import Page from './page';

/** @type Array */
let observedInstances;

/** @type Page */
let page;

const consoleWarn = console.warn;

afterEach(() => { console.warn = consoleWarn });

beforeEach(() => {
    page = new Page('ecomdev/varnish-element');
    console.warn = function () {};
    observedInstances = [];

    // Body fixture
    document.body.innerHTML = `
        <div>
            <script type="ecomdev/varnish-element" data-type="one">{"type_one":"one"}</script>
            <div>
                <script type="ecomdev/varnish-element" data-type="one">{"type_one":"two"}</script>
                <div>
                    <script type="ecomdev/varnish-element" data-type="two">{"type_two":"one"}</script>
                </div>
                <script type="ecomdev/varnish-element" data-type="two">{"type_two":"three"}</script>
            </div>
            
            
            <script type="ecomdev/varnish-element" data-type="three">bad data</script>
            
            <script type="ecomdev/varnish-element" data-type="one">{"type_one":"three"}</script>
            <script type="ecomdev/varnish-element" data-type="two">{"type_two":"two"}</script>
            <script type="ecomdev/varnish-element" data-type="three">{"type_three":"one"}</script>
            
            <script type="ecomdev/varnish-element">{"invalid":"one"}</script>
            
            <script type="text/babel">const value = 1;</script>
        </div>
    `;
});

describe('Registered elements', () => {

    beforeEach( () => {
        page.elementType('one', (constructor) => {
            observedInstances.push(['one', constructor]);
        })
    });

    it('creates originally registered elements on first processing', () => {
        page.processDocument();

        expect(observedInstances).toStrictEqual([
            ['one', {type_one:"one"}],
            ['one', {type_one:"two"}],
            ['one', {type_one:"three"}],
        ]);
    });

    it('creates additional elements when registered', () => {
        page.elementType('two', (constructor) => {
            observedInstances.push(['two', constructor]);
        })

        page.processDocument();

        expect(observedInstances).toStrictEqual([
            ['one', {type_one:"one"}],
            ['one', {type_one:"two"}],
            ['two', {type_two:"one"}],
            ['two', {type_two:"three"}],
            ['one', {type_one:"three"}],
            ['two', {type_two:"two"}],
        ]);
    });

    it('processes elements only once per type', () => {
        page.processDocument();
        page.elementType('two', (constructor) => {
            observedInstances.push(['two', constructor]);
        })
        page.processDocument();

        expect(observedInstances.length).toBe(6);
    });

    it('ignores invalid elements', () => {
        page.elementType('three', (constructor) => {
            observedInstances.push(['three', constructor]);
        })

        page.processDocument();

        expect(observedInstances).toStrictEqual([
            ['one', {type_one:"one"}],
            ['one', {type_one:"two"}],
            ['one', {type_one:"three"}],
            ['three', {type_three:"one"}],
        ]);
    });
});