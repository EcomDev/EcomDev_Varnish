import setHtmlValue from './set-html';

beforeEach(() => {
    document.body.innerHTML = `
        <div id="some-content-1">Div 1</div>
        <div id="some-content-2">Div 2</div>
        <div id="some-content-3">Div 3</div>
        <div id="some-content-4">Div 4</div>
    `;
});

function elementContent(id) {
    return document.getElementById('some-content-' + id).innerHTML;
}

describe('HTML update', () => {
    it('should change target element', () => {
        setHtmlValue('some-content-2', '<span>Updated Content</span>');
        expect(elementContent('2')).toBe('<span>Updated Content</span>')
    })

    it('should keep rest of the elements not updated', () => {
        setHtmlValue('some-content-2', '<span>Updated Content</span>');
        expect(elementContent('1')).toBe('Div 1');
        expect(elementContent('3')).toBe('Div 3');
        expect(elementContent('4')).toBe('Div 4');
    })

    it('should ignore non existing element',  () => {
        setHtmlValue('some-content-5', 'Some value');
    });
});