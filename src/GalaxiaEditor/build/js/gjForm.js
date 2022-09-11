let gjForm = {

    isSaving: false,
    elMain: null,
    fdMainInitial: null,

    init: function() {
        gjForm.elMain = document.forms[0];
        if (!gjForm.elMain) return;
        // if (gjForm.elMain.classList.contains('formDisable')) console.log('gjForm.init formDisable');
        // if (gjForm.elMain.classList.contains('formPrevent')) console.log('gjForm.init formPrevent');

        gjForm.fdMainInitial = new FormData(gjForm.elMain);
    },

    disableUnchanged: function() {
        if (!gjForm.elMain) return;
        gjForm.isSaving = true;
        if (!gjForm.elMain.classList.contains('formDisable')) return;
        let fdOld = gjForm.fdMainInitial;
        let fdNew = new FormData(gjForm.elMain);
        for (let input of gjForm.elMain.elements) {
            if (!input.name) continue;
            if (input.name === 'csrf') continue;
            if (!fdNew.has(input.name)) continue;
            if (!fdOld.has(input.name)) continue;
            if (fdNew.get(input.name) === fdOld.get(input.name)) {
                input.disabled = true;
            } else {
                // console.log(input.name, input.value, fdOld.get(input.name));
            }
        }
    },

    changed: function() {
        if (!gjForm.elMain) return false;
        if (!gjForm.elMain.classList.contains('formPrevent')) return;
        let fdOld = gjForm.fdMainInitial;
        let fdNew = new FormData(gjForm.elMain);
        for (let input of gjForm.elMain.elements) {
            if (!input.name) continue;
            if (input.name === 'csrf') continue;
            if (!fdNew.has(input.name)) continue;

            if (fdNew.get(input.name) instanceof File) {
                if (fdNew.get(input.name).name !== fdOld.get(input.name).name) {
                    // console.log('file', input.name, fdNew.get(input.name).name, fdOld.get(input.name).name);
                    return true;
                }
            } else {
                if (fdNew.get(input.name) !== fdOld.get(input.name)) {
                    // console.log(input.name, fdNew.get(input.name), fdOld.get(input.name));
                    return true;
                }
            }
        }
        return false;
    }
};
