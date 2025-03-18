const optFriend = document.getElementById('optFriend');
const friendsAdded = document.getElementById('friendsAdded');

//TODO
function addFriend(name, id, adding, userMade) {
    const friendDiv = document.createElement('div');
    friendDiv.classList.add('friend');

    if (adding) {
        friendDiv.classList.add('adding');
    }

    
    if(userMade) {
        friendDiv.innerHTML = `
            <span>${name}</span>
            <button type="button" class="btn btn-danger btn-sm">X</button>
        `;
    }
    else {
        friendDiv.innerHTML = `
            <span>${name}</span>
            <button type="button" class="btn btn-danger btn-sm" disabled>X</button>
        `;
    }

    friendDiv.id = id;
    friendsAdded.appendChild(friendDiv);

}

function handleEvents() {
    optFriend.addEventListener('change', e => {
        const results = document.getElementById('friendsResults');
        results.innerHTML = "";

        if(document.getElementById(e.target.value) && !document.getElementById(e.target.value).hidden) {
            results.innerHTML = "Friend already added";
            optFriend.value = "";

            return;
        }

        if(document.getElementsByClassName('friend').length >= 10) {
            results.innerHTML = "Maximum friends limit reached";
            optFriend.value = "";

            return;
        }
        addFriend(e.target.options[e.target.selectedIndex].text, e.target.value, true, true);
        optFriend.value = "";
    });

    friendsAdded.addEventListener('click', e => {
        if(e.target.classList.contains('notUserMade')) {
            return;
        } else {
            if (e.target.tagName === 'BUTTON') {
                e.target.parentElement.hidden = true;
            }
        }
        
    });
}
                            
handleEvents();