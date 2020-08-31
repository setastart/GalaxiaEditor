var gjInput = {

    trixCharWordCount: function(trixEl) {
        let lenEl = trixEl.parentNode.querySelector('.input-len');
        if (lenEl) {
            let text = trixEl.editor.getDocument().toString().trim();

            if (text.length === 0) {
                lenEl.innerHTML = 0;
            } else {
                let words       = text.split(/\s+/).length;
                lenEl.innerHTML = text.length + ' / ' + words;
            }
        }
    },



}




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


function gjInputChange(el) {
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
                    if (el.maxLength) {
                        let len = el.previousElementSibling?.querySelector('.input-len');
                        if (len) len.innerHTML = el.value.length;
                    }

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



