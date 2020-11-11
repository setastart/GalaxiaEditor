let gjImage = {

    editorImageSlug: 'image',

    init: function() {
        this.firstTime = true;
        this.el        = document.getElementById('image-select');

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
        if (this.el.classList.contains('hide')) return
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

            this.setInputAndImage(this.activeInput, img);
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

            this.setInputAndImage(imageInput, ordered[imgSlug]);
        }

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

    setInputAndImage: function(inputEl, img) {
        inputEl.value   = img.slug;
        let selectorImg = inputEl.nextElementSibling.children[0];
        selectorImg.parentNode.classList.remove('empty');
        selectorImg.src = img.src;
        selectorImg.setAttribute('width', img.w);
        selectorImg.setAttribute('height', img.h);
        textareaAutoGrow(inputEl)
    },

    resizeRequest: function(el) {
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


