function gjModuleMoveUp(el) {
    var target = document.getElementById(el.dataset.target);
    var groups = target.parentNode;
    if (target.previousElementSibling)
        groups.insertBefore(target, target.previousElementSibling);
    gjModuleCountPositions(groups);
    target.querySelector('.module-position').focus();
}

function gjModuleMoveDown(el) {
    var target = document.getElementById(el.dataset.target);
    var groups = target.parentNode;
    if (target.nextElementSibling)
        groups.insertBefore(target.nextElementSibling, target);
    gjModuleCountPositions(groups);
    target.querySelector('.module-position').focus();
}

function gjModuleMove(el, pos = null) {
    var target = document.getElementById(el.dataset.target);
    var positionEl = target.querySelector('.module-position');

    if (pos) {
        positionEl.value = pos;
        console.log('pos', positionEl.value);
    } else {
        pos = positionEl.value;
    }
    console.log(target);
    var parent = target.parentNode;
    var groups = parent.children;

    if (pos >= groups.length) {
        parent.appendChild(target);
    } else {
        var j = 1;
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].dataset === undefined || groups[i].dataset.disabled) continue;
            if (groups[i] === target) continue;

            if (j == pos) {
                parent.insertBefore(target, groups[i]);
            }
            j++;
        }
    }
    target.querySelector('.module-position').focus();

    gjModuleCountPositions(target.parentNode);
}

function gjCloneModuleInputs(el) {
    const groupId = gjUnique();
    let newGroup  = document.getElementById(el.dataset.target).cloneNode(true);
    let where = document.getElementById(el.dataset.where);
    const oldGroup = newGroup.id;

    newGroup.classList.remove('hide');
    newGroup.classList.add('module-field-group-new');
    newGroup.id = newGroup.id + '-' + groupId;
    newGroup.groupId = groupId;

    let inputs = newGroup.getElementsByTagName('input');
    let selects = newGroup.getElementsByTagName('select');
    let textareas = newGroup.getElementsByTagName('textarea');
    let buttons = newGroup.getElementsByTagName('button');
    let i;
    for (i = inputs.length - 1; i >= 0; i--) {
        inputs[i].name = inputs[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        inputs[i].disabled = false;
        if (inputs[i].dataset.target !== undefined)
            inputs[i].dataset.target = newGroup.id;
    }
    for (i = selects.length - 1; i >= 0; i--) {
        selects[i].name = selects[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        selects[i].disabled = false;
        if (selects[i].dataset.target !== undefined)
            selects[i].dataset.target = newGroup.id;
    }
    for (i = textareas.length - 1; i >= 0; i--) {
        textareas[i].name = textareas[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        textareas[i].disabled = false;
        if (textareas[i].dataset.target !== undefined)
            textareas[i].dataset.target = newGroup.id;
    }
    for (i = buttons.length - 1; i >= 0; i--) {
        if (buttons[i].classList.contains('ev-module-add')) {
            continue;
        }
        buttons[i].name = buttons[i].name.replace('\]\[new-0\]\[', '][new-' + groupId + '][');
        buttons[i].disabled = false;
        if (buttons[i].dataset.target !== undefined)
            buttons[i].dataset.target = newGroup.id;
    }

    where.prepend(newGroup);
    gjModuleMove(newGroup.querySelector('.ev-module-go'), el.dataset.pos);
    return newGroup;
}


function gjDeleteModuleInputs(el) {
    var check = el.nextElementSibling;
    var row = document.getElementById(el.dataset.target);
    var group = document.getElementById(el.dataset.target).parentNode;
    var inputs = row.getElementsByTagName('input');
    var buttons = row.getElementsByTagName('button');
    var selects = row.getElementsByTagName('select');
    var radios  = row.getElementsByTagName('radio');
    var textareas = row.getElementsByTagName('textarea');
    var i;

    if (row.classList.contains('module-field-group-new')) {
        group.removeChild(row);
        gjModuleCountPositions(group);
        return;
    }

    if (row.dataset.disabled) {
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
    el.disabled = false;
    check.disabled = false;
    gjModuleCountPositions(group);
}


function gjModuleCountPositions(el) {
    let groups = el.childNodes;
    let j = 0;
    for (let i = 0; i < groups.length; i++) {
        if (groups[i].dataset === undefined || groups[i].dataset.disabled) continue;
        j++;

        let pos = groups[i].querySelector('.module-position');
        if (pos) pos.value = j;

        let posBefore = groups[i].querySelector('.module-field-gallery-add.before');
        if (posBefore) {
            posBefore.dataset.pos = j;
            // posBefore.innerHTML = posBefore.dataset.pos;
        }

        let posAfter = groups[i].querySelector('.module-field-gallery-add.after');
        if (posAfter) {
            posAfter.dataset.pos = (j + 1);
            // posAfter.innerHTML = posAfter.dataset.pos;
        }
    }
}
