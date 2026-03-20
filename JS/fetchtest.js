let urluser = ("https://jsonplaceholder.typicode.com/users");
    fetch (urlusers).then(response => response.json()).then(data => MostrarData(data)).catch(error => console.log(error));

let urlclients = ("");

const MostrarData = (data) => {
    let body = ''
    for (let i = 0; i < data.length; i++){
        body += `<tr><td>${data[i].id}</td><td>${data[i].name}</td><td>${data[i].username}</td><td>${data[i].email}</td><td>${data[i].address.city}</td><td>${data[i].address.geo.lat}</td><td>${data[i].address.geo.lng}</td></tr>`
    }
    document.querySelector("#datauser").innerHTML = body
}