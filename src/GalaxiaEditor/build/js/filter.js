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

