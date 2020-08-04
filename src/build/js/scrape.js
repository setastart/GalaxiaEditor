
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
