if (!String.prototype.padStart) {
    String.prototype.padStart = function padStart(targetLength, padString) {
        targetLength = targetLength >> 0; //truncate if number, or convert non-number to 0;
        padString    = String(typeof padString !== 'undefined' ? padString : ' ');
        if (this.length >= targetLength) {
            return String(this);
        } else {
            targetLength = targetLength - this.length;
            if (targetLength > padString.length) {
                padString += padString.repeat(targetLength / padString.length); //append to original to ensure we are longer than needed
            }
            return padString.slice(0, targetLength) + String(this);
        }
    };
}


if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}


if (!Element.prototype.closest) {
    Element.prototype.closest = function(selectors) {
        let el = this;
        do {
            if (el.matches(selectors)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}


Array.prototype.contains = function(el) {
    return this.indexOf(el) > -1;
};


function trigger(el, evName, bubbles = false) {
    let ev = new Event(evName);
    if (bubbles) ev = new Event(evName, {bubbles: true});
    el.dispatchEvent(ev);
}


function getChildren(n, skipMe) {
    let r = [];
    for (; n; n = n.nextSibling)
        if (n.nodeType === Node.ELEMENT_NODE && n !== skipMe)
            r.push(n);
    return r;
}


function getSiblings(n) {
    return getChildren(n.parentNode.firstChild, n);
}
