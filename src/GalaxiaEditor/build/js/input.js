let gjInput = {

    // "what?" version ... http://jsperf.com/diacritics/12
    decoder: document.createElement('textarea'),


    trixCharWordCount: function(trixEl) {
        let lenEl = trixEl.parentNode.querySelector('.input-len');
        if (lenEl) {
            let text = trixEl.editor.getDocument().toString().trim();

            if (text.length === 0) {
                lenEl.innerHTML = '0 ❖ 0';
            } else {
                let words       = text.split(/\s+/).length;
                lenEl.innerHTML = text.length + ' ❖ ' + words;
            }
        }
    },


    removeDiacritics: function(text) {
        text                   = text.toLowerCase();
        text                   = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        this.decoder.innerHTML = text;
        text                   = this.decoder.value;
        return text;
    },


    textareaResize: function() {
        for (let i = 0; i < gjTextareas.length; i++) {
            gjInput.textareaAutoGrow(gjTextareas[i]);
        }
    },


    textareaAutoGrow: function(el) {
        if (el.scrollHeight > 34) {
            el.style.height = '';
            el.style.height = (el.scrollHeight + 8) + 'px';
        } else {
            el.style.height = '';
        }
        console.log(el.style.height);
    },


    mod: function(el, ev) {
        const text     = el.value;
        const caretPos = el.selectionStart;
        let re, match, foundPos, number;

        if (!ev.altKey) return;

        switch (ev.key) {
            case 'ArrowLeft':
                ev.preventDefault();
                let textBefore = text.substring(0, caretPos);
                re             = /(\w+)\W+\w*$/;
                match          = re.exec(textBefore);
                if (!match) {
                    if (text[0].match(/\W/))
                        el.setSelectionRange(0, 0);
                    return;
                }
                el.setSelectionRange(textBefore.length - match[0].length, textBefore.length - match[0].length + match[1].length);
                break;

            case 'ArrowRight':
                ev.preventDefault();
                let textAfter = text.substring(caretPos);
                re            = /^\w*\W(\w+)/;
                match         = re.exec(textAfter);
                if (!match) {
                    if (text[text.length - 1].match(/\W/))
                        el.setSelectionRange(text.length, text.length);
                    return;
                }
                el.setSelectionRange(caretPos + match[0].length - match[1].length, caretPos + match[0].length);
                break;

            case 'ArrowDown':
                ev.preventDefault();
                foundPos = gjInput.getTextAroundCaret(text, caretPos, '0-9');
                if (foundPos) {
                    number = +text.substring(foundPos[0], foundPos[0] + foundPos[1]);
                    if (ev.shiftKey) number -= 10;
                    else number--;
                    if (number < 0) number = 0;
                    number   = number.toString().padStart(foundPos[1], '0');
                    el.value = text.substring(0, foundPos[0]) + number.toString() + text.substring(foundPos[0] + foundPos[1]);
                    el.setSelectionRange(foundPos[0], foundPos[0] + number.toString().length);
                }
                break;

            case 'ArrowUp':
                ev.preventDefault();
                foundPos = gjInput.getTextAroundCaret(text, caretPos, '0-9');
                if (foundPos) {
                    number = +text.substring(foundPos[0], foundPos[0] + foundPos[1]);
                    if (ev.shiftKey) number += 10;
                    else number++;
                    if (number < 0) number = 0;
                    number   = number.toString().padStart(foundPos[1], '0');
                    el.value = text.substring(0, foundPos[0]) + number.toString() + text.substring(foundPos[0] + foundPos[1]);
                    el.setSelectionRange(foundPos[0], foundPos[0] + number.toString().length);
                }
                break;
        }
    },


    setCaretPosition: function(el, pos) {
        el.setSelectionRange(pos, pos);
    },


    getTextAroundCaret: function(text, caret, pattern) {
        const textBefore = text.substring(0, caret);
        const textAfter  = text.substring(caret);

        const matchBefore = textBefore.match(new RegExp('[' + pattern + ']*$'));
        const matchAfter  = textAfter.match(new RegExp('^[' + pattern + ']*'));

        if (matchBefore[0].length + matchAfter[0].length > 0) {
            return [matchBefore.index, matchBefore[0].length + matchAfter[0].length];
        }
        return false;
    },


    format: function(el, type) {
        const caretOld = el.selectionStart;
        const valOld   = el.value;
        let caretNew   = el.selectionStart;
        let valNew     = el.value;

        switch (type) {
            case 'slug':
                valNew = slugifyString(valNew, '-');
                if (valOld[firstDiffInStrings(valOld, valNew) - 1] === '-')
                    caretNew = caretOld - 1;
                break;

            case 'date':
                valNew = slugifyString(valNew, '-');
                valNew = valNew.replace(/[^0-9\-]+/g, '');

                if (valOld[firstDiffInStrings(valOld, valNew) - 1] === '-')
                    caretNew = caretOld - 1;
                break;

            case 'time':
                valNew = slugifyString(valNew, ':');
                valNew = valNew.replace(/[^0-9\:]+/g, '');

                if (valOld[firstDiffInStrings(valOld, valNew) - 1] === ':')
                    caretNew = caretOld - 1;
                break;
        }

        if (valNew !== valOld) {
            el.value = valNew;
            gjInput.setCaretPosition(el, caretNew);
        }
    },


    change: function(el) {
        let i;

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
                                el.gInput.elInitial.addEventListener('click', function() {
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
                                el.gInput.elInitialUndo.addEventListener('click', function() {
                                    this.gInput.value = this.gInput.undo;
                                    this.gInput.undo  = false;
                                    for (i = 0; i < this.gInput.radios.length; i++) {
                                        if (this.gInput.radios[i].value === this.gInput.value) {
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
                                el.gInput.elInitial.addEventListener('click', function() {
                                    this.gInput.undo          = this.gInput.inputEl.value;
                                    this.gInput.inputEl.value = this.gInput.inputEl.defaultValue;
                                    gjInput.initialUndoClasses(this.gInput.inputEl);
                                });
                            }
                            el.gInput.elInitialUndo = el.gInput.querySelector('.input-initial-undo');
                            if (el.gInput.elInitialUndo) {
                                el.gInput.elInitialUndo.gInput = el.gInput;
                                el.gInput.elInitialUndo.addEventListener('click', function() {
                                    this.gInput.inputEl.value = this.gInput.undo;
                                    this.gInput.undo          = false;
                                    gjInput.initialUndoClasses(this.gInput.inputEl);
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
                        el.gInput.elInitial.addEventListener('click', function() {
                            this.gInput.undo = this.gInput.inputEl.value;
                            for (i = 0; i < this.gInput.inputEl.length; i++) {
                                if (this.gInput.inputEl[i].defaultSelected) {
                                    this.gInput.inputEl.value = this.gInput.inputEl[i].value;
                                    break;
                                }
                            }
                            gjInput.initialUndoClasses(this.gInput.inputEl);
                        });
                    }
                    el.gInput.elInitialUndo = el.gInput.querySelector('.input-initial-undo');
                    if (el.gInput.elInitialUndo) {
                        el.gInput.elInitialUndo.gInput = el.gInput;
                        el.gInput.elInitialUndo.addEventListener('click', function() {
                            this.gInput.inputEl.value = this.gInput.undo;
                            this.gInput.undo          = false;
                            gjInput.initialUndoClasses(this.gInput.inputEl);
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
        gjInput.initialUndoClasses(el);
    },


    initialUndoClasses: function(el) {
        let i;
        let changed = false;
        let value   = el.value;
        switch (el.tagName) {
            case 'INPUT':
                switch (el.type) {
                    case 'radio':
                        for (i = 0; i < el.gInput.radios.length; i++) {
                            if (el.gInput.radios[i].checked !== el.gInput.radios[i].defaultChecked) {
                                changed = true;
                                value   = el.gInput.radios[i]
                                break;
                            }
                        }
                        break;
                    default:
                        if (el.value !== el.defaultValue) changed = true;
                        break;
                }
                break;
            case 'TEXTAREA':
                if (el.value !== el.defaultValue) changed = true;
                break;
            case 'SELECT':
                for (i = 0; i < el.length; i++) {
                    if (el[i].selected !== el[i].defaultSelected) {
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
                if (el.value !== el.defaultValue) changed = true;
                break;
        }

        // console.log('changed:', changed);
        if (changed) {
            el.gInput.classList.add('show-changed');
            el.gInput.classList.add('show-initial');
            el.gInput.classList.remove('show-initial-undo');
        } else if (el.gInput.undo && el.gInput.undo !== value) {
            el.gInput.classList.remove('show-changed');
            el.gInput.classList.remove('show-initial');
            el.gInput.classList.add('show-initial-undo');
        } else {
            el.gInput.classList.remove('show-changed');
            el.gInput.classList.remove('show-initial');
            el.gInput.classList.remove('show-initial-undo');
        }

    },


    switch: function(el) {
        if (el.checked) {
            document.body.classList.remove(el.value);
            if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'show');
        } else {
            document.body.classList.add(el.value);
            if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'hide');
        }
    },


    verify: function(url, row, fileSlug, fileAlt, fileInUse) {
    },


    validate: function(el) {
        let list = document.getElementById('upload-images');

        list.innerHTML = '<div class="row-head"></div>';

        let reader = new FileReader();

        reader.onload = function(e) {
            console.log(e.target.result);
        }


        let maxTotal = 0;
        let maxSize  = 0;

        for (let i = 0; i < el.files.length; i++) {
            maxTotal += el.files[i].size;
            maxSize = Math.max(maxSize, el.files[i].size);

            let row       = document.createElement('div');
            row.className = 'upload-file row';


            row.innerHTML = '';

            if (el.files[i].type.startsWith('image/')) {
                const imgCol = document.createElement('div');
                imgCol.classList.add('col', 'flexT');
                const imgThumb = document.createElement('div');
                imgThumb.classList.add('col-thumb', 'figure', 'single');
                const img = document.createElement('img');
                img.file  = el.files[i];

                const reader  = new FileReader();
                reader.onload = (function(aImg) {
                    return function(e) {
                        aImg.src = e.target.result;
                    };
                })(img);
                reader.readAsDataURL(el.files[i]);

                imgThumb.appendChild(img);
                imgCol.appendChild(imgThumb);

                row.appendChild(imgCol);
            }

            console.log(el.files[i]);

            const info     = document.createElement('div');
            info.className = 'col flex3 info';

            const fileName     = document.createElement('div');
            fileName.innerHTML = el.files[i].name;
            info.appendChild(fileName);

            const fileSlug = document.createElement('div');
            info.appendChild(fileSlug);

            const fileAlt = document.createElement('div');
            info.appendChild(fileAlt);

            const fileSize     = document.createElement('div');
            fileSize.innerHTML = '(' + gFileSize(el.files[i].size) + ')';
            info.appendChild(fileSize);

            const fileInUse = document.createElement('div');
            info.appendChild(fileInUse);

            row.appendChild(info);


            const controls = document.createElement('div');
            controls.className = 'col flex2 controls';
            row.appendChild(controls);


            let xhr       = new XMLHttpRequest();
            xhr.i         = i;
            xhr.row       = row;
            xhr.fileSlug  = fileSlug;
            xhr.fileAlt   = fileAlt;
            xhr.fileInUse = fileInUse;
            xhr.controls = controls;

            xhr.onload = function() {
                if (this.status !== 200) {
                    console.error(t('Error:') + ' ' + this.status);
                }
                let json = JSON.parse(this.responseText);

                this.fileSlug.innerHTML = ' ' + json.slug;
                const fileSlugSpan      = document.createElement('span');
                fileSlugSpan.className  = 'input-label-lang';
                fileSlugSpan.innerHTML  = 'Slug: ';
                this.fileSlug.prepend(fileSlugSpan);

                this.fileAlt.innerHTML = ' ' + json.alt;
                const fileAltSpan      = document.createElement('span');
                fileAltSpan.className  = 'input-label-lang';
                fileAltSpan.innerHTML  = 'Alt: ';
                this.fileAlt.prepend(fileAltSpan);

                let inputType = gjInput.getClonedUploadInput('type', 'imgType', this.i);
                if (inputType) this.controls.appendChild(inputType);

                if (json.status === 'error') {
                    this.row.classList.add('status-1');

                    let inputExisting = gjInput.getClonedUploadInput('existing', 'imgExisting', this.i);
                    if (inputExisting) this.controls.appendChild(inputExisting);
                }
            };

            xhr.onprogress = function(event) {
                if (!event.lengthComputable) return; // size unknown
                let percentComplete = event.loaded / event.total * 100;
                console.info(percentComplete + '%');
            };

            xhr.onerror = function() {
                console.error(t('Connection error'));
            };

            xhr.open('GET', '/edit/image/verify?filename=' + encodeURIComponent(el.files[i].name));
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send();


            let errors = 0;
            if (el.files[i].size > el.dataset.maxsize) {
                fileSize.classList.add('bold', 'red');
                errors++;
            }
            if (maxSize > el.dataset.maxtotal) errors++;
            if (i >= el.dataset.maxcount) errors++;
            if (errors > 0) row.classList.add('red status-0');


            list.appendChild(row);
        }

        let nodeMaxTotal = el.parentNode.querySelector('.info .maxtotal');
        let nodeMaxSize  = el.parentNode.querySelector('.info .maxsize');
        let nodeMaxCount = el.parentNode.querySelector('.info .maxcount');

        nodeMaxTotal.innerHTML = gFileSize(maxTotal);
        nodeMaxTotal.className = 'maxtotal ' + (maxTotal > el.dataset.maxtotal ? 'red' : '');

        nodeMaxSize.innerHTML = gFileSize(maxSize);
        nodeMaxSize.className = 'maxsize ' + (maxSize > el.dataset.maxsize ? 'red' : '');

        nodeMaxCount.innerHTML = el.files.length;
        nodeMaxCount.className = 'maxcount ' + (el.files.length > el.dataset.maxcount ? 'red' : '');

        el.parentNode.parentNode.classList.remove('input-wrap-errors');
        el.parentNode.parentNode.querySelector('.input-errors').classList.add('hide');
    },


    getClonedUploadInput: function(name, nameInput, i) {
        let inputProto = document.getElementById('upload-images-' + name + '-proto');
        if (!inputProto) return;

        let newType = inputProto.cloneNode(true);
        newType.classList.remove('hide');
        newType.id = 'upload-image-' + name + '-' + i;
        let inputs = newType.getElementsByTagName('input');
        for (let j = inputs.length - 1; j >= 0; j--) {
            inputs[j].name     = nameInput + '[' + i + ']';
            inputs[j].disabled = false;
            if (inputs[j].dataset.target !== undefined) inputs[j].dataset.target = newType.id;
        }

        return newType;
    },

}

