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




/***********************/
/******  main.js  ******/
/***********************/

let gjTextareas     = [];
let gjResizeTimeout = null;
let filterData      = ['pageCurrent', 'pageFirst', 'pagePrev', 'pageNext', 'pageLast', 'itemsPerPage', 'rowsFiltered', 'rowsTotal'];


window.addEventListener('DOMContentLoaded', function() {
    const iOS    = !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
    const safari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    if (iOS && safari) document.querySelector('meta[name=viewport]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0');
    gjLoad();
});


function gjLoad() {
    document.addEventListener('input', handleEventInput, true);
    document.addEventListener('change', handleEventChange, true);
    // document.addEventListener('focus', handleEventFocus, true);
    document.addEventListener('blur', handleEventBlur, true);
    document.addEventListener('click', handleEventClick, true);
    document.addEventListener('mousedown', handleEventMousedown, true);
    window.addEventListener('keydown', handleEventKeydown, true);
    window.addEventListener('error', handleEventError, true);
    document.addEventListener('submit', handleEventSubmit, true);
    window.addEventListener('beforeunload', handleEventBeforeunload, true);

    gjImage.init();
    gjTextareas = document.getElementsByTagName('textarea');

    gjInput.textareaResize();

    // prepare form pagination
    for (let i = 0; i < document.forms.length; i++) {
        document.forms[i].pagination = [];
        filterData.forEach(function(el) {
            document.forms[i].pagination[el] = document.forms[i].querySelectorAll('.' + el);
        });
    }

    gjLoadTrix();

    gjForm.init();

    // on window resize with debounce
    window.onresize = function() {
        if (gjResizeTimeout != null) clearTimeout(gjResizeTimeout);
        gjResizeTimeout = setTimeout(gjInput.textareaResize, 100);
    };
}


function handleEventInput(ev) {
    if (
        ev.target.matches('.input-text') ||
        ev.target.matches('.input-file') ||
        ev.target.matches('.input-trix')
    ) {
        gjInput.change(ev.target);
        gjInput.textareaAutoGrow(ev.target);
    }

    if (ev.target.matches('.input-slug')) {
        gjInput.format(ev.target, 'slug');
    }
    if (ev.target.matches('.input-date')) {
        gjInput.format(ev.target, 'date');
    }
    if (ev.target.matches('.input-time')) {
        gjInput.format(ev.target, 'time');
    }

    if (
        ev.target.matches('.itemsPerPage') ||
        ev.target.matches('.pageCurrent') ||
        ev.target.matches('.input-search')
    ) {
        gjFilter.filter(ev.target);
        ev.preventDefault();
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
        gjInput.change(ev.target);
    }

    if (
        ev.target.matches('#switches input') ||
        ev.target.matches('.openbox input')
    ) {
        gjInput.switch(ev.target);
    }

    if (ev.target.matches('.ev-cookie-toggle')) {
        if (ev.target.checked) {
            document.cookie = ev.target.dataset.key + '=' + ev.target.dataset.val + '; SameSite=Strict; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/';
            document.body.classList.add('isDevDebug');
        } else {
            document.cookie = ev.target.dataset.key + '=; SameSite=Strict; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
            document.body.classList.remove('isDevDebug');
        }
    }


    if (ev.target.matches('.input-image')) {
        gjInput.validate(ev.target);
    }

    if (ev.target.matches('.filterChange')) {
        gjFilter.filter(ev.target);
        ev.preventDefault();
    }

    if (ev.target.matches('.filterChangeEmpty')) {
        gjFilter.filterEmpty(ev.target);
    }

}


function handleEventBlur(ev) {
    if (ev.target.matches && ev.target.matches('.input-slugImg')) {
        let img     = ev.target.parentNode.querySelector('button img');
        let imgSlug = ev.target.value;
        if (img) {
            if (imgSlug) {
                let url = '/edit/' + gjImage.editorImageSlug + '/' + imgSlug + '/thumb';
                fetch(url, {
                    redirect: "manual"
                }).then((res) => {
                    img.src = '/edit/gfx/btn/no-photo-add.png';
                    if (res.status === 200) return res.text();
                }).then((data) => {
                    if (!data) return;
                    img.src = data + '?' + new Date;
                }).catch((error) => {
                    console.error('Error:', error);
                });
            } else {
                img.src = '/edit/gfx/btn/no-photo-add.png';
            }
        }
    }
    if (ev.target.matches && ev.target.matches('.input-select')) {
        gjInput.change(ev.target);
    }
}

function handleEventFocus(ev) {
}


function handleEventClick(ev) {
    if (ev.target.matches('.slugImage')) {
        gjImage.openSingle(ev.target, ev.target.dataset.imgtype ?? '');
    }
    if (ev.target.matches('.image-select-header-close')) {
        gjImage.close(ev.target);
    }
    if (ev.target.matches('.image-select-header-select')) {
        gjImage.selectGallery();
    }
    if (ev.target.matches('.image-select-header-select-all')) {
        gjImage.selectAll();
    }
    if (ev.target.matches('.image-select-header-select-none')) {
        gjImage.selectNone();
    }
    if (ev.target.matches('.imageSelectItem')) {
        gjImage.selectSingle(ev.target);
    }
    if (ev.target.matches('.input-translate')) {
        gjTranslate.input(ev.target);
    }
    if (ev.target.matches('.input-calendar')) {
        gjInput.calendar(ev.target);
    }

    // checkbox alt key
    if (ev.target.matches('.btn-checkbox input')) {
        if (ev.altKey) {
            ev.target.closest('.input-wrap')?.querySelectorAll('.btn-checkbox input')?.forEach(function(el) {
                if (ev.target.checked) {
                    el.checked = true;
                    el.parentElement.classList.add('active');
                } else {
                    el.checked = false;
                    el.parentElement.classList.remove('active');
                }
            });
        }
    }


    if (ev.target.matches('.scrape-jsonld')) {
        gjScraper.jsonld(ev.target);
    }
    if (ev.target.matches('.scrape-youtube')) {
        gjScraper.youtube(ev.target);
    }
    if (ev.target.matches('.scrape-vimeo')) {
        gjScraper.vimeo(ev.target);
    }


    if (ev.target.matches('.gchat-room-btn')) {
        gjcClickSend(ev.target, ev.target.dataset.room);
    }
    if (ev.target.matches('.ev-cookie-set')) {
        document.cookie = ev.target.dataset.key + '=' + ev.target.dataset.val + '; SameSite=Strict; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/';
    }
    if (ev.target.matches('.ev-cookie-del')) {
        document.cookie = ev.target.dataset.key + '=; SameSite=Strict; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
    }


    if (ev.target.matches('.ev-gallery-delete-none')) {
        gjField.deleteAll(ev.target.closest('.module-field-multi-header').dataset.target, 'enable');
    }
    if (ev.target.matches('.ev-gallery-delete-all')) {
        gjField.deleteAll(ev.target.closest('.module-field-multi-header').dataset.target, 'disable');
    }
    if (ev.target.matches('.ev-gallery-reorder')) {
        gjField.sortNatural(ev.target.closest('.module-field-multi-header').dataset.target);
    }
    if (ev.target.matches('.ev-gallery-add')) {
        let pos = ev.target.closest('.module-field-group')?.querySelector('.module-position').value ?? 0;
        if (ev.target.matches('.after')) pos++;

        let fieldId = ev.target.closest('.module-field')?.id ?? ev.target.closest('.module-field-multi-header')?.nextElementSibling.id;
        if (!fieldId) return;
        const imgType = document.querySelector('#' + fieldId + '-new .slugImage')?.dataset.imgtype ?? '';
        gjImage.openGallery(fieldId, imgType, pos);
    }

    if (ev.target.matches('.imageList-delete')) {
        let fieldId = ev.target.closest('.module-field')?.id ?? ev.target.closest('.module-field-multi-header')?.nextElementSibling.id;
        if (!fieldId) return;
        gjImage.openGallery(fieldId, '', 0);
    }


    if (ev.target.matches('.ev-module-add')) {
        let fieldId = ev.target.closest('.module-field-group')?.id ?? ev.target.closest('.module-field-multi-header')?.nextElementSibling.id;

        gjField.cloneNew(fieldId, 0);
        gjField.countPos(document.getElementById(ev.target.dataset.where));
    }
    if (ev.target.matches('.ev-module-rem')) {
        gjField.delete(ev.target);
    }
    if (ev.target.matches('.ev-module-first')) {
        gjField.moveTo(ev.target, 1);
    }
    if (ev.target.matches('.ev-module-last')) {
        gjField.moveTo(ev.target, 999999);
    }
    if (ev.target.matches('.ev-module-up')) {
        gjField.moveUp(ev.target);
    }
    if (ev.target.matches('.ev-module-down')) {
        gjField.moveDown(ev.target);
    }
    if (ev.target.matches('.ev-module-go')) {
        gjField.moveTo(ev.target);
    }


    if (
        ev.target.matches('.pageFirst') ||
        ev.target.matches('.pagePrev') ||
        ev.target.matches('.pageNext') ||
        ev.target.matches('.pageLast')
    ) {
        gjFilter.filter(ev.target, true);
        ev.preventDefault();
    }
}


function handleEventMousedown() {
    document.body.classList.remove('show-focus-outlines');
}


function handleEventKeydown(ev) {

    // [command + s] sends form
    if (['s', 'S'].contains(ev.key) && (navigator.platform.match('Mac') ? ev.metaKey : ev.ctrlKey)) {
        ev.preventDefault();
        if (document.forms[0].id === 'logout') return;
        if (gjImage.selectorOpen) return;
        gjForm.disableUnchanged();
        document.forms[0].submit();
    }

    // only use focus on keyboard
    if (ev.key === 'Tab') {
        document.body.classList.add('show-focus-outlines');
    }
    if (['Esc', 'Escape'].contains(ev.key)) {
        document.body.classList.remove('show-focus-outlines');
        gjImage.close();
    }

    if (ev.key === 'Enter') {
        if (ev.target.matches('.input-text:not(.input-textarea)')) {
            ev.preventDefault();
            return false;
        }
        if (ev.target.matches('.module-position')) {
            gjField.moveTo(ev.target.nextElementSibling);
            ev.preventDefault();
            return false;
        }
    }

    if (ev.target.matches('.input-date') || ev.target.matches('.input-time')) {
        gjInput.mod(ev.target, ev);
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
        gjImage.resizeRequest(ev.target);
    }
}


function handleEventSubmit(ev) {
    gjForm.disableUnchanged();
}


function handleEventBeforeunload(ev) {
    gjImage.close();
    if (gjForm.changed()) {
        if (!gjForm.isSaving) {
            ev.preventDefault();
            return ev.returnValue = "Are you sure you want to exit?";
        }
    }
}


function gjLoadTrix() {
    document.addEventListener('trix-before-initialize', function(ev) {
        ev.target.addEventListener('keydown', function(ev) {
            if (ev.shiftKey && ev.key === 'Enter') {
                ev.target.editor.recordUndoEntry('Shift+Enter');
                ev.target.editor.insertHTML('<br>');
                ev.preventDefault();
            }
        });
    });

    document.addEventListener('trix-change', function(ev) {
        let editorEl = ev.target;
        if (!editorEl.gInputLoaded) {
            editorEl.gInput       = editorEl.parentNode;
            editorEl.gInputLoaded = true;
        }

        gjInput.trixCharWordCount(editorEl);
        gjInput.initialUndoClasses(editorEl);
    });

    document.addEventListener('trix-initialize', function(ev) {
        let editorEl = ev.target;
        gjInput.trixCharWordCount(editorEl);
    });

    document.addEventListener('trix-file-accept', function(event) {
        event.preventDefault(); // disable trix image attachment pasting
    });
}




/*************************/
/******  filter.js  ******/
/*************************/

let gjFilter = {

    filtering: false,
    debounceTimer: null,

    filter: function(el, instantaneous) {
        el.form.querySelector('.load').classList.add('loading1');

        if (instantaneous) {
            this.load(el);
            return;
        }
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(function() {
            gjFilter.load(el);
        }, 400);
    },


    filterEmpty: function(el) {
        if (el.parentNode.classList.contains('active')) {
            el.name  = el.parentNode.previousElementSibling.name;
            el.value = '{{empty}}'

            el.parentNode.previousElementSibling.disabled = true;
        } else {
            el.name  = undefined;
            el.value = undefined;

            el.parentNode.previousElementSibling.disabled = false;
        }
        this.load(el);
    },


    load: function(el) {
        window.requestAnimationFrame(function() {
            window.requestAnimationFrame(function() {
                el.form.querySelector('.load').classList.add('loading2');
            });
        });

        if (el.closest('.pagination-footer')) window.scrollTo(0, 0);

        var xhr = new XMLHttpRequest();
        var fd  = new FormData(el.form);
        if (el.tagName === 'BUTTON' && el.name && el.value) fd.set(el.name, el.value);

        xhr.form   = el.form;
        xhr.onload = function(event) {
            var loadEl       = this.form.querySelector('.load');
            loadEl.innerHTML = event.target.responseText;
            loadEl.classList.remove('loading1');
            loadEl.classList.remove('loading2');

            gjImage.paintSelectedActivated();

            var results = loadEl.querySelector('.results').dataset;

            for (var i = 0; i < filterData.length; i++) {
                var el      = filterData[i];
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
        return false;

    }
}





/************************/
/******  image.js  ******/
/************************/

let gjImage = {

    editorImageSlug: 'image',

    init: function() {
        this.firstTime = true;
        this.el        = document.getElementById('image-select');

        this.selectorOpen   = false;
        this.activeInput    = null;
        this.activeFieldId  = null;
        this.activePos      = 0;
        this.activeImages   = [];
        this.selectedImages = {};
        this.isGallery      = false;

        this.scrollClosed = 0;
        this.scrollOpen   = 0;
    },


    openSingle: function(inputEl, imgType) {
        this.activeInput  = inputEl.previousElementSibling;
        this.activeImages = [inputEl.value];
        this.isGallery    = false;

        this.el.querySelectorAll('.btn-group-gallery').forEach(function(el) {
            el.classList.add('hide')
        });
        this.open(imgType);
    },


    openGallery: function(fieldId, imgType, pos) {
        this.activeFieldId = fieldId;
        this.activePos     = pos;
        this.activeImages  = Array.from(document.getElementById(fieldId).querySelectorAll('.input-wrap-slugImage .input-slug:not([disabled])')).map(el => el.value);
        this.isGallery     = true;

        this.el.querySelectorAll('.btn-group-gallery').forEach(function(el) {
            el.classList.remove('hide')
        });
        this.open(imgType);
    },


    open: function(imgType) {
        this.scrollClosed = window.scrollY;

        this.el.classList.remove('hide');
        this.selectorOpen = true;
        this.selectedImages = {};

        let siblings = getSiblings(this.el);
        for (let i = 0; i < siblings.length; i++) {
            siblings[i].classList.add('hide');
        }

        let typeInput = document.getElementsByName('filterTexts[type]')[0]
        if (this.firstTime) {
            this.firstTime  = false;
            typeInput.value = imgType;
            trigger(typeInput, 'input');
            typeInput.focus();
        } else {
            this.paintSelectedActivated();
            window.scrollTo(0, this.scrollOpen);
        }
    },


    close: function() {
        if (!this.el) return;
        if (this.el.classList.contains('hide')) return
        this.selectorOpen = false;
        this.scrollOpen = window.scrollY;

        this.el.classList.add('hide');

        let siblings = getSiblings(this.el);
        for (let i = 0; i < siblings.length; i++) {
            siblings[i].classList.remove('hide');
        }

        window.scrollTo(0, this.scrollClosed);

        if (this.activeInput) this.activeInput.focus();
    },


    selectSingle: function(btnEl) {
        if (this.isGallery) {
            if (this.selectedImages.hasOwnProperty(btnEl.id)) {
                delete this.selectedImages[btnEl.id];
                btnEl.classList.remove('selected');
            } else {
                this.selectedImages[btnEl.id] = this.dataFromButton(btnEl);
                btnEl.classList.add('selected');
            }
        } else {
            btnEl.classList.add('active');

            let img = this.dataFromButton(btnEl);

            this.close();

            this.setInputAndImage(this.activeInput, img, true);
        }
    },


    selectGallery: function() {
        this.close();

        let ordered = {};
        Object.keys(this.selectedImages).sort(function(a, b) {
            return b.localeCompare(a, undefined, {numeric: true});
        }).forEach(function(key) {
            ordered[key] = gjImage.selectedImages[key];
        });

        for (let imgSlug in ordered) {
            let cloned     = gjField.cloneNew(this.activeFieldId, this.activePos);
            let imageInput = cloned.querySelector('.input-wrap-slugImage .input-slug');
            this.setInputAndImage(imageInput, ordered[imgSlug], false);
        }

        gjInput.textareaResize();

        gjField.countPos(document.getElementById(this.activeFieldId));
    },


    selectAll: function() {
        let images = this.el.querySelectorAll('.imageSelectItem:not(.active)');
        for (let i = 0; i < images.length; i++) {
            this.selectedImages[images[i].id] = this.dataFromButton(images[i]);
            images[i].classList.add('selected');
        }
    },


    selectNone: function() {
        let images = this.el.querySelectorAll('.imageSelectItem.selected');
        for (let i = 0; i < images.length; i++) {
            images[i].classList.remove('selected');
            delete this.selectedImages[images[i].id];
        }
    },


    dataFromButton: function(btnEl) {
        let img = btnEl.children[0].children[0];
        return {
            'slug': btnEl.id,
            'src': img.src,
            'w': img.getAttribute('width'),
            'h': img.getAttribute('height'),
        }
    },


    setInputAndImage: function(inputEl, img, autogrow) {
        inputEl.value   = img.slug;
        let selectorImg = inputEl.nextElementSibling.children[0];
        selectorImg.parentNode.classList.remove('empty');
        selectorImg.src = img.src;
        selectorImg.setAttribute('width', img.w);
        selectorImg.setAttribute('height', img.h);
        if (autogrow) gjInput.textareaAutoGrow(inputEl);
    },


    resizeRequest: function(el) {
        if (el.parentNode.classList.contains('waiting')) return;
        if (el.parentNode.classList.contains('loading')) return;

        el.parentNode.classList.add('waiting');
        let re    = /.*\/([0-9a-z-]+)_(\d+_\d+)\./;
        let match = re.exec(el.src);
        if (!match) return;
        let imgSlug = match[1];
        let size    = match[2].split('_');

        let xhr   = new XMLHttpRequest();
        xhr.imgEl = el;

        xhr.onload = function() {
            if (this.status !== 200 && this.responseText !== 'ok') return;
            this.imgEl.parentNode.classList.remove('waiting');
            this.imgEl.parentNode.classList.add('loading');
            this.imgEl.src += '?' + +new Date;
            // this.imgEl.onload = null;
            this.imgEl.onerror = null;
        };

        xhr.onprogress = function(event) {
            if (!event.lengthComputable) return; // size unknown
            let percentComplete = event.loaded / event.total * 100;
            console.log(percentComplete);
        };

        xhr.onerror = function() {
            console.error('Resize request error.');
        };

        xhr.open('GET', '/edit/' + gjImage.editorImageSlug + '/' + imgSlug + '/resize/' + size[0] + '/' + size[1]);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send();

        el.onload = function() {
            this.onload  = null;
            this.onerror = null;
            this.parentNode.classList.remove('waiting', 'loading')
        };
    },


    paintSelectedActivated: function() {
        if (!this.activeImages) return;

        let active = this.el.querySelectorAll('.active');
        for (let i = 0; i < active.length; i++) {
            active[i].classList.remove('active');
        }
        let selected = this.el.querySelectorAll('.selected');
        for (let i = 0; i < selected.length; i++) {
            selected[i].classList.remove('selected');
        }
        let images = this.el.querySelectorAll('.imageSelectItem');

        for (let i = 0; i < images.length; i++) {
            if (this.activeImages.contains(images[i].id)) {
                images[i].classList.add('active');
            }
            if (this.selectedImages.hasOwnProperty(images[i].id)) {
                images[i].classList.add('selected');
            }
        }
    },

};




/*************************/
/******  gjForm.js  ******/
/*************************/

let gjForm = {

    isSaving: false,
    elMain: null,
    fdMainInitial: null,

    init: function() {
        gjForm.elMain = document.forms[0];
        if (!gjForm.elMain) return;
        // if (gjForm.elMain.classList.contains('formDisable')) console.log('gjForm.init formDisable');
        // if (gjForm.elMain.classList.contains('formPrevent')) console.log('gjForm.init formPrevent');

        gjForm.fdMainInitial = new FormData(gjForm.elMain);
    },

    disableUnchanged: function() {
        if (!gjForm.elMain) return;
        gjForm.isSaving = true;
        if (!gjForm.elMain.classList.contains('formDisable')) return;
        let fdOld = gjForm.fdMainInitial;
        let fdNew = new FormData(gjForm.elMain);
        for (let input of gjForm.elMain.elements) {
            if (!input.name) continue;
            if (input.name === 'csrf') continue;
            if (!fdNew.has(input.name)) continue;
            if (!fdOld.has(input.name)) continue;
            if (fdNew.get(input.name) === fdOld.get(input.name)) {
                input.disabled = true;
            } else {
                // console.log(input.name, input.value, fdOld.get(input.name));
            }
        }
    },

    changed: function() {
        if (!gjForm.elMain) return false;
        if (!gjForm.elMain.classList.contains('formPrevent')) return;
        let fdOld = gjForm.fdMainInitial;
        let fdNew = new FormData(gjForm.elMain);
        for (let input of gjForm.elMain.elements) {
            if (!input.name) continue;
            if (input.name === 'csrf') continue;
            if (!fdNew.has(input.name)) continue;

            if (fdNew.get(input.name) instanceof File) {
                if (fdNew.get(input.name).name !== fdOld.get(input.name).name) {
                    // console.log('file', input.name, fdNew.get(input.name).name, fdOld.get(input.name).name);
                    return true;
                }
            } else {
                if (fdNew.get(input.name) !== fdOld.get(input.name)) {
                    // console.log(input.name, fdNew.get(input.name), fdOld.get(input.name));
                    return true;
                }
            }
        }
        return false;
    }
};




/************************/
/******  field.js  ******/
/************************/

let gjField = {

    animatePrepare: function(el) {
        let els = el.parentNode.children;
        for (let i = 0; i < els.length; i++) {
            els[i].posAbs = {x: els[i].offsetLeft, y: els[i].offsetTop};
        }
    },

    animatePerform: function(el) {
        let els = el.parentNode.children;
        for (let i = 0; i < els.length; i++) {
            gjField.animateTransform(els[i], els[i].posAbs);
        }
        gjField.animateZIndex(el);
    },

    animateTransform: function(el, posAbs) {
        let posdif = {x: (posAbs.x - el.offsetLeft), y: (posAbs.y - el.offsetTop)};
        if (posdif.x == 0 && posdif.y == 0) return;

        el.style.transform  = 'translate(' + posdif.x + 'px, ' + posdif.y + 'px)';
        el.style.transition = '';

        window.requestAnimationFrame(function() {
            el.style.transition = 'transform 352ms cubic-bezier(0.65,0.05,0.36,1)';
            el.style.transform  = 'translate(0px, 0px)';
        });
    },

    animateZIndex: function(el) {
        el.style.zIndex   = '2';
        el.style.position = 'relative';
        // console.log(el.style.zIndex, el);

        if (!el.ontransitionend) {
            el.ontransitionend = function(ev) {
                ev.target.style.zIndex   = '1';
                ev.target.style.position = '';
                // ev.target.scrollIntoView({block: "nearest", inline: "nearest"});
                // console.log(ev.target.style.zIndex, ev.target);
            };
        }
    },

    moveUp: function(el) {
        let target = document.getElementById(el.dataset.target);
        let parent = target.parentNode;
        gjField.animatePrepare(target);

        if (target.previousElementSibling)
            parent.insertBefore(target, target.previousElementSibling);
        gjField.countPos(parent);
        target.querySelector('.module-position').focus();

        gjField.animatePerform(target);
    },


    moveDown: function(el) {
        let target = document.getElementById(el.dataset.target);
        let parent = target.parentNode;
        gjField.animatePrepare(target);

        if (target.nextElementSibling)
            parent.insertBefore(target.nextElementSibling, target);
        gjField.countPos(parent);
        target.querySelector('.module-position').focus();

        gjField.animatePerform(target);
    },

    moveTo: function(el, pos = null) {
        let target     = document.getElementById(el.dataset.target);
        let positionEl = target.querySelector('.module-position');
        gjField.animatePrepare(target);

        if (pos) {
            positionEl.value = pos;
        } else {
            pos = positionEl.value;
        }
        let parent = target.parentNode;
        let groups = parent.children;

        if (pos >= groups.length) {
            parent.appendChild(target);
        } else {
            let j = 1;
            for (let i = 0; i < groups.length; i++) {
                if (groups[i].dataset === undefined || groups[i].dataset.disabled) continue;
                if (groups[i] === target) continue;

                if (j == pos) {
                    parent.insertBefore(target, groups[i]);
                }
                j++;
            }
        }

        target.querySelector('.module-position').focus();
        gjField.countPos(target.parentNode);
        gjField.animatePerform(target);
    },

    sortNatural: function(fieldId) {
        let list = document.getElementById(fieldId);

        let items    = list.childNodes;
        let itemsArr = [];
        for (let i in items) {
            if (items[i].nodeType !== Node.ELEMENT_NODE) continue;
            items[i].dataset.slugsort = items[i].querySelector('.input-wrap-slugImage .input-slug').value;
            itemsArr.push(items[i]);
        }

        itemsArr.sort(function(a, b) {
            return a.dataset.slugsort.localeCompare(b.dataset.slugsort, undefined, {numeric: true});
        });

        for (let i = 0; i < itemsArr.length; ++i) {
            list.appendChild(itemsArr[i]);
        }

        gjField.countPos(list);
    },

    setNewId: function(group, groupId) {
        let inputs    = group.getElementsByTagName('input');
        let selects   = group.getElementsByTagName('select');
        let textareas = group.getElementsByTagName('textarea');
        let buttons   = group.getElementsByTagName('button');
        let trixes    = group.getElementsByTagName('trix-editor-new');
        let i;
        for (i = inputs.length - 1; i >= 0; i--) {
            inputs[i].name     = inputs[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            inputs[i].id       = inputs[i].id.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            inputs[i].disabled = false;
            if (inputs[i].dataset.target !== undefined) inputs[i].dataset.target = group.id;
        }
        for (i = trixes.length - 1; i >= 0; i--) {
            trixes[i].setAttribute('input', trixes[i].attributes.input.value.replace('\]\[new-0\]\[', '][new-' + groupId + ']['));
            trixes[i].outerHTML = trixes[i].outerHTML.replace(/trix-editor-new/, 'trix-editor');
        }
        for (i = selects.length - 1; i >= 0; i--) {
            selects[i].name     = selects[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            selects[i].disabled = false;
            if (selects[i].dataset.target !== undefined) selects[i].dataset.target = group.id;
        }
        for (i = textareas.length - 1; i >= 0; i--) {
            textareas[i].name     = textareas[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            textareas[i].disabled = false;
            if (textareas[i].dataset.target !== undefined) textareas[i].dataset.target = group.id;
        }
        for (i = buttons.length - 1; i >= 0; i--) {
            if (buttons[i].classList.contains('ev-module-add')) continue;
            buttons[i].name     = buttons[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            buttons[i].disabled = false;
            if (buttons[i].dataset.target !== undefined) buttons[i].dataset.target = group.id;
        }

    },

    cloneNew: function(fieldId, pos) {
        const groupId = gjUnique();

        let newGroup = document.getElementById(fieldId + '-new').cloneNode(true);
        let where = document.getElementById(fieldId);

        newGroup.classList.remove('hide');
        newGroup.classList.add('module-field-group-new');
        newGroup.id      = newGroup.id + '-' + groupId;
        newGroup.groupId = groupId;
        gjField.setNewId(newGroup, groupId);

        where.prepend(newGroup);
        let go = newGroup.querySelector('.ev-module-go');
        if (pos) gjField.moveTo(go, pos);

        return newGroup;
    },

    deleteAll: function(fieldId, action) {
        let field = document.getElementById(fieldId);

        let closeButtons = field.querySelectorAll('.ev-module-rem');
        for (let i = 0; i < closeButtons.length; i++) {
            gjField.delete(closeButtons[i], action);
        }
    },

    delete: function(el, action) {
        let check     = el.nextElementSibling;
        let row       = document.getElementById(el.dataset.target);
        let group     = document.getElementById(el.dataset.target).parentNode;
        let inputs    = row.getElementsByTagName('input');
        let buttons   = row.getElementsByTagName('button');
        let selects   = row.getElementsByTagName('select');
        let radios    = row.getElementsByTagName('radio');
        let textareas = row.getElementsByTagName('textarea');
        let i;

        if (!['toggle', 'enable', 'disable'].contains(action)) action = 'toggle';

        let result = action;
        if (action === 'toggle') {
            if (row.dataset.disabled) {
                result = 'enable';
            } else {
                result = 'disable';
            }
        }

        if (action !== 'enable') {
            if (row.classList.contains('module-field-group-new')) {
                group.removeChild(row);
                gjField.countPos(group);
                return;
            }
        }

        if (result === 'enable') {
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
        el.disabled    = false;
        check.disabled = false;
        gjField.countPos(group);
    },

    countPos: function(el) {
        let groups = el.childNodes;
        let j      = 0;
        for (let i = 0; i < groups.length; i++) {
            if (groups[i].dataset === undefined || groups[i].dataset.disabled) continue;
            j++;

            let pos = groups[i].querySelector('.module-position');
            if (pos) {
                pos.value = j;
                gjInput.change(pos);
            }

            let posBefore = groups[i].querySelector('.ev-gallery-add.before');
            if (posBefore) {
                posBefore.dataset.pos = j;
                // posBefore.innerHTML = posBefore.dataset.pos;
            }

            let posAfter = groups[i].querySelector('.ev-gallery-add.after');
            if (posAfter) {
                posAfter.dataset.pos = (j + 1);
                // posAfter.innerHTML = posAfter.dataset.pos;
            }
        }
        let elCount = el.previousElementSibling.querySelector('.module-field-count');
        if (elCount) elCount.innerHTML = j;
    },

}






/************************/
/******  input.js  ******/
/************************/

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
            el.style.height = (el.scrollHeight + 2) + 'px';
        } else {
            el.style.height = '';
        }
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


    calendar: function(el) {
        let elWrap = el.closest('.input-wrap');
        if (!elWrap) return;

        let elInput = elWrap.querySelector('input');
        if (!elInput) return;

        console.log(elInput.type);
        if (elInput.type === 'text') {
            elInput.type = 'date';
            el.innerText = 'text';
        } else if (elInput.type === 'date') {
            elInput.type = 'text';
            el.innerText = 'calendar';
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
            this.textareaResize();
        } else {
            document.body.classList.add(el.value);
            if (el.parentNode.dataset.remember) sessionStorage.setItem(el.value, 'hide');
            this.textareaResize();
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




/*************************/
/******  scrape.js  ******/
/*************************/

let gjScraper = {

    jsonld: function(el) {
        let elMeta = gjScraper.setupEl(el);

        let xhr      = new XMLHttpRequest();
        xhr.errorsEl = elMeta.errorsEl;
        xhr.infosEl  = elMeta.infosEl;

        gjScraper.sendXhr(xhr, '/edit/importer/jsonld?url=' + elMeta.url, importRelationsJsonld);
    },


    youtube: function(el) {
        let elMeta = gjScraper.setupEl(el);

        let youtubeId = elMeta.url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
        if (youtubeId && youtubeId[1]) {
            youtubeId                = youtubeId[1];
            elMeta.infosEl.innerHTML = '<li>' + youtubeId + '</li>';
        } else {
            elMeta.errorsEl.innerHTML = '<li>' + t('Invalid Youtube Id from url') + '</li>';
            elMeta.infosEl.innerHTML  = '';
        }

        let xhr      = new XMLHttpRequest();
        xhr.errorsEl = elMeta.errorsEl;
        xhr.infosEl  = elMeta.infosEl;

        gjScraper.sendXhr(xhr, '/edit/importer/youtube?id=' + youtubeId, importRelationsYoutube);
    },


    vimeo: function(el) {
        let elMeta = gjScraper.setupEl(el);

        let vimeoId = elMeta.url.match(/vimeo\.com\/(\d+)$/i);
        if (vimeoId && vimeoId[1]) {
            vimeoId                  = vimeoId[1];
            elMeta.infosEl.innerHTML = '<li>' + vimeoId + '</li>';
        } else {
            elMeta.errorsEl.innerHTML = '<li>' + t('Invalid Vimeo Id from url') + '</li>';
            elMeta.infosEl.innerHTML  = '';
            return;
        }

        let xhr      = new XMLHttpRequest();
        xhr.errorsEl = elMeta.errorsEl;
        xhr.infosEl  = elMeta.infosEl;

        gjScraper.sendXhr(xhr, '/edit/importer/vimeo?id=' + vimeoId, importRelationsVimeo);
    },


    setupEl: function(el) {
        let r                = {};
        r.url                = el.previousElementSibling.value;
        r.errorsEl           = el.parentNode.querySelector('.input-errors');
        r.infosEl            = el.parentNode.querySelector('.input-infos');
        r.errorsEl.innerHTML = '';
        r.infosEl.innerHTML  = '<li>' + t('Loading') + '</li>';

        if (!r.url && r.errorsEl) {
            r.errorsEl.innerHTML = '<li>' + t('Empty') + '</li>';
            r.infosEl.innerHTML  = '';
        }

        return r;
    },


    sendXhr: function(xhr, url, relations) {

        xhr.onload = function() {
            if (this.status !== 200) {
                this.errorsEl.innerHTML = '<li>' + t('Error:') + ' ' + this.status + '</li>';
                this.infosEl.innerHTML  = '';
            }
            if (relations === undefined) {
                this.errorsEl.innerHTML = '<li>' + t('Could not load') + '</li>';
                this.infosEl.innerHTML  = '';
                return;
            }
            let jsonld = JSON.parse(this.responseText);

            if (jsonld.error) {
                this.errorsEl.innerHTML = '<li>' + jsonld.error + '</li>';
                this.infosEl.innerHTML  = '';
                return;
            }

            let changes = [];

            let json = jsonld.data;
            for (let relationKey in relations) {
                let jsonldSearchRegex, inputEl, content;

                if (relationKey.substr(0, 10) === 'add-module') {
                    let foundInJsonld = [];
                    for (let inputNameRaw in relations[relationKey]) {
                        jsonldSearchRegex = relations[relationKey][inputNameRaw].match(/^@type-(\w+)+:(.+)$/);
                        if (!jsonldSearchRegex) continue;
                        if (json['@type'] !== jsonldSearchRegex[1]) continue;
                        if (!json[jsonldSearchRegex[2]]) continue;
                        foundInJsonld.push({raw: inputNameRaw, clean: jsonldSearchRegex[2]});
                    }

                    if (foundInJsonld.length) {
                        inputEl   = document.getElementById(relationKey);
                        let field = relationKey.substring(4, relationKey.length - 4);
                        if (!inputEl) continue;
                        let addedFields = gjField.cloneNew(field, 0);

                        for (let i = 0; i < foundInJsonld.length; i++) {
                            let inputName    = foundInJsonld[i];
                            let inputNameNew = inputName.raw.replace('\]\[new-0\]\[', '][new-' + addedFields.groupId + '][');

                            content = gjScraper.jsonPathToValue(json, inputName.clean);
                            if (!content) continue;

                            inputEl = document.getElementsByName(inputNameNew)[0];
                            if (!inputEl) continue;

                            changes.push({el: inputEl, content: content});
                        }
                    }
                    continue;
                }

                jsonldSearchRegex = relations[relationKey].match(/^@type-(\w+)+:(.+)$/);
                if (!jsonldSearchRegex) continue;
                if (json['@type'] !== jsonldSearchRegex[1]) continue;
                content = gjScraper.jsonPathToValue(json, jsonldSearchRegex[2]);
                if (!content) continue;
                inputEl = document.getElementsByName(relationKey)[0];
                if (!inputEl) continue;
                if (inputEl.value) continue;
                changes.push({el: inputEl, content: content});
            }

            for (let i = 0; i < changes.length; i++) {
                let content = changes[i].content;
                let inputEl = changes[i].el;
                let date, localeDate;
                switch (inputEl.tagName) {
                    case 'INPUT':
                    case 'TEXTAREA':
                        if (inputEl.value === content) continue;
                        if (inputEl.classList.contains('input-trix')) {
                            let editor = inputEl.nextElementSibling.nextElementSibling.editor;
                            editor.recordUndoEntry('Content updated');
                            editor.setSelectedRange([0, editor.getDocument().getLength()])
                            content = '<p>' + content + '</p>';
                            content = content.replace(/\n\n+/g, '</p><p>');
                            content = content.replace(/\n/g, '<br>');
                            editor.insertHTML(content);
                            break;
                        }

                        let img = {slug: content, src: '/media/image/' + content + '/' + content + '.jpg'}

                        if (inputEl.classList.contains('input-slugImg')) {
                            gjImage.setInputAndImage(inputEl, img, true);
                        }

                        switch (inputEl.type) {
                            case 'url':
                                content   = decodeURI(content);
                                let regex = RegExp(/^https?:\/\/([^.]+\.)?facebook\./)
                                if (regex.test(content)) content = content.replace(/^https?:\/\/([^.]+)\./, 'https://www.');
                                break;
                        }

                        if (inputEl.classList.contains('input-date')) {
                            content    = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                            date       = new Date(content);
                            localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                            content    = gjScraper.dateFormatDate(localeDate);
                        }
                        if (inputEl.classList.contains('input-time')) {
                            content    = content.replace(/(^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d)([+-]\d\d)(\d\d)/, '$1.000$2:$3');
                            date       = new Date(content);
                            localeDate = new Date(date.toLocaleString('en-US', {timeZone: 'Europe/Lisbon'}))
                            content    = gjScraper.dateFormatTime(localeDate);
                        }
                        inputEl.value = content;
                        // trigger(inputEl, 'input');
                        break;

                    case 'SELECT':
                        break;

                    case 'BUTTON':
                        if (inputEl.value === content) continue;
                        inputEl.value = content;
                        trigger(inputEl, 'input');
                        break;

                    case 'DATALIST':
                        break;

                }

            }
            this.infosEl.innerHTML  = '<li>' + t('Imported') + ': ' + changes.length + '</li>';
            this.errorsEl.innerHTML = '';
        };

        xhr.onprogress = function(event) {
            if (!event.lengthComputable) return; // size unknown
            let percentComplete    = event.loaded / event.total * 100;
            this.infosEl.innerHTML = '<li>' + percentComplete + '%</li>';
        };

        xhr.onerror = function() {
            this.errorsEl.innerHTML = '<li>' + t('Connection error') + '</li>';
            this.infosEl.innerHTML  = '';
        };

        xhr.open('GET', url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send();
    },


    jsonPathToValue: function(jsonData, path) {
        if (!(jsonData instanceof Object) || typeof (path) === 'undefined') {
            throw 'Not valid argument:jsonData:' + jsonData + ', path:' + path;
        }
        path          = path.replace(/\[(\w+)\]/g, '.$1'); // convert indexes to properties
        path          = path.replace(/^\./, ''); // strip a leading dot
        let pathArray = path.split('.');
        for (let i = 0, n = pathArray.length; i < n; ++i) {
            let key = pathArray[i];
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
    },


    dateFormatDate: function(date) {
        let day   = ('0' + date.getDate()).slice(-2);
        let month = ('0' + (date.getMonth() + 1)).slice(-2);
        let year  = date.getFullYear();
        return year + '-' + month + '-' + day;
    },

    dateFormatTime: function(date) {
        let hour   = ('0' + date.getHours()).toString().slice(-2);
        let minute = ('0' + date.getMinutes()).toString().slice(-2);
        return hour + ':' + minute;
    },

    dateFormatDateTime: function(date) {
        let day    = ('0' + date.getDate()).slice(-2);
        let month  = ('0' + (date.getMonth() + 1)).slice(-2);
        let year   = date.getFullYear();
        let hour   = ('0' + date.getHours()).toString().slice(-2);
        let minute = ('0' + date.getMinutes()).toString().slice(-2);
        return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + '00';
    },

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
    var text = gjInput.removeDiacritics(text);
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





/****************************/
/******  translate.js  ******/
/****************************/

let gjTranslate = {

    input: function(el) {
        let elWrap = el.closest('.input-wrap');
        if (!elWrap) return;

        let elTrix  = elWrap.querySelector('trix-editor');
        let elInput = elWrap.querySelector('input, textarea');
        if (!elInput) return;

        let text = elInput.value;
        if (!text) return;

        let lang = elWrap.querySelector('.input-label-lang')?.innerText;

        let xhr       = new XMLHttpRequest();
        xhr.elWrap    = elWrap;
        xhr.elInput   = elInput;
        xhr.elTrix    = elTrix;
        xhr.textInput = text;

        xhr.onload = function() {
            let text = this.responseText;
            if (this.status !== 200 && text !== 'ok') return;

            if (this.elTrix) {
                let editor = this.elTrix.editor;

                text = text.replaceAll('<p> ', '<p>');
                text = text.replaceAll('<br> ', '<br>');
                text = text.replaceAll('<h1> ', '<h1>');
                text = text.replaceAll('<h2> ', '<h2>');
                text = text.replaceAll('<blockquote> ', '<blockquote>');
                text = text.replaceAll('<li> ', '<li>');

                text = text.replaceAll('</strong> ,', '</strong>,');
                text = text.replaceAll('</strong> .', '</strong>.');
                text = text.replaceAll('</em> ,', '</em>,');
                text = text.replaceAll('</em> .', '</em>.');
                text = text.replaceAll('</del> ,', '</del>,');
                text = text.replaceAll('</del> .', '</del>.');

                editor.composition.replaceHTML(text);
                return;
            }

            // console.log(text);
            text = gjTranslate.decodeHtml(text);
            // console.log(text);
            this.elInput.value = text;
        };

        xhr.onprogress = function(event) {
            if (!event.lengthComputable) return; // size unknown
            let percentComplete = event.loaded / event.total * 100;
            // console.log(percentComplete);
        };

        xhr.onerror = function() {
            console.error('Resize request error.');
        };

        let post     = {text: text, lang: lang};
        let formData = new FormData();
        formData.append('text', text);
        formData.append('lang', lang);

        xhr.open('POST', '/' + gtranslate);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    },


    decodeHtml: function(html) {
        let txt       = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }

}




