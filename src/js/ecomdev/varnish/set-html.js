export default function setHtmlValue(elementId, html) {
    const element = document.getElementById(elementId);
    if (!element) {
        return;
    }

    element.innerHTML = '';
    const range = document.createRange();
    range.selectNode(element);
    element.appendChild(range.createContextualFragment(html));
}