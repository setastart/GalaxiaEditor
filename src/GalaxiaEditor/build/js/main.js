let gjTextareas                    = [];
let gjResizeTimeout                = null;
let filterData                     = ['pageCurrent', 'pageFirst', 'pagePrev', 'pageNext', 'pageLast', 'itemsPerPage', 'rowsFiltered', 'rowsTotal'];


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
    window.addEventListener('beforeunload', handleEventBeforeunload, true);


    gjImage.init();
    gjTextareas     = document.getElementsByTagName('textarea');

    gjInput.textareaResize();


    // prepare form pagination
    for (let i = 0; i < document.forms.length; i++) {
        document.forms[i].pagination = [];
        filterData.forEach(function (el) {
            document.forms[i].pagination[el] = document.forms[i].querySelectorAll('.' + el);
        });
    }


    // on window resize with debounce
    window.onresize = function () {
        if (gjResizeTimeout != null) clearTimeout(gjResizeTimeout);
        gjResizeTimeout = setTimeout(gjInput.textareaResize, 100);
    }
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
    if (ev.target.matches && ev.target.matches('.input-select')) {
        gjInput.change(ev.target);
    }
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

    // checkbox alk key
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
        gjImage.openGallery(fieldId, imgType, pos)
    }

    if (ev.target.matches('.imageList-delete')) {
        let fieldId = ev.target.closest('.module-field')?.id ?? ev.target.closest('.module-field-multi-header')?.nextElementSibling.id;
        if (!fieldId) return;
        gjImage.openGallery(fieldId, '', 0)
    }


    if (ev.target.matches('.ev-module-add')) {
        let pos = ev.target.closest('.module-field-group')?.querySelector('.module-position') ?? 0;
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
        if (ev.target.matches('.input-text')) {
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


function handleEventBeforeunload(ev) {
    gjImage.close();
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
