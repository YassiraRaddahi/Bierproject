zoekterm = document.getElementById("zoekveld").value;

function fetchBeersWithLikes(zoekterm = "") {
    fetch(`${API_BASE}/beers/likes?include=relation_counts&search=${encodeURIComponent(zoekterm)}`)
        .then(res => res.json())
        .then(fetched_beers => {
            if (fetched_beers.error != null) {
                document.getElementById("bier-list").innerHTML = "Er zijn geen resultaten voor deze zoekterm!";
                return;
            }
            console.log(fetched_beers);
            showTable(fetched_beers);
        })
        .catch(error => console.error('Error fetching data:', error));
}


// Niet vergeten: Ik moet er nog voor zorgen dat ik na het fetchen van de data de like knop niet klikbaar maak, 
// zodat ik niet meerdere likes kan geven aan hetzelfde biertje zonder de eerdere fetch te hebben afgerond.
function likeBeer(id) {
    //let clicked_button = document.getElementById(`like-button-${id}`);
    //console.log(clicked_button);
    //clicked_button.disabled = true;

    //api/<collection_a>/<collection_b>/<id>
    fetch(`${API_BASE}/likes`,
        {
            method: "POST",
            headers: { "content-type": "application/json; charset=UTF-8" },
            body: JSON.stringify({ beer_id: id })
        })
        .then(res => res.json())
        .then(fetched_result => {
            if (fetched_result.error != null) {
                console.error(fetched_result.error);
                return;
            }

            if (!zoekterm || !zoekterm.trim()) {
                fetchBeersWithLikes();
               
            } 
            else 
            {
                fetchBeersWithLikes(zoekterm);
            }

             fetchTopxLikedBeer(topx);

            console.log(fetched_result.message);

        })
        .catch(error => console.error('Error fetching data:', error));
}

function fetchTopxLikedBeer(topx) {
    let top = topx;
    document.getElementById("top-x-title-text").innerHTML = `Top ${top} meest gelikete biertjes:`;

    fetch(`${API_BASE}/beers/likes/top/${top}`)
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
        if (data[i].likes_count > 0) {
            html += `<p>Nr. ${i + 1} ${data[i].beer_name} - <b>${data[i].likes_count}</b> like(s)</p>`;

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


    for (let i = 0; i < data.length; i++) {
        html += "<tr>";
        html += `<td>${data[i].beer_name}</td>`;
        html += `<td>${data[i].beer_brewer}</td>`;
        html += `<td>${data[i].likes_count} <button id="like-button-${data[i].beer_id}" class="button-heart" onclick="likeBeer(${data[i].beer_id})"><i class="fa-regular fa-heart like-heart"></i></button></td>`;
        html += "</tr>";
    }

    html += "</tbody>";
    html += "</table>";
    document.getElementById("bier-list").innerHTML = html;
    console.log("Bieren geladen");
}       
