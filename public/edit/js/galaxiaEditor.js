'use strict';

/***************************/
/******  polyfill.js  ******/
/***************************/

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
    Element.prototype.closest = function (s) {
        var el = this;
        do {
            if (el.matches(s)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}



Array.prototype.contains = function(element){
    return this.indexOf(element) > -1;
};



function trigger(el, evName, bubbles = false) {
    var ev = new Event(evName);
    if (bubbles) ev = new Event(evName, {bubbles: true});
    el.dispatchEvent(ev);
}




function getChildren(n, skipMe){
    var r = [];
    for ( ; n; n = n.nextSibling )
       if ( n.nodeType == 1 && n != skipMe)
          r.push( n );
    return r;
}




function getSiblings(n) {
    return getChildren(n.parentNode.firstChild, n);
}








/***********************/
/******  main.js  ******/
/***********************/

let gjTextareas                 = [];
let gjResizeTimeout             = null;
let gjImageSelector             = [];
let gjImageSelectorActiveInput  = null;
let gjImageSelectorActiveImages = [];
let filterData                  = ['pageCurrent', 'pageFirst', 'pagePrev', 'pageNext', 'pageLast', 'itemsPerPage', 'rowsFiltered', 'rowsTotal'];


window.addEventListener('DOMContentLoaded', function () {
    const iOS    = !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
    const safari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    if (iOS && safari) document.querySelector('meta[name=viewport]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0');
    gjLoad();
});


function gjLoad() {
    document.addEventListener('input', handleEventInput, true);
    document.addEventListener('change', handleEventChange, true);
    document.addEventListener('blur', handleEventBlur, true);
    document.addEventListener('click', handleEventClick, true);
    document.addEventListener('mousedown', handleEventMousedown, true);
    document.addEventListener('drop', handleEventDrop, false);
    document.addEventListener('dragover', handleEventDragover, false);
    window.addEventListener('keydown', handleEventKeydown, true);
    window.addEventListener('error', handleEventError, true);


    gjImageSelector = document.getElementById('image-select');
    gjTextareas     = document.getElementsByTagName('textarea');

    gjResizeTextareas();


    // prepare form pagination
    for (let i = 0; i < document.forms.length; i++) {
        document.forms[i].pagination = [];
        filterData.forEach(function (el) {
            document.forms[i].pagination[el] = document.forms[i].querySelectorAll('.' + el);
        });
    }


    document.addEventListener('trix-before-initialize', function (ev) {
        ev.target.addEventListener('keydown', function (ev) {
            if (ev.shiftKey && ev.key === 'Enter') {
                ev.target.editor.recordUndoEntry('Shift+Enter');
                ev.target.editor.insertHTML('<br><br>');
                ev.preventDefault();
            }
        });
    });


    document.addEventListener('trix-change', function (ev) {
        let editor = ev.target;
        if (!editor.gInputLoaded) {
            editor.gInput       = editor.parentNode;
            editor.gInputLoaded = true;
        }
        initialUndoClasses(editor);
    });


    // on window resize with debounce
    window.onresize = function () {
        if (gjResizeTimeout != null) clearTimeout(gjResizeTimeout);
        gjResizeTimeout = setTimeout(gjResizeTextareas, 100);
    }

}

function handleEventDragover(ev) {
    console.log(ev);
}

function handleEventDrop(ev) {
    console.log('File(s) dropped');
    console.log(ev);
    // Prevent default behavior (Prevent file from being opened)
    ev.preventDefault();
    ev.stopPropagation()

    if (ev.dataTransfer.items) {
        // Use DataTransferItemList interface to access the file(s)
        for (var i = 0; i < ev.dataTransfer.items.length; i++) {
            // If dropped items aren't files, reject them
            if (ev.dataTransfer.items[i].kind === 'file') {
                var file = ev.dataTransfer.items[i].getAsFile();
                console.log('... file[' + i + '].name = ' + file.name);
            }
        }
    } else {
        // Use DataTransfer interface to access the file(s)
        for (var i = 0; i < ev.dataTransfer.files.length; i++) {
            console.log('... file[' + i + '].name = ' + ev.dataTransfer.files[i].name);
        }
    }
    return false;
}

function handleEventInput(ev) {

    if (
        ev.target.matches('.input-text') ||
        ev.target.matches('.input-file') ||
        ev.target.matches('.input-trix')
    ) {
        gjInputChange(ev.target, ev);
        textareaAutoGrow(ev.target);
    }

    if (ev.target.matches('.input-slug')) {
        gjInputFormat(ev.target, 'slug');
    }
    if (ev.target.matches('.input-date')) {
        gjInputFormat(ev.target, 'date');
    }
    if (ev.target.matches('.input-time')) {
        gjInputFormat(ev.target, 'time');
    }

    if (
        ev.target.matches('.itemsPerPage') ||
        ev.target.matches('.pageCurrent') ||
        ev.target.matches('.input-search')
    ) {
        filter(ev.target, ev);
    }
}


function handleEventChange(ev) {

    // checkbox .active toggling
    if (ev.target.matches('.btn-checkbox input')) {
        if (ev.target.checked) {
            ev.target.parentNode.classList.add('active');
        } else {
            ev.target.parentNode.classList.remove('active');
        }
    }

    if (
        ev.target.matches('.input-radio input') ||
        ev.target.matches('.input-select') ||
        ev.target.matches('.input-trix')
    ) {
        gjInputChange(ev.target, ev);
    }

    if (
        ev.target.matches('#switches input') ||
        ev.target.matches('.openbox input')
    ) {
        gjSwitch(ev.target, ev);
    }

    if (ev.target.matches('.input-image')) {
        gjImageValidate(ev.target);
    }

    if (ev.target.matches('.filterChange')) {
        filter(ev.target, ev);
    }

    if (ev.target.matches('.filterChangeEmpty')) {
        filterEmpty(ev.target, ev);
    }

}


function handleEventBlur(ev) {
    if (ev.target.matches && ev.target.matches('.input-select')) {
        gjInputChange(ev.target, ev);
    }
}


function handleEventClick(ev) {
    if (ev.target.matches('.slugImage')) {
        gjImageSelectorOpen(ev.target, ev.target.dataset.imgtype);
    }
    if (ev.target.matches('.image-select-header-close')) {
        gjImageSelectorClose(ev.target)
    }
    if (ev.target.matches('.scrape-jsonld')) {
        gjImportJsonld(ev.target, ev)
    }
    if (ev.target.matches('.scrape-youtube')) {
        gjImportYoutube(ev.target, ev)
    }
    if (ev.target.matches('.scrape-vimeo')) {
        gjImportVimeo(ev.target, ev)
    }
    if (ev.target.matches('.gchat-room-btn')) {
        gjcClickSend(ev.target, ev.target.dataset.room)
    }
    if (ev.target.matches('.imageSelectItem')) {
        gjImageSelectorActivate(ev.target)
    }

    if (ev.target.matches('.ev-cookie-set')) {
        document.cookie = ev.target.data.key + '=' + ev.target.data.val + '; SameSite=Strict; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/';
    }
    if (ev.target.matches('.ev-cookie-del')) {
        document.cookie = ev.target.data.key + '=; SameSite=Strict; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
    }


    if (ev.target.matches('.ev-module-add')) {
        let cloned = gjCloneModuleInputs(ev.target);
        if (cloned) {
            let slugImage = cloned.querySelector('.slugImage');
            if (slugImage) gjImageSelectorOpen(slugImage, ev.target.dataset.imgtype ?? '');
        }
    }
    if (ev.target.matches('.ev-module-rem')) {
        gjDeleteModuleInputs(ev.target);
    }
    if (ev.target.matches('.ev-module-first')) {
        gjModuleMove(ev.target, 1);
    }
    if (ev.target.matches('.ev-module-last')) {
        gjModuleMove(ev.target, 999999);
    }
    if (ev.target.matches('.ev-module-up')) {
        gjModuleMoveUp(ev.target);
    }
    if (ev.target.matches('.ev-module-down')) {
        gjModuleMoveDown(ev.target);
    }
    if (ev.target.matches('.ev-module-go')) {
        gjModuleMove(ev.target);
    }


    if (
        ev.target.matches('.pageFirst') ||
        ev.target.matches('.pagePrev') ||
        ev.target.matches('.pageNext') ||
        ev.target.matches('.pageLast')
    ) {
        filter(ev.target, ev);
    }
}


function handleEventMousedown() {
    document.body.classList.remove('show-focus-outlines');
}


function handleEventKeydown(ev) {

    // [command + s] sends form
    if (['s', 'S'].contains(ev.key) && (navigator.platform.match('Mac') ? ev.metaKey : ev.ctrlKey)) {
        ev.preventDefault();
        if (document.forms[0].id !== 'logout') {
            document.forms[0].submit();
        }
    }

    // only use focus on keyboard
    if (ev.key === 'Tab') {
        document.body.classList.add('show-focus-outlines');
    }
    if (['Esc', 'Escape'].contains(ev.key)) {
        document.body.classList.remove('show-focus-outlines');
        gjImageSelectorClose();
    }

    if (ev.key === 'Enter') {
        if (ev.target.matches('.input-text')) {
            ev.preventDefault();
            return false;
        }
        if (ev.target.matches('.module-position')) {
            gjModuleMove(ev.target.nextElementSibling);
            ev.preventDefault();
            return false;
        }
    }

    if (ev.target.matches('.input-date') || ev.target.matches('.input-time')) {
        gjInputMod(ev.target, ev);
    }

    if (ev.target.matches('.gchat-room-text')) {
        gjcEnterSend(ev.target, ev, ev.target.dataset.room);
    }
}


function handleEventError(ev) {
    if (ev.target.matches && (
        ev.target.matches('.slugImage img') ||
        ev.target.matches('.imageSelectItem img') ||
        ev.target.matches('.col-thumb img')
    )) {
        gjImageResizeRequest(ev.target, ev)
    }
}




/*************************/
/******  filter.js  ******/
/*************************/


function filter(el, ev, highlight) {
    var xhr = new XMLHttpRequest();
    var fd = new FormData(el.form);
    if (el.tagName == 'BUTTON') fd.set(el.name, el.value);
    // for (var pair of fd.entries()) {
    //     console.log(pair[0]+ ', ' + pair[1]);
    // }

    xhr.form = el.form;
    xhr.onload = function(event) {
        var loadEl = this.form.querySelector('.load');
        loadEl.innerHTML = event.target.responseText;

        if (gjImageSelectorActiveImages) {
            let images = loadEl.querySelectorAll('.imageSelectItem');
            for (let i = 0; i < images.length; i++) {
                if (gjImageSelectorActiveImages.contains(images[i].dataset.imgslug)) {
                    images[i].classList.add('active');
                    console.log(images[i].dataset.imgslug);
                }
            }
        }

        var results = loadEl.querySelector('.results').dataset;

        for (var i = 0; i < filterData.length; i++) {
            var el = filterData[i];
            var datakey = el.toLowerCase();
            if (results[datakey] == undefined) continue;

            for (var j = 0; j < this.form.pagination[el].length; j++) {
                var elToChange = this.form.pagination[el][j];

                switch (elToChange.tagName) {
                    case 'SPAN':
                        elToChange.innerHTML = results[datakey];
                        break;

                    case 'INPUT':
                        elToChange.value = results[datakey];
                        break;

                    case 'BUTTON':
                        elToChange.value = results[datakey];
                        if (el == 'pageFirst')
                            elToChange.disabled = (!results.pagecurrent || results.pagecurrent == 1);

                        if (el == 'pagePrev') {
                            elToChange.disabled = (!results.pagecurrent || results.pagecurrent == 1);
                        }

                        if (el == 'pageNext') {
                            elToChange.disabled = (results.pagecurrent == results.pagelast);
                        }

                        if (el == 'pageLast') {
                            elToChange.disabled = (results.pagecurrent == results.pagelast);
                        }
                        break;

                }

            }
        }

    };

    xhr.onerror = function() {
        console.error('filter loading error.');
    };

    xhr.open('POST', el.form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.send(fd);
    ev.preventDefault();
    return false;
}
function filterEmpty(el, ev) {
    if (el.parentNode.classList.contains('active')) {
        el.name = undefined;
        el.value = undefined;
        el.parentNode.previousElementSibling.disabled = false;
    } else {
        el.name = el.parentNode.previousElementSibling.name;
        el.value = '{{empty}}'
        el.parentNode.previousElementSibling.disabled = true;
    }
    filter(el, ev);
}




/************************/
/******  image.js  ******/
/************************/


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




/*************************/
/******  module.js  ******/
/*************************/

function gjModuleMoveUp(el) {
    var target = document.getElementById(el.dataset.target);
    var groups = target.parentNode;
    if (target.previousElementSibling)
        groups.insertBefore(target, target.previousElementSibling);
    gjModuleCountPositions(groups);
    target.querySelector('.module-position').focus();
}

function gjModuleMoveDown(el) {
    var target = document.getElementById(el.dataset.target);
    var groups = target.parentNode;
    if (target.nextElementSibling)
        groups.insertBefore(target.nextElementSibling, target);
    gjModuleCountPositions(groups);
    target.querySelector('.module-position').focus();
}

function gjModuleMove(el, pos = null) {
    var target = document.getElementById(el.dataset.target);
    var positionEl = target.querySelector('.module-position');

    if (pos) {
        positionEl.value = pos;
        console.log('pos', positionEl.value);
    } else {
        pos = positionEl.value;
    }
    console.log(target);
    var parent = target.parentNode;
    var groups = parent.children;

    if (pos >= groups.length) {
        parent.appendChild(target);
    } else {
        var j = 1;
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].dataset === undefined || groups[i].dataset.disabled) continue;
            if (groups[i] === target) continue;

            if (j == pos) {
                parent.insertBefore(target, groups[i]);
            }
            j++;
        }
    }
    target.querySelector('.module-position').focus();

    gjModuleCountPositions(target.parentNode);
}

function gjCloneModuleInputs(el) {
    const groupId = gjUnique();
    let newGroup  = document.getElementById(el.dataset.target).cloneNode(true);
    let where = document.getElementById(el.dataset.where);
    const oldGroup = newGroup.id;

    newGroup.classList.remove('hide');
    newGroup.classList.add('module-field-group-new');
    newGroup.id = newGroup.id + '-' + groupId;
    newGroup.groupId = groupId;

    let inputs = newGroup.getElementsByTagName('input');
    let selects = newGroup.getElementsByTagName('select');
    let textareas = newGroup.getElementsByTagName('textarea');
    let buttons = newGroup.getElementsByTagName('button');
    let i;
    for (i = inputs.length - 1; i >= 0; i--) {
        inputs[i].name = inputs[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        inputs[i].disabled = false;
        if (inputs[i].dataset.target !== undefined)
            inputs[i].dataset.target = newGroup.id;
    }
    for (i = selects.length - 1; i >= 0; i--) {
        selects[i].name = selects[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        selects[i].disabled = false;
        if (selects[i].dataset.target !== undefined)
            selects[i].dataset.target = newGroup.id;
    }
    for (i = textareas.length - 1; i >= 0; i--) {
        textareas[i].name = textareas[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        textareas[i].disabled = false;
        if (textareas[i].dataset.target !== undefined)
            textareas[i].dataset.target = newGroup.id;
    }
    for (i = buttons.length - 1; i >= 0; i--) {
        if (buttons[i].classList.contains('ev-module-add')) {
            continue;
        }
        buttons[i].name = buttons[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        buttons[i].disabled = false;
        if (buttons[i].dataset.target !== undefined)
            buttons[i].dataset.target = newGroup.id;
    }

    where.prepend(newGroup);
    gjModuleMove(newGroup.querySelector('.ev-module-go'), el.dataset.pos);
    return newGroup;
}


function gjDeleteModuleInputs(el) {
    var check = el.nextElementSibling;
    var row = document.getElementById(el.dataset.target);
    var group = document.getElementById(el.dataset.target).parentNode;
    var inputs = row.getElementsByTagName('input');
    var buttons = row.getElementsByTagName('button');
    var selects = row.getElementsByTagName('select');
    var radios  = row.getElementsByTagName('radio');
    var textareas = row.getElementsByTagName('textarea');
    var i;

    if (row.classList.contains('module-field-group-new')) {
        group.removeChild(row);
        gjModuleCountPositions(group);
        return;
    }

    if (row.dataset.disabled) {
        row.removeAttribute('data-disabled');
        row.classList.remove('module-field-group-delete');
        for (i = inputs.length - 1; i >= 0; i--)
            inputs[i].disabled = false;
        for (i = selects.length - 1; i >= 0; i--)
            selects[i].disabled = false;
        for (i = radios.length - 1; i >= 0; i--)
            radios[i].disabled = false;
        for (i = buttons.length - 1; i >= 0; i--)
            buttons[i].disabled = false;
        for (i = textareas.length - 1; i >= 0; i--)
            textareas[i].disabled = false;
        check.checked = false;
    } else {
        row.dataset.disabled = true;
        row.classList.add('module-field-group-delete');
        for (i = inputs.length - 1; i >= 0; i--)
            inputs[i].disabled = true;
        for (i = selects.length - 1; i >= 0; i--)
            selects[i].disabled = true;
        for (i = radios.length - 1; i >= 0; i--)
            radios[i].disabled = true;
        for (i = buttons.length - 1; i >= 0; i--)
            buttons[i].disabled = true;
        for (i = textareas.length - 1; i >= 0; i--)
            textareas[i].disabled = true;
        check.checked = true;
    }
    el.disabled = false;
    check.disabled = false;
    gjModuleCountPositions(group);
}


function gjModuleCountPositions(el) {
    let groups = el.childNodes;
    let j = 0;
    for (let i = 0; i < groups.length; i++) {
        if (groups[i].dataset === undefined || groups[i].dataset.disabled) continue;
        j++;

        let pos = groups[i].querySelector('.module-position');
        if (pos) pos.value = j;

        let posBefore = groups[i].querySelector('.module-field-gallery-add.before');
        if (posBefore) {
            posBefore.dataset.pos = j;
            // posBefore.innerHTML = posBefore.dataset.pos;
        }

        let posAfter = groups[i].querySelector('.module-field-gallery-add.after');
        if (posAfter) {
            posAfter.dataset.pos = (j + 1);
            // posAfter.innerHTML = posAfter.dataset.pos;
        }
    }
}




/************************/
/******  input.js  ******/
/************************/

// "what?" version ... http://jsperf.com/diacritics/12
var textDecoded = document.createElement('textarea');

function removeDiacritics(text) {
    text                  = text.toLowerCase();
    text                  = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    textDecoded.innerHTML = text;
    text                  = textDecoded.value;
    return text;
}

function gjResizeTextareas() {
    for (var i = 0; i < gjTextareas.length; i++) {
        textareaAutoGrow(gjTextareas[i]);
    }
}

function textareaAutoGrow(el) {
    if (el.scrollHeight > 38) {
        el.style.height = '';
        el.style.height = (el.scrollHeight + 2) + 'px';
    } else {
        el.style.height = '';
    }
}


function gjInputMod(el, ev) {
    var text     = el.value;
    var caretPos = el.selectionStart;
    if (ev.altKey && ev.key == 'ArrowLeft') {
        ev.preventDefault();
        var textBefore = text.substring(0, caretPos);
        var re         = /(\w+)\W+\w*$/;
        var match      = re.exec(textBefore);
        if (!match) {
            if (text[0].match(/\W/))
                el.setSelectionRange(0, 0);
            return;
        }
        el.setSelectionRange(textBefore.length - match[0].length, textBefore.length - match[0].length + match[1].length);
    }
    if (ev.altKey && ev.key == 'ArrowRight') {
        ev.preventDefault();
        var textAfter = text.substring(caretPos);
        var re        = /^\w*\W(\w+)/;
        var match     = re.exec(textAfter);
        if (!match) {
            if (text[text.length - 1].match(/\W/))
                el.setSelectionRange(text.length, text.length);
            return;
        }
        el.setSelectionRange(caretPos + match[0].length - match[1].length, caretPos + match[0].length);
    }
    if (ev.altKey && ev.key == 'ArrowDown') {
        ev.preventDefault();
        var foundPos = getTextAroundCaret(text, caretPos, '0-9');
        if (foundPos) {
            var number = +text.substring(foundPos[0], foundPos[0] + foundPos[1]);
            if (ev.shiftKey) number -= 10;
            else number--;
            if (number < 0) number = 0;
            number   = number.toString().padStart(foundPos[1], '0');
            el.value = text.substring(0, foundPos[0]) + number.toString() + text.substring(foundPos[0] + foundPos[1]);
            el.setSelectionRange(foundPos[0], foundPos[0] + number.toString().length);
        }
    }
    if (ev.altKey && ev.key == 'ArrowUp') {
        ev.preventDefault();
        var foundPos = getTextAroundCaret(text, caretPos, '0-9');
        if (foundPos) {
            var number = +text.substring(foundPos[0], foundPos[0] + foundPos[1]);
            if (ev.shiftKey) number += 10;
            else number++;
            if (number < 0) number = 0;
            number   = number.toString().padStart(foundPos[1], '0');
            el.value = text.substring(0, foundPos[0]) + number.toString() + text.substring(foundPos[0] + foundPos[1]);
            el.setSelectionRange(foundPos[0], foundPos[0] + number.toString().length);
        }
    }
    // return false;
}

function setCaretPosition(el, pos) {
    // el.focus();
    el.setSelectionRange(pos, pos);
}

function getTextAroundCaret(text, caret, pattern) {
    var textBefore = text.substring(0, caret);
    var textAfter  = text.substring(caret);

    var re          = new RegExp('[' + pattern + ']*$');
    var matchBefore = textBefore.match(re);
    re              = new RegExp('^[' + pattern + ']*');
    var matchAfter  = textAfter.match(re);
    if (matchBefore[0].length + matchAfter[0].length > 0)
        return [matchBefore.index, matchBefore[0].length + matchAfter[0].length];
    return false;
}

function gjInputFormat(el, type) {
    var caretOld = el.selectionStart;
    var caretNew = el.selectionStart;
    var valOld   = el.value;
    var valNew   = el.value;

    switch (type) {
        case 'slug':
            valNew = slugifyString(valNew, '-');
            if (valOld[firstDiffInStrings(valOld, valNew) - 1] == '-')
                caretNew = caretOld - 1;
            break;

        case 'date':
            valNew = slugifyString(valNew, '-');
            valNew = valNew.replace(/[^0-9\-]+/g, '');

            if (valOld[firstDiffInStrings(valOld, valNew) - 1] == '-')
                caretNew = caretOld - 1;
            break;

        case 'time':
            valNew = slugifyString(valNew, ':');
            valNew = valNew.replace(/[^0-9\:]+/g, '');

            if (valOld[firstDiffInStrings(valOld, valNew) - 1] == ':')
                caretNew = caretOld - 1;
            break;
    }

    if (valNew != valOld) {
        el.value = valNew;
        setCaretPosition(el, caretNew);
    }
}


function gjInputChange(el, ev) {
    var i;

    switch (el.tagName) {
        case 'INPUT':
        case 'TEXTAREA':
            switch (el.type) {
                case 'radio':
                    if (!el.gInputLoaded) {
                        // console.log('loading ' + el.tagName + ' ' + el.type + ' gInput');
                        el.gInput           = el.parentNode.parentNode;
                        el.gInput.radios    = el.gInput.querySelectorAll('input[name="' + el.name + '"]');
                        el.gInput.labels    = el.gInput.querySelectorAll('label');
                        el.gInput.elInitial = el.gInput.querySelector('.input-initial');
                        if (el.gInput.elInitial) {
                            el.gInput.elInitial.gInput = el.gInput;
                            el.gInput.elInitial.addEventListener('click', function (ev) {
                                this.gInput.undo = this.gInput.value;
                                for (i = 0; i < this.gInput.radios.length; i++) {
                                    if (this.gInput.radios[i].defaultChecked) {
                                        this.gInput.radios[i].checked = true;
                                        trigger(this.gInput.radios[i], 'change');
                                        break;
                                    }
                                }
                            });
                        }
                        el.gInput.elInitialUndo = el.gInput.querySelector('.input-initial-undo');
                        if (el.gInput.elInitialUndo) {
                            el.gInput.elInitialUndo.gInput = el.gInput;
                            el.gInput.elInitialUndo.addEventListener('click', function (ev) {
                                this.gInput.value = this.gInput.undo;
                                this.gInput.undo  = false;
                                for (i = 0; i < this.gInput.radios.length; i++) {
                                    if (this.gInput.radios[i].value == this.gInput.value) {
                                        this.gInput.radios[i].checked = true;
                                        trigger(this.gInput.radios[i], 'change');
                                        break;
                                    }
                                }
                            });
                        }
                        for (i = 0; i < el.gInput.radios.length; i++) {
                            el.gInput.radios[i].gInput       = el.gInput;
                            el.gInput.radios[i].gInputLoaded = true;
                        }
                    }
                    el.gInput.value = el.value;
                    for (i = 0; i < el.gInput.labels.length; i++)
                        el.gInput.labels[i].classList.remove('active');
                    el.parentNode.classList.add('active');

                    break;
                default:
                    if (!el.gInputLoaded) {
                        // console.log('loading ' + el.tagName + ' ' + el.type + ' gInput');
                        el.gInput           = el.parentNode;
                        el.gInput.inputEl   = el;
                        el.gInput.elInitial = el.gInput.querySelector('.input-initial');
                        if (el.gInput.elInitial) {
                            el.gInput.elInitial.gInput = el.gInput;
                            el.gInput.elInitial.addEventListener('click', function (ev) {
                                this.gInput.undo          = this.gInput.inputEl.value;
                                this.gInput.inputEl.value = this.gInput.inputEl.defaultValue;
                                initialUndoClasses(this.gInput.inputEl);
                            });
                        }
                        el.gInput.elInitialUndo = el.gInput.querySelector('.input-initial-undo');
                        if (el.gInput.elInitialUndo) {
                            el.gInput.elInitialUndo.gInput = el.gInput;
                            el.gInput.elInitialUndo.addEventListener('click', function (ev) {
                                this.gInput.inputEl.value = this.gInput.undo;
                                this.gInput.undo          = false;
                                initialUndoClasses(this.gInput.inputEl);
                            });
                        }
                        el.gInputLoaded = true;
                    }
                    break;
            }
            break;

        case 'SELECT':
            if (!el.gInputLoaded) {
                // console.log('loading ' + el.tagName + ' gInput');
                el.gInput           = el.parentNode;
                el.gInput.inputEl   = el;
                el.gInput.elInitial = el.gInput.querySelector('.input-initial');
                if (el.gInput.elInitial) {
                    el.gInput.elInitial.gInput = el.gInput;
                    el.gInput.elInitial.addEventListener('click', function (ev) {
                        this.gInput.undo = this.gInput.inputEl.value;
                        for (i = 0; i < this.gInput.inputEl.length; i++) {
                            if (this.gInput.inputEl[i].defaultSelected) {
                                this.gInput.inputEl.value = this.gInput.inputEl[i].value;
                                break;
                            }
                        }
                        initialUndoClasses(this.gInput.inputEl);
                    });
                }
                el.gInput.elInitialUndo = el.gInput.querySelector('.input-initial-undo');
                if (el.gInput.elInitialUndo) {
                    el.gInput.elInitialUndo.gInput = el.gInput;
                    el.gInput.elInitialUndo.addEventListener('click', function (ev) {
                        this.gInput.inputEl.value = this.gInput.undo;
                        this.gInput.undo          = false;
                        initialUndoClasses(this.gInput.inputEl);
                    });
                }
                el.gInputLoaded = true;
            }

            break;

        case 'BUTTON':
            break;

        case 'DATALIST':
            break;

    }
    initialUndoClasses(el);
}

function gjInputInitial(el, ev) {
    var gInput = el.parentNode;

}

function initialUndoClasses(el) {
    var i;
    var changed = false;
    var value   = el.value;
    switch (el.tagName) {
        case 'INPUT':
            switch (el.type) {
                case 'radio':
                    for (i = 0; i < el.gInput.radios.length; i++) {
                        if (el.gInput.radios[i].checked != el.gInput.radios[i].defaultChecked) {
                            changed = true;
                            value   = el.gInput.radios[i]
                            break;
                        }
                    }
                    break;
                default:
                    if (el.value != el.defaultValue) changed = true;
                    break;
            }
            break;
        case 'TEXTAREA':
            if (el.value != el.defaultValue) changed = true;
            break;
        case 'SELECT':
            for (i = 0; i < el.length; i++) {
                if (el[i].selected != el[i].defaultSelected) {
                    changed = true;
                    value   = el[i].selected;
                    break;
                }
            }
            break;
        case 'BUTTON':
            break;
        case 'DATALIST':
            break;
        case 'TRIX-EDITOR':
            if (el.value != el.defaultValue) changed = true;
            break;
    }
    // console.log('changed:', changed);
    if (changed) {
        el.gInput.classList.add('show-changed');
        el.gInput.classList.add('show-initial');
        el.gInput.classList.remove('show-initial-undo');
    } else if (el.gInput.undo && el.gInput.undo != value) {
        el.gInput.classList.remove('show-changed');
        el.gInput.classList.remove('show-initial');
        el.gInput.classList.add('show-initial-undo');
    } else {
        el.gInput.classList.remove('show-changed');
        el.gInput.classList.remove('show-initial');
        el.gInput.classList.remove('show-initial-undo');
    }

}


function gjSwitch(el, ev) {
    if (el.checked) {
        document.body.classList.remove(el.value);
        if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'show');
    } else {
        document.body.classList.add(el.value);
        if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'hide');
    }
}


function gjImageValidate(el) {
    let fileList = el.parentNode.querySelector('.upload-files');

    fileList.innerHTML = '';

    let maxTotal = 0;
    let maxSize  = 0;

    for (let i = 0; i < el.files.length; i++) {
        maxTotal += el.files[i].size;
        maxSize = Math.max(maxSize, el.files[i].size);

        let li           = document.createElement('li');
        li.innerHTML     = el.files[i].name;
        let liSize       = document.createElement('span');
        liSize.innerHTML = ' (' + gFileSize(el.files[i].size) + ')';
        li.appendChild(liSize);

        let errors = 0;
        if (el.files[i].size > el.dataset.maxsize) {
            liSize.classList = 'bold red';
            errors++;
        }
        if (maxSize > el.dataset.maxtotal) errors++;
        if (i >= el.dataset.maxcount) errors++;
        li.classList = (errors > 0) ? 'red' : 'green';

        fileList.appendChild(li);
    }

    let nodeMaxTotal = el.parentNode.querySelector('.info .maxtotal');
    let nodeMaxSize  = el.parentNode.querySelector('.info .maxsize');
    let nodeMaxCount = el.parentNode.querySelector('.info .maxcount');

    nodeMaxTotal.innerHTML = gFileSize(maxTotal);
    nodeMaxTotal.classList = 'maxtotal ' + (maxTotal > el.dataset.maxtotal ? 'red' : '');

    nodeMaxSize.innerHTML = gFileSize(maxSize);
    nodeMaxSize.classList = 'maxsize ' + (maxSize > el.dataset.maxsize ? 'red' : '');

    nodeMaxCount.innerHTML = el.files.length;
    nodeMaxCount.classList = 'maxcount ' + (el.files.length > el.dataset.maxcount ? 'red' : '');

    el.parentNode.parentNode.classList.remove('input-wrap-errors');
    el.parentNode.parentNode.querySelector('.input-errors').classList.add('hide');
}







/*************************/
/******  scrape.js  ******/
/*************************/


function gjImportJsonld(el, ev) {
    var url = el.previousElementSibling.value;
    var errorsEl = el.parentNode.querySelector('.input-errors');
    var infosEl = el.parentNode.querySelector('.input-infos');
    errorsEl.innerHTML = '';
    infosEl.innerHTML = '<li>' + t('Loading') + '</li>';

    if (!url) {
        if (errorsEl) {
            errorsEl.innerHTML = '<li>' + t('Empty') + '</li>';
            infosEl.innerHTML = '';
        }
        return;
    }
    // var regex = RegExp(/^https?:\/\/([^.]+\.)?facebook\./)
    // if (!regex.test(url)) {
    //     if (errorsEl) {
    //         errorsEl.innerHTML = '<li>' + t('Invalid url') + '</li>';
    //         infosEl.innerHTML = '';
    //     }
    //     return;
    // }
    var xhr = new XMLHttpRequest();
    xhr.errorsEl = errorsEl;
    xhr.infosEl = infosEl;

    xhr.onload = function() {
        if (this.status != 200) return;
        if (importRelationsJsonld == undefined) {
            this.errorsEl.innerHTML = '<li>' + t('Could not load') + '</li>';
            this.infosEl.innerHTML = '';
            return;
        }
        var jsonld = JSON.parse(this.responseText);
        // console.log(jsonld);
        if (jsonld.error) {
            this.errorsEl.innerHTML = '<li>' + jsonld.error + '</li>';
            this.infosEl.innerHTML = '';
            return;
        }
        if (!Array.isArray(jsonld)) jsonld = [jsonld];

        var changes = [];

        for (var jsonKey in jsonld) {
            var json = jsonld[jsonKey];
            // console.log(json);
            for (var resultName in importRelationsJsonld) {

                if (resultName.substr(0, 10) == 'add-module') {
                    var foundInJsonld = [];
                    for (var inputNameRaw in importRelationsJsonld[resultName]) {
                        var jsonldSearchRegex = importRelationsJsonld[resultName][inputNameRaw].match(/^@type-(\w+)+:(.+)$/);
                        if (!jsonldSearchRegex) continue;
                        if (json['@type'] != jsonldSearchRegex[1]) continue;
                        if (!json[jsonldSearchRegex[2]]) continue;
                        foundInJsonld.push({raw: inputNameRaw, clean: jsonldSearchRegex[2]});
                    }
                    // console.log(foundInJsonld);
                    if (foundInJsonld.length) {
                        var inputEl = document.getElementById(resultName);
                        if (!inputEl) continue;
                        var addedFields = gjCloneModuleInputs(inputEl);

                        for (var i = 0; i < foundInJsonld.length; i++) {
                            var inputName = foundInJsonld[i];
                            var inputNameNew = inputName.raw.replace('\]\[new-0\]\[', '][new-' + addedFields.groupId + '][');
                            var content = jsonPathToValue(json, inputName.clean);
                            if (!content) continue;
                            var inputEl = document.getElementsByName(inputNameNew)[0];
                            if (!inputEl) continue;
                            changes.push({el: inputEl, content: content});
                        }
                    }
                    continue;
                }
                var jsonldSearchRegex = importRelationsJsonld[resultName].match(/^@type-(\w+)+:(.+)$/);
                if (!jsonldSearchRegex) continue;
                if (json['@type'] != jsonldSearchRegex[1]) continue;
                var content = jsonPathToValue(json, jsonldSearchRegex[2]);
                if (!content) continue;
                var inputEl = document.getElementsByName(resultName)[0];
                if (!inputEl) continue;
                if (inputEl.value) continue;
                changes.push({el: inputEl, content: content});
            }
        }

        // console.log(changes);

        for (var i = 0; i < changes.length; i++) {
            var content = changes[i].content;
            var inputEl = changes[i].el;
            switch (inputEl.tagName) {
                case 'INPUT':
                case 'TEXTAREA':
                    // console.log(inputEl, results[datakey]);
                    if (inputEl.value == content) continue;
                    if (inputEl.classList.contains('input-trix')) {
                        var editor = inputEl.nextElementSibling.nextElementSibling.editor;
                        editor.recordUndoEntry('Content updated');
                        editor.setSelectedRange([0, editor.getDocument().getLength()])
                        content = '<p>' + content + '</p>';
                        content = content.replace(/\n\n+/g, '</p><p>');
                        content = content.replace(/\n/g, '<br>');
                        editor.insertHTML(content);
                        break;
                    }
                    switch (inputEl.type) {
                        case 'url':
                            content = decodeURI(content);
                            var regex = RegExp(/^https?:\/\/([^.]+\.)?facebook\./)
                            if (regex.test(content)) content = content.replace(/^https?:\/\/([^.]+)\./, 'https://www.');
                            break;
                    }


                    if (inputEl.classList.contains('input-date')) {
                        content = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                        var date = new Date(content);
                        var localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                        content = dateFormatDate(localeDate);
                    }
                    if (inputEl.classList.contains('input-time')) {
                        content = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                        var date = new Date(content);
                        var localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                        content = dateFormatTime(localeDate);
                    }
                    inputEl.value = content;
                    // trigger(inputEl, 'input');
                    break;

                case 'SELECT':
                    break;

                case 'BUTTON':
                    if (inputEl.value == content) continue;
                    inputEl.value = content;
                    trigger(inputEl, 'input');
                    break;

                case 'DATALIST':
                    break;

            }

        }
        this.infosEl.innerHTML = '<li>' + t('Imported') + ': ' + changes.length + '</li>';
        this.errorsEl.innerHTML = '';
    };
    xhr.onprogress = function(event) {
        if (!event.lengthComputable) return; // size unknown
        var percentComplete = event.loaded / event.total * 100;
        this.infosEl.innerHTML = '<li>' + percentComplete + '%</li>';
    };
    xhr.onerror = function() {
        this.errorsEl.innerHTML = '<li>' + t('Connection error') + '</li>';
        this.infosEl.innerHTML = '';
    };
    xhr.open('GET', '/edit/importer/jsonld?url=' + url);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
}


function gjImportYoutube(el, ev) {
    var url = el.previousElementSibling.value;
    var errorsEl = el.parentNode.querySelector('.input-errors');
    var infosEl = el.parentNode.querySelector('.input-infos');
    errorsEl.innerHTML = '';
    infosEl.innerHTML = '<li>' + t('Loading') + '</li>';

    if (!url) {
        errorsEl.innerHTML = '<li>' + t('Empty') + '</li>';
        infosEl.innerHTML = '';
        return;
    }

    var youtubeId = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
    if (youtubeId[1]) {
        youtubeId = youtubeId[1];
        infosEl.innerHTML = '<li>' + youtubeId + '</li>';
    } else {
        errorsEl.innerHTML = '<li>' + t('Invalid Youtube Id from url') + '</li>';
        infosEl.innerHTML = '';
    }

    var urlEmbed = 'https://www.youtube.com/embed/' + youtubeId;

    var xhr = new XMLHttpRequest();
    xhr.errorsEl = errorsEl;
    xhr.infosEl = infosEl;

    xhr.onload = function() {
        if (this.status != 200) {
            this.errorsEl.innerHTML = '<li>' + t('Error:') + ' ' + this.status + '</li>';
            this.infosEl.innerHTML = '';
        }
        if (importRelationsYoutube == undefined) {
            this.errorsEl.innerHTML = '<li>' + t('Could not load') + '</li>';
            this.infosEl.innerHTML = '';
            return;
        }
        var jsonld = JSON.parse(this.responseText);
        // console.log(jsonld);
        if (jsonld.error) {
            this.errorsEl.innerHTML = '<li>' + jsonld.error + '</li>';
            this.infosEl.innerHTML = '';
            return;
        }
        if (!Array.isArray(jsonld)) jsonld = [jsonld];

        var changes = [];

        for (var jsonKey in jsonld) {
            var json = jsonld[jsonKey];
            console.log(json);
            for (var resultName in importRelationsYoutube) {

                if (resultName.substr(0, 10) == 'add-module') {
                    var foundInJsonld = [];
                    for (var inputNameRaw in importRelationsYoutube[resultName]) {
                        var jsonldSearchRegex = importRelationsYoutube[resultName][inputNameRaw].match(/^@type-(\w+)+:(.+)$/);
                        if (!jsonldSearchRegex) continue;
                        if (json['@type'] != jsonldSearchRegex[1]) continue;
                        if (!json[jsonldSearchRegex[2]]) continue;
                        foundInJsonld.push({raw: inputNameRaw, clean: jsonldSearchRegex[2]});
                    }
                    // console.log(foundInJsonld);
                    if (foundInJsonld.length) {
                        var inputEl = document.getElementById(resultName);
                        if (!inputEl) continue;
                        var addedFields = gjCloneModuleInputs(inputEl);

                        for (var i = 0; i < foundInJsonld.length; i++) {
                            var inputName = foundInJsonld[i];
                            var inputNameNew = inputName.raw.replace('\]\[new-0\]\[', '][new-' + addedFields.groupId + '][');
                            var content = jsonPathToValue(json, inputName.clean);
                            if (!content) continue;
                            var inputEl = document.getElementsByName(inputNameNew)[0];
                            if (!inputEl) continue;
                            changes.push({el: inputEl, content: content});
                        }
                    }
                    continue;
                }
                var jsonldSearchRegex = importRelationsYoutube[resultName].match(/^@type-(\w+)+:(.+)$/);
                if (!jsonldSearchRegex) continue;
                if (json['@type'] != jsonldSearchRegex[1]) continue;
                var content = jsonPathToValue(json, jsonldSearchRegex[2]);
                if (!content) continue;
                var inputEl = document.getElementsByName(resultName)[0];
                if (!inputEl) continue;
                if (inputEl.value) continue;
                changes.push({el: inputEl, content: content});
            }
        }

        // console.log(changes);

        for (var i = 0; i < changes.length; i++) {
            var content = changes[i].content;
            var inputEl = changes[i].el;
            switch (inputEl.tagName) {
                case 'INPUT':
                case 'TEXTAREA':
                    // console.log(inputEl, results[datakey]);
                    if (inputEl.value == content) continue;
                    if (inputEl.classList.contains('input-trix')) {
                        var editor = inputEl.nextElementSibling.nextElementSibling.editor;
                        editor.recordUndoEntry('Content updated');
                        editor.setSelectedRange([0, editor.getDocument().getLength()])
                        content = '<p>' + content + '</p>';
                        content = content.replace(/\n\n+/g, '</p><p>');
                        content = content.replace(/\n/g, '<br>');
                        editor.insertHTML(content);
                        break;
                    }
                    switch (inputEl.type) {
                        case 'url':
                            content = decodeURI(content);
                            var regex = RegExp(/^https?:\/\/([^.]+\.)?facebook\./)
                            if (regex.test(content)) content = content.replace(/^https?:\/\/([^.]+)\./, 'https://www.');
                            break;
                    }


                    if (inputEl.classList.contains('input-date')) {
                        content = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                        var date = new Date(content);
                        var localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                        content = dateFormatDate(localeDate);
                    }
                    if (inputEl.classList.contains('input-time')) {
                        content = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                        var date = new Date(content);
                        var localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                        content = dateFormatTime(localeDate);
                    }
                    inputEl.value = content;
                    // trigger(inputEl, 'input');
                    break;

                case 'SELECT':
                    break;

                case 'BUTTON':
                    if (inputEl.value == content) continue;
                    inputEl.value = content;
                    trigger(inputEl, 'input');
                    break;

                case 'DATALIST':
                    break;

            }

        }
        this.infosEl.innerHTML = '<li>' + t('Imported') + ': ' + changes.length + '</li>';
        this.errorsEl.innerHTML = '';
    };
    xhr.onprogress = function(event) {
        if (!event.lengthComputable) return; // size unknown
        var percentComplete = event.loaded / event.total * 100;
        this.infosEl.innerHTML = '<li>' + percentComplete + '%</li>';
    };
    xhr.onerror = function() {
        this.errorsEl.innerHTML = '<li>' + t('Connection error') + '</li>';
        this.infosEl.innerHTML = '';
    };
    xhr.open('GET', '/edit/importer/youtube?id=' + youtubeId);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
}


function gjImportVimeo(el, ev) {
    var url = el.previousElementSibling.value;
    var errorsEl = el.parentNode.querySelector('.input-errors');
    var infosEl = el.parentNode.querySelector('.input-infos');
    errorsEl.innerHTML = '';
    infosEl.innerHTML = '<li>' + t('Loading') + '</li>';

    if (!url) {
        errorsEl.innerHTML = '<li>' + t('Empty') + '</li>';
        infosEl.innerHTML = '';
        return;
    }

    var vimeoId = url.match(/vimeo\.com\/(\d+)$/i);

    if (vimeoId && vimeoId[1]) {
        vimeoId = vimeoId[1];
        infosEl.innerHTML = '<li>' + vimeoId + '</li>';
    } else {
        errorsEl.innerHTML = '<li>' + t('Invalid Vimeo Id from url') + '</li>';
        infosEl.innerHTML = '';
        return;
    }

    var urlEmbed = 'https://www.vimeo.com/embed/' + vimeoId;

    var xhr = new XMLHttpRequest();
    xhr.errorsEl = errorsEl;
    xhr.infosEl = infosEl;

    xhr.onload = function() {
        if (this.status != 200) {
            this.errorsEl.innerHTML = '<li>' + t('Error:') + ' ' + this.status + '</li>';
            this.infosEl.innerHTML = '';
        }
        if (importRelationsVimeo == undefined) {
            this.errorsEl.innerHTML = '<li>' + t('Could not load') + '</li>';
            this.infosEl.innerHTML = '';
            return;
        }
        var jsonld = JSON.parse(this.responseText);
        console.log(jsonld);
        if (jsonld.error) {
            this.errorsEl.innerHTML = '<li>' + jsonld.error + '</li>';
            this.infosEl.innerHTML = '';
            return;
        }
        var jsonData = jsonld.data;
        if (!Array.isArray(jsonData)) jsonData = [jsonData];

        var changes = [];

        for (var jsonKey in jsonData) {
            var json = jsonData[jsonKey];
            console.log(json);
            for (var resultName in importRelationsVimeo) {

                if (resultName.substr(0, 10) == 'add-module') {
                    var foundInJsonld = [];
                    for (var inputNameRaw in importRelationsVimeo[resultName]) {
                        var jsonldSearchRegex = importRelationsVimeo[resultName][inputNameRaw].match(/^@type-(\w+)+:(.+)$/);
                        if (!jsonldSearchRegex) continue;
                        if (json['@type'] != jsonldSearchRegex[1]) continue;
                        if (!json[jsonldSearchRegex[2]]) continue;
                        foundInJsonld.push({raw: inputNameRaw, clean: jsonldSearchRegex[2]});
                    }
                    // console.log(foundInJsonld);
                    if (foundInJsonld.length) {
                        var inputEl = document.getElementById(resultName);
                        if (!inputEl) continue;
                        var addedFields = gjCloneModuleInputs(inputEl);

                        for (var i = 0; i < foundInJsonld.length; i++) {
                            var inputName = foundInJsonld[i];
                            var inputNameNew = inputName.raw.replace('\]\[new-0\]\[', '][new-' + addedFields.groupId + '][');
                            var content = jsonPathToValue(json, inputName.clean);
                            if (!content) continue;
                            var inputEl = document.getElementsByName(inputNameNew)[0];
                            if (!inputEl) continue;
                            changes.push({el: inputEl, content: content});
                        }
                    }
                    continue;
                }
                var jsonldSearchRegex = importRelationsVimeo[resultName].match(/^@type-(\w+)+:(.+)$/);
                if (!jsonldSearchRegex) continue;
                if (json['@type'] != jsonldSearchRegex[1]) continue;
                var content = jsonPathToValue(json, jsonldSearchRegex[2]);
                if (!content) continue;
                var inputEl = document.getElementsByName(resultName)[0];
                if (!inputEl) continue;
                if (inputEl.value) continue;
                changes.push({el: inputEl, content: content});
            }
        }

        // console.log(changes);

        for (var i = 0; i < changes.length; i++) {
            var content = changes[i].content;
            var inputEl = changes[i].el;
            switch (inputEl.tagName) {
                case 'INPUT':
                case 'TEXTAREA':
                    // console.log(inputEl, results[datakey]);
                    if (inputEl.value == content) continue;
                    if (inputEl.classList.contains('input-trix')) {
                        var editor = inputEl.nextElementSibling.nextElementSibling.editor;
                        editor.recordUndoEntry('Content updated');
                        editor.setSelectedRange([0, editor.getDocument().getLength()])
                        content = '<p>' + content + '</p>';
                        content = content.replace(/\n\n+/g, '</p><p>');
                        content = content.replace(/\n/g, '<br>');
                        editor.insertHTML(content);
                        break;
                    }
                    switch (inputEl.type) {
                        case 'url':
                            content = decodeURI(content);
                            var regex = RegExp(/^https?:\/\/([^.]+\.)?facebook\./)
                            if (regex.test(content)) content = content.replace(/^https?:\/\/([^.]+)\./, 'https://www.');
                            break;
                    }


                    if (inputEl.classList.contains('input-date')) {
                        content = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                        var date = new Date(content);
                        var localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                        content = dateFormatDate(localeDate);
                    }
                    if (inputEl.classList.contains('input-time')) {
                        content = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                        var date = new Date(content);
                        var localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                        content = dateFormatTime(localeDate);
                    }
                    inputEl.value = content;
                    // trigger(inputEl, 'input');
                    break;

                case 'SELECT':
                    break;

                case 'BUTTON':
                    if (inputEl.value == content) continue;
                    inputEl.value = content;
                    trigger(inputEl, 'input');
                    break;

                case 'DATALIST':
                    break;

            }

        }
        this.infosEl.innerHTML = '<li>' + t('Imported') + ': ' + changes.length + '</li>';
        this.errorsEl.innerHTML = '';
    };
    xhr.onprogress = function(event) {
        if (!event.lengthComputable) return; // size unknown
        var percentComplete = event.loaded / event.total * 100;
        this.infosEl.innerHTML = '<li>' + percentComplete + '%</li>';
    };
    xhr.onerror = function() {
        this.errorsEl.innerHTML = '<li>' + t('Connection error') + '</li>';
        this.infosEl.innerHTML = '';
    };
    xhr.open('GET', '/edit/importer/vimeo?id=' + vimeoId);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
}



function jsonPathToValue(jsonData, path) {
    if (!(jsonData instanceof Object) || typeof (path) === 'undefined') {
        throw 'Not valid argument:jsonData:' + jsonData + ', path:' + path;
    }
    path = path.replace(/\[(\w+)\]/g, '.$1'); // convert indexes to properties
    path = path.replace(/^\./, ''); // strip a leading dot
    var pathArray = path.split('.');
    for (var i = 0, n = pathArray.length; i < n; ++i) {
        var key = pathArray[i];
        if (key in jsonData) {
            if (jsonData[key] !== null) {
                jsonData = jsonData[key];
            } else {
                return null;
            }
        } else {
            return false;
        }
    }
    return jsonData;
}




function dateFormatDate(date) {
    var day = ('0' + date.getDate()).slice(-2);
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var year = date.getFullYear();
    return year + '-' + month + '-' + day;
}
function dateFormatTime(date) {
    var hour = ('0' + date.getHours()).toString().slice(-2);
    var minute = ('0' + date.getMinutes()).toString().slice(-2);
    return hour + ':' + minute;
}
function dateFormatTimestamp(date) {
    var day = ('0' + date.getDate()).slice(-2);
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var year = date.getFullYear();
    var hour = ('0' + date.getHours()).toString().slice(-2);
    var minute = ('0' + date.getMinutes()).toString().slice(-2);
    return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + '00';
}




/***********************/
/******  text.js  ******/
/***********************/

function firstDiffInStrings(a, b) {
    var i = 0;
    if (a === b) return -1;
    while (a[i] === b[i]) i++;
    return i;
}

function slugifyString(text, separator = '-') {
    var text = removeDiacritics(text);
    text     = text.replace(/[^a-z0-9\-]+/g, separator);
    text     = text.replace(/-+/g, separator);
    text     = text.replace(/^-+/, '');
    return text;
}


function gjUnique() {
    return Math.random().toString(10).substring(2, 10);
}


function t(text) {
    var lang = document.documentElement.lang;
    if (translations[text] == undefined) return text;
    if (translations[text][lang] == undefined) return text;
    return translations[text][lang];
}


var translations = {
    'Empty': {
        'pt': 'Vazio',
        'es': 'Vacio',
    },
    'Invalid url': {
        'pt': 'Url inválido',
        'es': 'Url invalido',
    },
    'Imported': {
        'pt': 'Importado',
        'es': 'Importado',
    },
}


function gFileSize(bytes, decimals = 2) {
    if (bytes < 1024) return bytes + ' B'
    const size   = [' B', ' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
    const factor = Math.floor((String(bytes).length - 1) / 3);
    return (bytes / Math.pow(1024, factor)).toFixed(decimals) + (size[factor] ?? '');
}





