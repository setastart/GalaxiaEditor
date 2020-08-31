
function filter(el, ev, highlight) {
    var xhr = new XMLHttpRequest();
    var fd = new FormData(el.form);
    if (el.tagName == 'BUTTON') fd.set(el.name, el.value);

    xhr.form = el.form;
    xhr.onload = function(event) {
        var loadEl = this.form.querySelector('.load');
        loadEl.innerHTML = event.target.responseText;

        gjImage.paintSelectedActivated();

        var results = loadEl.querySelector('.results').dataset;

        for (var i = 0; i < filterData.length; i++) {
            var el = filterData[i];
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
    ev.preventDefault();
    return false;
}
function filterEmpty(el, ev) {
    if (el.parentNode.classList.contains('active')) {
        el.name = undefined;
        el.value = undefined;
        el.parentNode.previousElementSibling.disabled = false;
    } else {
        el.name = el.parentNode.previousElementSibling.name;
        el.value = '{{empty}}'
        el.parentNode.previousElementSibling.disabled = true;
    }
    filter(el, ev);
}
