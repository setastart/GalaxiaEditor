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
