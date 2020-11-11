
// initialize gchat

window.addEventListener("DOMContentLoaded", function(event) {
    gchat.roomNodes = document.querySelectorAll('.gchat-room');
    if (!gchat.roomNodes.length) {
        console.error('no rooms found');
        return;
    }

    gchat.listenStatus = document.getElementById('status-listen');
    if (!gchat.listenStatus) {
        console.error('missing status-listen element');
        return;
    }

    const geVars = ['myId', 'clientId', 'csrf'];
    for (let varName in geVars) {
        if (gchat[geVars[varName]] === undefined) {
            console.error('variable ' + geVars[varName] + ' missing');
            return;
        }
    }

    gchat.listenReq = {
        'csrf': gchat.csrf,
        'clientId': gchat.clientId,
        'rooms': {}
    };

    let roomsFound = false;
    for (let i = 0; i < gchat.roomNodes.length; i++) {
        const room = gchat.roomNodes[i].dataset.room;
        if (!room) {
            console.log('invalid room: ' + room + ' (skipping)');
            continue;
        }
        if (gchat.rooms[room] !== undefined) {
            console.log('duplicate room: ' + room + ' (skipping)');
            continue;
        }

        gchat.rooms[room] = {'els': {}};
        gchat.rooms[room].els.title           = gchat.roomNodes[i].querySelectorAll('.title')[0];
        gchat.rooms[room].els.users           = gchat.roomNodes[i].querySelectorAll('.users')[0];
        gchat.rooms[room].els.messagesWrapper = gchat.roomNodes[i].querySelectorAll('.messages-wrapper')[0];
        gchat.rooms[room].els.messages        = gchat.roomNodes[i].querySelectorAll('.messages')[0];
        gchat.rooms[room].els.publishStatus   = gchat.roomNodes[i].querySelectorAll('.status-publish')[0];
        gchat.rooms[room].els.send            = gchat.roomNodes[i].querySelectorAll('.send')[0];
        gchat.rooms[room].els.sendTextarea    = gchat.roomNodes[i].querySelectorAll('.send textarea')[0];
        gchat.rooms[room].els.sendButton      = gchat.roomNodes[i].querySelectorAll('.send button')[0];
        gchat.rooms[room].users    = [];
        gchat.rooms[room].lastId   = '0';
        gchat.rooms[room].datePrev = false;

        gchat.listenReq.rooms[room] = {
            'users'  : [],
            'lastId' : '0'
        };

        var elsRequired = ['title', 'users', 'messagesWrapper', 'messages', 'publishStatus', 'send'];
        var elsMissing = false;
        for (el in elsRequired) {
            if (!gchat.rooms[room].els[elsRequired[el]]) {
                console.error('missing element ' + elsRequired[el] + ' from room: ' + room);
                elsMissing = true;
            }
        }
        if (elsMissing) delete gchat.rooms[room];
        else {
            gchat.rooms[room].name = gchat.rooms[room].els.title.innerText;
            gchat.rooms[room].els.title.addEventListener('click', function(ev) {
                ev.target.parentNode.classList.toggle('active');
                var targetRoom = ev.target.parentNode.dataset.room;
                window.setTimeout(gjcScrollToBottom, 150, targetRoom);
                gchat.rooms[targetRoom].els.sendTextarea.focus();
            });
            roomsFound = true;
        }
    }
    if (roomsFound) gchat.enabled = true;
});

window.addEventListener("load", function (e) {
    if (sessionStorage.getItem("clientId")) gchat.clientId = sessionStorage.getItem("clientId");
    else sessionStorage.setItem("clientId", gchat.clientId);
    gjcListen();
});




// listen to new messages and enter/leave activity for all rooms

function gjcListen() {
    if (!gchat.enabled) return;

    gchat.listenXhr = new XMLHttpRequest();
    gchat.listenXhr.open('POST', '/edit/chat/listen');
    gchat.listenXhr.setRequestHeader('Content-Type', 'application/json');
    gchat.listenXhr.onload = function() {

        if (gchat.listenXhr.readyState !== 4 || gchat.listenXhr.status !== 200) {
            console.error(gchat.listenXhr.statusText, gchat.listenXhr);
            gchat.listenStatus.innerHTML = 'error ' + gchat.listenXhr.statusText;
            gchat.listenStatus.classList.value = 'red';
            return;
        }

        var json = JSON.parse(gchat.listenXhr.response);

        if (json.status != 'ok') {
            console.error(json);
            gchat.listenStatus.innerHTML = 'error ' + json.error;
            gchat.listenStatus.classList.value = 'red';
            return;
        }

        var localDateFormatter = new Intl.DateTimeFormat(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        for (let room in json.rooms) {
            for (msgId in json.rooms[room].messages) {
                var entry = '';
                var msg = json.rooms[room].messages[msgId];
                var msgDate = new Date(parseInt(msg.timestamp));
                var date = msgDate.getDate() + '-' + msgDate.getDate() + '-' + msgDate.getDate();
                if (date != gchat.rooms[room].datePrev) {
                    entry += '<div style="order: ' + msg.timestamp + '" class="gchat-room-date"><span class="date">' + localDateFormatter.format(msgDate) + '</span></div>';
                    gchat.rooms[room].datePrev = date;
                }

                entry += '<div style="order: ' + msg.timestamp + '" class="gchat-room-msg ' + msg.type + ' s0i s1i' + ((msg.user == gchat.myId) ? ' me' : '') + '" data-filter-text-0="' + removeDiacritics(json.users[msg.user].name) + '" data-filter-text-1="' + removeDiacritics(msg.content) + '">';
                entry += '    <span class="user brewer-dark-' + ((msg.user % 10) + 1) + '">' + json.users[msg.user].name + '</span>';
                entry += '    <span class="msg">' + msg.content + '</span>';
                entry += '    <small class="time grey">' + msgDate.toTimeString().slice(0, 5) + '</small>';
                entry += '</div>';
                gchat.rooms[room].els.messages.insertAdjacentHTML('beforeend', entry);
            }

            var roomUsers = ''
            for (user in json.rooms[room].users)
                roomUsers += '<span class="brewer-dark-' + ((user % 10) + 1) + '">' + json.users[user].name + ((json.rooms[room].users[user] > 1) ? ' (' + json.rooms[room].users[user] + ')' : '') + '</span>';
            gchat.rooms[room].els.users.innerHTML = roomUsers;

            if (gchat.listenReq.rooms[room].lastId) {
                var i;

                var enterUsers = difference(Object.keys(json.rooms[room].users), Object.keys(gchat.listenReq.rooms[room].users));
                for (i = 0; i < enterUsers.length; i++) {
                    // if (enterUsers[i] == gchat.myId) continue;
                    // console.log('entered room ' + room + ': ', json.users[enterUsers[i]].name);
                    var msgDate = new Date(parseInt(json.users[enterUsers[i]].lastSeen));
                    entry  = '<div style="order: ' + json.users[enterUsers[i]].lastSeen + '" class="gchat-room-msg enter">';
                    entry += '    <small class="time grey">' + json.users[enterUsers[i]].name + ' entrou 🙋🏻‍♀️ ' + msgDate.toTimeString().slice(0, 5) + '</small>';
                    entry += '</div>';
                    gchat.rooms[room].els.messages.insertAdjacentHTML('beforeend', entry);
                }

                var leaveUsers = difference(Object.keys(gchat.listenReq.rooms[room].users), Object.keys(json.rooms[room].users));
                for (i = 0; i < leaveUsers.length; i++) {
                    // if (leaveUsers[i] == gchat.myId) continue;
                    // console.log('left: ', json.users[leaveUsers[i]].name);
                    var msgDate = new Date(parseInt(json.users[leaveUsers[i]].lastSeen));
                    entry  = '<div style="order: ' + json.users[leaveUsers[i]].lastSeen + '" class="gchat-room-msg enter">';
                    entry += '    <small class="time grey">' + json.users[leaveUsers[i]].name + ' saiu 💤 ' + msgDate.toTimeString().slice(0, 5) + '</small>';
                    entry += '</div>';
                    gchat.rooms[room].els.messages.insertAdjacentHTML('beforeend', entry);
                }

            }
            gchat.listenReq.rooms[room].users = json.rooms[room].users;

            gjcScrollToBottom(room);
            if (json.rooms[room].lastId) gchat.listenReq.rooms[room].lastId = json.rooms[room].lastId;
        }

        gchat.listenStatus.innerHTML = gchat.listenXhr.status;
        gchat.listenStatus.classList.value = 'green';
        if (gchat.listenXhr) gchat.listenXhr.abort();
        window.clearTimeout(gchat.listenTimeout);
        // console.log('restarting listening');
        // gchat.listenTimeout = window.setTimeout(gjcListen, 1000);
        gjcListen();
    };
    gchat.listenXhr.onerror = function(event) {
        console.log("An error occurred while transferring the file.");
        gchat.listenStatus.innerHTML = 'error, reconnecting...';
        gchat.listenStatus.classList.value = 'red';
        // window.clearTimeout(gchat.listenTimeout);
        // gchat.listenTimeout = window.setTimeout(gjcListen, 2000);
    };
    gchat.listenXhr.onabort = function(event) {
        console.log("Canceled by the user.");
        gchat.listenStatus.innerHTML = 'canceled';
        gchat.listenStatus.classList.value = 'yellow';
    };

    // console.log('listening...');
    gchat.listenXhr.send(JSON.stringify(gchat.listenReq));
}




// post message

function gjcEnterSend(e, event, room) {
    if (!gchat.enabled) return;
    if (event.key !== 'Enter' || event.shiftKey) return;
    msgSend(room, 'speak', e.value, e, e.nextElementSibling);
    event.preventDefault();
    return false;
}

function gjcClickSend(e, room) {
    if (!gchat.enabled) return;
    msgSend(room, 'speak', e.previousElementSibling.value, e.previousElementSibling, e);
    event.preventDefault();
    return false;
}

function msgSend(room, type, msg, eText, eBtn) {
    if (!gchat.enabled) return;
    if (!msg) {
        console.log('msg empty');
        gchat.rooms[room].els.publishStatus.innerHTML = 'empty';
        gchat.rooms[room].els.publishStatus.classList.value = 'status-publish blue';
        return;
    }

    console.log('sending msg "' + msg + '" to room ' + room);
    eText.disabled = true;
    eBtn.disabled = true;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/edit/chat/publish');
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onprogress = function(event) {
        if (!event.lengthComputable) return; // size unknown
        var percentComplete = event.loaded / event.total * 100;
        gchat.rooms[room].els.publishStatus.innerHTML = percentComplete + '%';
        gchat.rooms[room].els.publishStatus.classList.value = 'status-publish blue';
    };

    xhr.onload = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var json = JSON.parse(xhr.response);
            if (json.status === 'ok') {
                eText.value = '';
                eText.focus();

                gchat.rooms[room].els.publishStatus.innerHTML = xhr.statusText;
                gchat.rooms[room].els.publishStatus.classList.value = 'status-publish green';
            } else {
                gchat.rooms[room].els.publishStatus.innerHTML = 'error ' + json.error;
                gchat.rooms[room].els.publishStatus.classList.value = 'status-publish red';
            }
            eText.disabled = false;
            eBtn.disabled = false;

        } else {
            console.error(xhr);
            gchat.rooms[room].els.publishStatus.innerHTML = 'error ' + xhr.statusText;
            gchat.rooms[room].els.publishStatus.classList.value = 'status-publish red';
        }
    };

    xhr.onerror = function(event) {
        console.log("An error occurred while transferring the file.");
        gchat.rooms[room].els.publishStatus.innerHTML = 'error';
        gchat.rooms[room].els.publishStatus.classList.value = 'status-publish red';
    };

    xhr.onabort = function(event) {
        console.log("The transfer has been canceled by the user.");
        gchat.rooms[room].els.publishStatus.innerHTML = 'canceled';
        gchat.rooms[room].els.publishStatus.classList.value = 'status-publish yellow';
    };

    gjcPostRequest = {
        'csrf': gchat.listenReq.csrf,
        'clientId': gchat.listenReq.clientId,
        'type': type,
        'msg':  msg,
        'room': room
    };
    xhr.send(JSON.stringify(gjcPostRequest));
}




// leave rooms when closing tab/window

window.addEventListener('beforeunload', function() { gjcLeaveRooms(); });
window.addEventListener("unload", function() { gjcLeaveRooms(); });
function gjcLeaveRooms() {
    if (!gchat.enabled) return;
    if (gchat.leaving) return;

    gchat.listenXhr.abort();

    var leaveRequest = {
        'csrf': gchat.listenReq.csrf,
        'clientId': gchat.listenReq.clientId,
        'type': 'leave',
        'msg': '',
        'rooms': Object.keys(gchat.rooms),
    };

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/edit/chat/publish', false);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(leaveRequest));

    gchat.leaving = true;
}















function gjcScrollToBottom(room) {
    // console.log('scrolol ' + room);
    if (!gchat.enabled) return;
    if (gchat.rooms[room] === undefined) return;
    gchat.rooms[room].els.messagesWrapper.scrollTop = gchat.rooms[room].els.messagesWrapper.scrollHeight;
}




function difference(a1, a2) {
    let result = [];
    for (let i = 0; i < a1.length; i++) {
        if (!a2.contains(a1[i])) {
            result.push(a1[i]);
        }
    }
    return result;
}
