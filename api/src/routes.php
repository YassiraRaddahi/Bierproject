<?php


// Uit bier api

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$id = (int) (int) array_slice($segments, -1, 1)[0];


$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "GET":
        if ($id > 0) {
            echo showBeer($conn, $id);
        } else {
            echo showBeers($conn);
        }
        break;
    case "POST":
        echo createBeer($conn);
        break;
    case "PUT":
        if ($id > 0) {
            echo updateBeer($conn, $id);
        }
        else
        {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "You need to specify whitch beer to update"]);
        }
        break;
    case "PATCH":
        break;
    case "DELETE":
        if ($id > 0) {
            echo deleteBeer($conn, $id);
        }
        else
        {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "You need to specify whitch beer to remove"]);
        }
        break;
}

// Uit likes Api

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$indexApi = strpos($path, '/api');
$apiPath = substr($path, $indexApi);
$segments = explode('/', trim($apiPath, '/'));
//echo json_encode($segments);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "GET":
        //api/bieren/likes
        if (count($segments) === 3 && $segments[2] === 'likes') 
        {
            echo showLikes($conn);
            break;
        } 
        //api/bieren/likes/top/([0-9]+)
        else if (count($segments) === 5 && filter_var($segments[4], FILTER_VALIDATE_INT) !== false) 
        {
            $topx = (int) $segments[4];
            echo showTopxLikedBeer($conn, $topx);
            break;
        } 
        else 
        {
            http_response_code(404); // not found
            echo json_encode(["error" => "There is no response for this url"]);
            break;
        }
    case "POST":
        $id = (int) array_slice($segments, -2, 1)[0];
        if ($id > 0) {
            echo addLike($conn, $id);
        }
        break;
}

