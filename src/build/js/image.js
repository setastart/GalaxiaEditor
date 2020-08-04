
function gjImageSelectorOpen(el, imgType) {
    gjImageSelector.scrollPrev = window.scrollY;

    gjImageSelectorActiveInput = el.previousElementSibling;
    gjImageSelector.classList.remove('hide');

    gjImageSelectorActiveImages = [];
    let moduleField = el.closest('.module-field');
    if (moduleField) {
        gjImageSelectorActiveImages = Array.from(moduleField.querySelectorAll('.input-wrap-slugImage .input-slug')).map(el => el.value);
    } else {
        gjImageSelectorActiveImages = [gjImageSelectorActiveInput.value];
    }

    var siblings = getSiblings(gjImageSelector);
    for (var i = 0; i < siblings.length; i++) {
        siblings[i].classList.add('hide');
    }
    // window.scrollTo(0, 0);

    var typeInput = document.getElementsByName('filterTexts[type]')[0]
    if (!typeInput.opened) {
        typeInput.value = imgType;
        typeInput.opened = true;
    }
    trigger(typeInput, 'input');
    gjImageSelector.querySelector('.input-search').focus();
}

function gjImageSelectorClose() {
    if (gjImageSelector.classList.contains('hide')) return
    gjImageSelector.classList.add('hide');

    var siblings = getSiblings(gjImageSelector);
    for (var i = 0; i < siblings.length; i++) {
        siblings[i].classList.remove('hide');
    }
    window.scrollTo(0, gjImageSelector.scrollPrev);
    gjImageSelectorActiveInput.focus();
}

function gjImageSelectorActivate(el) {
    var selectedImg = el.children[0].children[0];
    var selectorImg = gjImageSelectorActiveInput.nextElementSibling.children[0];

    gjImageSelectorClose();
    gjImageSelectorActiveInput.value = el.id;
    selectorImg.src = selectedImg.src;
    selectorImg.parentNode.classList.remove('empty');
    selectorImg.setAttribute('width', selectedImg.getAttribute('width'));
    selectorImg.setAttribute('height', selectedImg.getAttribute('height'));
    textareaAutoGrow(gjImageSelectorActiveInput)
}


function gjImageResizeRequest(el, ev) {
    el.parentNode.classList.add('waiting');
    var re = /.*\/([0-9a-z-]+)_(\d+_\d+)\./;
    var match = re.exec(el.src);
    if (!match) return;
    var imgSlug = match[1];
    var size = match[2].split('_');

    var xhr = new XMLHttpRequest();
    xhr.imgEl = el;
    xhr.onload = function() {
        if (this.status != 200 && this.responseText != 'ok') return;
        this.imgEl.parentNode.classList.remove('waiting');
        this.imgEl.parentNode.classList.add('loading');
        this.imgEl.src += '?' + +new Date;
        // this.imgEl.onload = null;
        this.imgEl.onerror = null;
    };
    xhr.onprogress = function(event) {
        if (!event.lengthComputable) return; // size unknown
        var percentComplete = event.loaded / event.total * 100;
        console.log(percentComplete);
    };
    xhr.onerror = function() {
        console.error('Resize request error.');
    };
    xhr.open('GET', '/edit/images/' + imgSlug + '/resize/' + size[0] + '/' + size[1]);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
    el.onload = function() {
        this.onload = null;
        this.onerror = null;
        this.parentNode.classList.remove('waiting', 'loading')
    };
}
