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
            if (this.status !== 200 && this.responseText !== 'ok') return;

            if (this.elTrix) {
                let editor = this.elTrix.editor;
                let text   = this.responseText;

                text = text.replaceAll('<p> ', '<p>');
                text = text.replaceAll('<br> ', '<br>');
                text = text.replaceAll('<h1> ', '<h1>');
                text = text.replaceAll('<h2> ', '<h2>');
                text = text.replaceAll('<blockquote> ', '<blockquote>');

                text = text.replaceAll('</strong> ,', '</strong>,');
                text = text.replaceAll('</strong> .', '</strong>.');
                text = text.replaceAll('</em> ,', '</em>,');
                text = text.replaceAll('</em> .', '</em>.');
                text = text.replaceAll('</del> ,', '</del>,');
                text = text.replaceAll('</del> .', '</del>.');

                editor.composition.replaceHTML(text);
                return;
            }
            if (this.elInput.tagName === 'TEXTAREA') {
                this.elInput.innerHTML = this.responseText;
            }
        };

        xhr.onprogress = function(event) {
            if (!event.lengthComputable) return; // size unknown
            let percentComplete = event.loaded / event.total * 100;
            console.log(percentComplete);
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
    }

}
