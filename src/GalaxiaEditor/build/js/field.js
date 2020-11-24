let gjField = {

    moveUp: function(el) {
        let target = document.getElementById(el.dataset.target);
        let groups = target.parentNode;
        if (target.previousElementSibling)
            groups.insertBefore(target, target.previousElementSibling);
        gjField.countPos(groups);
        target.querySelector('.module-position').focus();
    },


    moveDown: function(el) {
        let target = document.getElementById(el.dataset.target);
        let groups = target.parentNode;
        if (target.nextElementSibling)
            groups.insertBefore(target.nextElementSibling, target);
        gjField.countPos(groups);
        target.querySelector('.module-position').focus();
    },

    moveTo: function(el, pos = null) {
        let target     = document.getElementById(el.dataset.target);
        let positionEl = target.querySelector('.module-position');

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

    cloneNew: function(fieldId, pos) {
        const groupId = gjUnique();
        let newGroup   = document.getElementById(fieldId + '-new').cloneNode(true);
        let where      = document.getElementById(fieldId);

        newGroup.classList.remove('hide');
        newGroup.classList.add('module-field-group-new');
        newGroup.id      = newGroup.id + '-' + groupId;
        newGroup.groupId = groupId;

        let inputs    = newGroup.getElementsByTagName('input');
        let selects   = newGroup.getElementsByTagName('select');
        let textareas = newGroup.getElementsByTagName('textarea');
        let buttons   = newGroup.getElementsByTagName('button');
        let i;
        for (i = inputs.length - 1; i >= 0; i--) {
            inputs[i].name     = inputs[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            inputs[i].disabled = false;
            if (inputs[i].dataset.target !== undefined) inputs[i].dataset.target = newGroup.id;
        }
        for (i = selects.length - 1; i >= 0; i--) {
            selects[i].name     = selects[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            selects[i].disabled = false;
            if (selects[i].dataset.target !== undefined) selects[i].dataset.target = newGroup.id;
        }
        for (i = textareas.length - 1; i >= 0; i--) {
            textareas[i].name     = textareas[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            textareas[i].disabled = false;
            if (textareas[i].dataset.target !== undefined) textareas[i].dataset.target = newGroup.id;
        }
        for (i = buttons.length - 1; i >= 0; i--) {
            if (buttons[i].classList.contains('ev-module-add')) continue;
            buttons[i].name     = buttons[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
            buttons[i].disabled = false;
            if (buttons[i].dataset.target !== undefined) buttons[i].dataset.target = newGroup.id;
        }

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
        el.previousElementSibling.querySelector('.module-field-count').innerHTML = j;
    },

}


