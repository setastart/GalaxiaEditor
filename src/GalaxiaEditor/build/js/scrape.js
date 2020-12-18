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
                            gjImage.setInputAndImage(inputEl, img);
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
