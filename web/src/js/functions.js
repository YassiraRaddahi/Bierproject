function searchBeer() {
    let zoekterm = document.getElementById("zoekveld").value.trim();

    if (zoekterm != null && /^\d+$/.test(zoekterm)) {
        fetchBeer(zoekterm);
        console.log(zoekterm);
    }
    else {
        fetchBeers();
        console.log(zoekterm);
    }
}

function fetchBeer(zoekterm) {
    fetch(`${API_BASE}/${encodeURIComponent(zoekterm)}`)
        .then(res => res.json())
        .then(fetched_beer => {
            if (fetched_beer.error != null) {
                document.getElementById("bier-list").innerHTML = "Dit biertje bestaat niet!";
                return;
            }
            console.log(fetched_beer);
            showTable(fetched_beer);
        })
        .catch(error => console.error('Error fetching data:', error));
}


function fetchBeers() {
    fetch(API_BASE)
        .then(res => res.json())
        .then(fetched_beers => {
            console.log(fetched_beers);
            showTable(fetched_beers);
        })
        .catch(error => console.error('Error fetching data:', error));
}

// Niet vergeten: Ik moet er nog voor zorgen dat ik na het fetchen van de data de like knop niet klikbaar maak, 
// zodat ik niet meerdere likes kan geven aan hetzelfde biertje zonder de eerdere fetch te hebben afgerond.
function likeBeer(isArray, id) {
    let clicked_button = document.getElementById(`like-button-${id}`);
    console.log(clicked_button);
    clicked_button.disabled = true;

    fetch(`${API_BASE}/${encodeURIComponent(id)}/likes`,
        {
            method: "POST",
            headers: { "content-type": "application/json; charset=UTF-8" }
        })
        .then(res => res.json())
        .then(fetched_result => {
            if (fetched_result.error != null) {
                console.error(fetched_result.error);
                return;
            }

            if (isArray) {
                fetchBeers();
                fetchTopxLikedBeer(topx);
            } else {
                fetchBeer(id);
                fetchTopxLikedBeer(topx);
            }

            console.log(fetched_result.message);

        })
        .catch(error => console.error('Error fetching data:', error));
}

function fetchTopxLikedBeer(topx) {
    let top = topx;
    document.getElementById("top-x-title-text").innerHTML = `Top ${top} meest gelikete biertjes:`;

    fetch(`${API_BASE}/likes/top/${top}`)
        .then(res => res.json())
        .then(fetched_beers => {
            console.log(fetched_beers);
            showTopxLikedBeer(topx, fetched_beers);
        })
        .catch(error => console.error('Error fetching data:', error));
}

function showTopxLikedBeer(topx, data) {
    let html = "";

    for (let i = 0; i < data.length; i++) {
        if (data[i].likes > 0) {
            html += `<p>Nr. ${i + 1} ${data[i].naam} - <b>${data[i].likes}</b> like(s)</p>`;

        }
        else {
            html = "";
            break;
        }
    }

    if (html == "") {
        document.getElementById("top-x-information").innerHTML = `De top ${topx} komt hieronder te staan zodra deze bekend is!`;
    }
    else {
        document.getElementById("top-x-information").innerHTML = html;
    }

}


function showTable(data) {
    let html = "<div>"
    html += "<table class='table'>";
    html += "<thead>";
    html += "<tr>";
    html += "<th scope='col'>Naam</th>";
    html += "<th scope='col'>Brouwer</th>";
    html += "<th scope='col'>Likes</th>";
    html += "</tr>";
    html += "</thead>"
    html += "<tbody>";

    if (Array.isArray(data)) {
        for (let i = 0; i < data.length; i++) {
            html += "<tr>";
            html += `<td>${data[i].naam}</td>`;
            html += `<td>${data[i].brouwer}</td>`;
            html += `<td>${data[i].likes} <button id="like-button-${data[i].id}" class="button-heart" onclick="likeBeer(true, ${data[i].id})"><i class="fa-regular fa-heart like-heart"></i></button></td>`;
            html += "</tr>";
        }
    }
    else {
        html += "<tr>";
        html += `<td>${data.naam}</td>`;
        html += `<td>${data.brouwer}</td>`;
        html += `<td>${data.likes} <button id="like-button-${data.id}" class="button-heart" onclick="likeBeer(false, ${data.id})"><i class="fa-regular fa-heart like-heart"></i></button></td>`;
        html += "</tr>";
    }
    html += "</tbody>";
    html += "</table>";
    document.getElementById("bier-list").innerHTML = html;
    console.log("Bieren geladen");
}
