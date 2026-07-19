async function post(url, data) {

    const response =
        await fetch(url, {
            method: 'POST',
            body: data
        });

    return await response.json();
}

function toast(message) {

    const div =
        document.createElement('div');

    div.className = 'toast';

    div.innerHTML = message;

    document.body.appendChild(div);

    setTimeout(() => {
        div.remove();
    }, 3000);
}