'use strict';


// polyfills

if (!String.prototype.padStart) {
    String.prototype.padStart = function padStart(targetLength, padString) {
        targetLength = targetLength >> 0; //truncate if number, or convert non-number to 0;
        padString = String(typeof padString !== 'undefined' ? padString : ' ');
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


// globals

var gjCheckboxes = [];
var gjSwitchTargets = [];
var gjRadios = [];
var gjMsgBoxes = [];
var gjInputSlugs = [];
var gjFilters = [];
var gjFilterTextsEmpty = [];
var gjTextareas = [];
var gjInputs = [];
var gjResizeTimeout = null;
var gjImageSelector = [];
var gjImageSelectorActiveInput = null;
var gjScrollPosition = 0;
var filterData = ['pageCurrent', 'pageFirst', 'pagePrev', 'pageNext', 'pageLast', 'itemsPerPage', 'rowsFiltered', 'rowsTotal'];

window.addEventListener('DOMContentLoaded', function(event) {
    var iOS = !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
    var safari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    if (iOS && safari) document.querySelector('meta[name=viewport]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0');
    gjLoad();
});


function gjSwitch(el, ev) {
    if (el.checked) {
        document.body.classList.remove(el.value);
        if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'show');
    } else {
        document.body.classList.add(el.value);
        if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'hide');
    }
}

function gjLoad() {
    var i, j, k;

    // checkbox .active toggling
    gjCheckboxes = document.querySelectorAll('.btn-checkbox input');
    for (i = 0; i < gjCheckboxes.length; i++) {
        gjCheckboxes[i].addEventListener('change', function(ev) {
            if (ev.target.checked) {
                ev.target.parentNode.classList.add('active');
            } else {
                ev.target.parentNode.classList.remove('active');
            }
        });
    }

    // close message boxes
    gjMsgBoxes = document.querySelectorAll('.x-close');
    for (i = 0; i < gjMsgBoxes.length; i++) {
        gjMsgBoxes[i].addEventListener('click', function(ev) {
            ev.target.parentNode.classList.add('hide');
        });
    }

    // [command + s] sends form
    window.addEventListener('keydown', function(ev) {
        if (ev.keyCode == 83 && (navigator.platform.match('Mac') ? ev.metaKey : ev.ctrlKey)) {
            ev.preventDefault();
            if (document.forms[0].id != 'logout')
                document.forms[0].submit();
        }
    });

    // [escape] key closes imageSelect
    gjImageSelector = document.getElementById('image-select');
    window.addEventListener('keydown', function(ev) {
        if (ev.key == 'Escape') gjImageSelectorClose();
    });

    gjTextareas = document.getElementsByTagName('textarea');
    gjResizeTextareas();


    // prepare form pagination
    for (i = 0; i < document.forms.length; i++) {
        document.forms[i].pagination = [];
        filterData.forEach(function(el) {
            document.forms[i].pagination[el] = document.forms[i].querySelectorAll('.' + el);
        });
    }

}

function filter(el, ev) {
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
    event.preventDefault(ev);
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

function trigger(el, evName, bubbles = false) {
    if (bubbles) var ev = new Event(evName, {bubbles: true});
    else         var ev = new Event(evName);
    el.dispatchEvent(ev);
}


function gjImageSelectorOpen(el, imgType) {
    gjImageSelector.scrollPrev = window.scrollY;

    gjImageSelectorActiveInput = el.previousElementSibling;
    gjImageSelector.classList.remove('hide');

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
    var selectedImg = el.children[0].children[1];
    var selectorSpacer = gjImageSelectorActiveInput.nextElementSibling.children[0];
    var selectorImg = gjImageSelectorActiveInput.nextElementSibling.children[1];

    gjImageSelectorClose();
    gjImageSelectorActiveInput.value = el.id;
    selectorImg.src = selectedImg.src;
    selectorImg.parentNode.classList.remove('empty');
    selectorImg.setAttribute('width', selectedImg.getAttribute('width'));
    selectorImg.setAttribute('height', selectedImg.getAttribute('height'));
    selectorSpacer.style.maxWidth = selectedImg.getAttribute('width') + 'px'
    selectorSpacer.style.maxHeight = selectedImg.getAttribute('height') + 'px'
    selectorSpacer.children[0].style.paddingBottom = (selectedImg.getAttribute('height') / selectedImg.getAttribute('width') * 100) + '%';
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

        console.log('inp', el);
    el.onload = function() {
        this.onload = null;
        this.onerror = null;
        this.parentNode.classList.remove('waiting', 'loading')
    };
}

function gjModuleMoveUp(el, pos ) {
    var target = document.getElementById(el.dataset.target);
    var groups = target.parentNode;
    if (target.previousElementSibling)
        groups.insertBefore(target, target.previousElementSibling);
    gjModuleCountPositions(groups);
}

function gjModuleMoveDown(el) {
    var target = document.getElementById(el.dataset.target);
    var groups = target.parentNode;
    if (target.nextElementSibling)
        groups.insertBefore(target.nextElementSibling, target);
    gjModuleCountPositions(groups);
}

function gjModuleMove(el, pos = null) {
    if (!pos) pos = el.previousElementSibling.value;
    var target = document.getElementById(el.dataset.target);
    var parent = target.parentNode;
    var groups = parent.children;

    if (pos >= groups.length) {
        parent.appendChild(target);
    } else {
        var j = 1;
        for (i = 0; i < groups.length; i++) {
            if (groups[i].dataset == undefined || groups[i].dataset.disabled) continue;
            if (groups[i] == target) continue;

            if (j == pos) {
                parent.insertBefore(target, groups[i]);
            }
            j++;
        }
    }


    gjModuleCountPositions(target.parentNode);
}

function gjCloneModuleInputs(el) {
    var groupId = gjUnique();
    var newGroup = document.getElementById(el.dataset.target).cloneNode(true);
    var where = document.getElementById(el.dataset.where);

    newGroup.classList.remove('hide');
    newGroup.classList.add('module-field-group-new');
    newGroup.id = newGroup.id + '-' + groupId;
    newGroup.groupId = groupId;

    var inputs = newGroup.getElementsByTagName('input');
    var buttons = newGroup.getElementsByTagName('button');
    var selects = newGroup.getElementsByTagName('select');
    var textareas = newGroup.getElementsByTagName('textarea');
    for (i = inputs.length - 1; i >= 0; i--) {
        inputs[i].name = inputs[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        inputs[i].disabled = false;
        if (inputs[i].dataset.target != undefined)
            inputs[i].dataset.target = newGroup.id;
    }
    for (i = selects.length - 1; i >= 0; i--) {
        selects[i].name = selects[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        selects[i].disabled = false;
        if (selects[i].dataset.target != undefined)
            selects[i].dataset.target = newGroup.id;
    }
    for (i = buttons.length - 1; i >= 0; i--) {
        buttons[i].name = buttons[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        buttons[i].disabled = false;
        if (buttons[i].dataset.target != undefined)
            buttons[i].dataset.target = newGroup.id;
    }
    for (i = textareas.length - 1; i >= 0; i--) {
        textareas[i].name = textareas[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        textareas[i].disabled = false;
        if (textareas[i].dataset.target != undefined)
            textareas[i].dataset.target = newGroup.id;
    }

    where.prepend(newGroup);
    gjModuleCountPositions(where);
    return newGroup;
}


function gjDeleteModuleInputs(el) {
    var check = el.nextElementSibling;
    var row = document.getElementById(el.dataset.target);
    var group = document.getElementById(el.dataset.target).parentNode;
    var inputs = row.getElementsByTagName('input');
    var buttons = row.getElementsByTagName('button');
    var selects = row.getElementsByTagName('select');
    var textareas = row.getElementsByTagName('textarea');

    if (row.classList.contains('module-field-group-new')) {
        group.removeChild(row);
        return;
    }

    if (row.dataset.disabled) {
        row.removeAttribute('data-disabled');
        row.classList.remove('module-field-group-delete');
        for (i = inputs.length - 1; i >= 0; i--)
            inputs[i].disabled = false;
        for (i = selects.length - 1; i >= 0; i--)
            selects[i].disabled = false;
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
    var groups = el.childNodes;
    var j = 0;
    for (i = 0; i < groups.length; i++) {
        if (groups[i].dataset == undefined || groups[i].dataset.disabled) continue;
        j++;
        if (groups[i].querySelector('.module-position'))
            groups[i].querySelector('.module-position').value = j;
    }
}

function getChildren(n, skipMe){
    var r = [];
    for ( ; n; n = n.nextSibling )
       if ( n.nodeType == 1 && n != skipMe)
          r.push( n );
    return r;
};

function getSiblings(n) {
    return getChildren(n.parentNode.firstChild, n);
}




// only use focus on keyboard
document.addEventListener('keydown', function(ev) {
    if (ev.keyCode === 9) { // tab
        document.body.classList.add('show-focus-outlines');
    }
    if (ev.keyCode === 27) { // esc
        document.body.classList.remove('show-focus-outlines');
    }
});

document.addEventListener('mousedown', function(ev) {
    document.body.classList.remove('show-focus-outlines');
});




// "what?" version ... http://jsperf.com/diacritics/12
var textDecoded = document.createElement('textarea');
function removeDiacritics(text) {
    text = text.toLowerCase();
    text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    textDecoded.innerHTML = text;
    text = textDecoded.value;
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

function disableEnterKey(ev) {
    if (ev.key == 'Enter') {
        ev.preventDefault();
        return false;
    }
}


function gjInputMod(el, ev) {
    var text = el.value;
    var caretPos = el.selectionStart;
    if (ev.altKey && ev.key == 'ArrowLeft') {
        ev.preventDefault();
        var textBefore = text.substring(0, caretPos);
        var re = /(\w+)\W+\w*$/;
        var match = re.exec(textBefore);
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
        var re = /^\w*\W(\w+)/;
        var match = re.exec(textAfter);
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
            number = number.toString().padStart(foundPos[1], '0');
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
            number = number.toString().padStart(foundPos[1], '0');
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
    var textAfter = text.substring(caret);

    var re = new RegExp('[' + pattern + ']*$');
    var matchBefore = textBefore.match(re);
    re = new RegExp('^[' + pattern + ']*');
    var matchAfter = textAfter.match(re);
    if (matchBefore[0].length + matchAfter[0].length > 0)
        return [matchBefore.index, matchBefore[0].length + matchAfter[0].length];
    return false;
}

function gjInputFormat(el, type) {
    var caretOld = el.selectionStart;
    var caretNew = el.selectionStart;
    var valOld = el.value;
    var valNew = el.value;

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


function firstDiffInStrings(a, b) {
    var i = 0;
    if (a === b) return -1;
    while (a[i] === b[i]) i++;
    return i;
}

function slugifyString(text, separator = '-') {
    var text = removeDiacritics(text);
    text = text.replace(/[^a-z0-9\-]+/g, separator);
    text = text.replace(/-+/g, separator);
    text = text.replace(/^-+/, '');
    return text;
}


// on window resize with debounce
var resizeFunction = function() {
    gjResizeTextareas();
};
window.onresize = function(){
   if(gjResizeTimeout != null) clearTimeout(gjResizeTimeout);
   gjResizeTimeout = setTimeout(resizeFunction, 100);
}



function gjUnique() {
    return Math.random().toString(10).substring(2, 10);
};


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
    var regex = RegExp(/^https?:\/\/([^.]+\.)?facebook\./)
    if (!regex.test(url)) {
        if (errorsEl) {
            errorsEl.innerHTML = '<li>' + t('Invalid url') + '</li>';
            infosEl.innerHTML = '';
        }
        return;
    }
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
    var regex = RegExp(/^https?:\/\/([^.]+\.)?youtube\./)
    if (!regex.test(url)) {
        errorsEl.innerHTML = '<li>' + t('Invalid url') + '</li>';
        infosEl.innerHTML = '';
        return;
    }

    var youtubeId = url.match(/watch\?v=([\w]+)/);
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



function gjInputChange(el, ev) {
    var i;

    switch (el.tagName) {
        case 'INPUT':
        case 'TEXTAREA':
            switch (el.type) {
                case 'radio':
                    if (!el.gInputLoaded) {
                        // console.log('loading ' + el.tagName + ' ' + el.type + ' gInput');
                        el.gInput = el.parentNode.parentNode;
                        el.gInput.radios = el.gInput.querySelectorAll('input[name="' + el.name + '"]');
                        el.gInput.labels = el.gInput.querySelectorAll('label');
                        el.gInput.elInitial = el.gInput.querySelector('.input-initial');
                        if (el.gInput.elInitial) {
                            el.gInput.elInitial.gInput = el.gInput;
                            el.gInput.elInitial.addEventListener('click', function(ev) {
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
                            el.gInput.elInitialUndo.addEventListener('click', function(ev) {
                                this.gInput.value = this.gInput.undo;
                                this.gInput.undo = false;
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
                            el.gInput.radios[i].gInput = el.gInput;
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
                        el.gInput = el.parentNode;
                        el.gInput.inputEl = el;
                        el.gInput.elInitial = el.gInput.querySelector('.input-initial');
                        if (el.gInput.elInitial) {
                            el.gInput.elInitial.gInput = el.gInput;
                            el.gInput.elInitial.addEventListener('click', function(ev) {
                                this.gInput.undo = this.gInput.inputEl.value;
                                this.gInput.inputEl.value = this.gInput.inputEl.defaultValue;
                                initialUndoClasses(this.gInput.inputEl);
                            });
                        }
                        el.gInput.elInitialUndo = el.gInput.querySelector('.input-initial-undo');
                        if (el.gInput.elInitialUndo) {
                            el.gInput.elInitialUndo.gInput = el.gInput;
                            el.gInput.elInitialUndo.addEventListener('click', function(ev) {
                                this.gInput.inputEl.value = this.gInput.undo;
                                this.gInput.undo = false;
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
                el.gInput = el.parentNode;
                el.gInput.inputEl = el;
                el.gInput.elInitial = el.gInput.querySelector('.input-initial');
                if (el.gInput.elInitial) {
                    el.gInput.elInitial.gInput = el.gInput;
                    el.gInput.elInitial.addEventListener('click', function(ev) {
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
                    el.gInput.elInitialUndo.addEventListener('click', function(ev) {
                        this.gInput.inputEl.value = this.gInput.undo;
                        this.gInput.undo = false;
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
    var value = el.value;
    switch (el.tagName) {
        case 'INPUT':
            switch (el.type) {
                case 'radio':
                    for (i = 0; i < el.gInput.radios.length; i++) {
                        if (el.gInput.radios[i].checked != el.gInput.radios[i].defaultChecked) {
                            changed = true;
                            value = el.gInput.radios[i]
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
                    value = el[i].selected;
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

document.addEventListener('trix-before-initialize', function(ev) {
    ev.target.addEventListener('keydown', function(ev) {
        if (ev.shiftKey && ev.key == 'Enter') {
            ev.target.editor.recordUndoEntry('Shift+Enter');
            ev.target.editor.insertHTML('<br><br>');
            ev.preventDefault();
        }
    });
});


document.addEventListener('trix-change', function(ev) {
    var editor = ev.target;
    if (!editor.gInputLoaded) {
        // console.log('loading ' + editor.tagName + ' gInput');
        editor.gInput = editor.parentNode;
        editor.gInputLoaded = true;
    }
    initialUndoClasses(editor);
});


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
