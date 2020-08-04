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

