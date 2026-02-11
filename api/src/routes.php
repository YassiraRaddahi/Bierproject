<?php

// Uit bier api

require_once($root . '/src/config/tables.php');
require_once($root . '/src/helpers/validation.php');

$uri =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basepath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$clean_path = trim(preg_replace('#^' . preg_quote($basepath) . '#', '', $uri), '/');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "GET":
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/?$#', $clean_path, $matches)) {
            $collection = $matches['collection'];
            $table = getTableName($allowed_tables, $collection);
            
            if($table) 
            {
                 echo all($conn, $table);
            }
           
            break;
            
        }

        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<id>\d+)/?$#', $clean_path, $matches)) 
        {
            $collection = $matches['collection'];
            $table = getTableName($allowed_tables, $collection);
            if (!$table) {
                break;
            }
            
            $id = (int) $matches['id'];
            
            if ($id > 0) {
                echo show($conn, $table, $id);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(["error" => "This beer does not exist"]);
            }

            break;
        }

        //api/bieren/likes
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/?$#', $clean_path, $matches)) 
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

        http_response_code(404); // Not Found
        echo json_encode(["message" => "No valid endpoint is inserted"]);
        break;
    case "POST":
         if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/?$#', $clean_path, $matches)) {
            $collection = $matches['collection'];
            $table = getTableName($allowed_tables, $collection);
            
            if($table) 
            {
                 echo create($conn, $table);
            }
           
            break;
            
        }
    case "PUT":
        if (preg_match('#^bieren/(?P<id>\d+)/?$#', $clean_path, $matches)) {
            $id = (int) $matches['id'];
            if ($id > 0) {
                echo update($conn, $table, $id);
            } else {
                http_response_code(404); // Bad Request
                echo json_encode(["error" => "This beer does not exist"]);
            }
            break;
        }
    case "PATCH":
        break;
    case "DELETE":
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<id>\d+)/?$#', $clean_path, $matches)) 
        {
            $collection = $matches['collection'];
            $id = (int) $matches['id'];
            $table = getTableName($allowed_tables, $collection);
            if (!$table) {
                break;
            }
            
            if ($id > 0) {
                echo delete($conn, $table, $id); 
            } else {
                http_response_code(404); // Not Found
                echo json_encode(["error" => "This beer does not exist"]);
            }

            break;
        }
}

// Uit likes Api

// $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// $indexApi = strpos($path, '/api');
// $apiPath = substr($path, $indexApi);
// $segments = explode('/', trim($apiPath, '/'));
// //echo json_encode($segments);

// $method = $_SERVER['REQUEST_METHOD'];

// switch ($method) {
//     case "GET":
//         //api/bieren/likes
//         if (count($segments) === 3 && $segments[2] === 'likes') 
//         {
//             echo showLikes($conn);
//             break;
//         } 
//         //api/bieren/likes/top/([0-9]+)
//         else if (count($segments) === 5 && filter_var($segments[4], FILTER_VALIDATE_INT) !== false) 
//         {
//             $topx = (int) $segments[4];
//             echo showTopxLikedBeer($conn, $topx);
//             break;
//         } 
//         else 
//         {
//             http_response_code(404); // not found
//             echo json_encode(["error" => "There is no response for this url"]);
//             break;
//         }
//     case "POST":
//         $id = (int) array_slice($segments, -2, 1)[0];
//         if ($id > 0) {
//             echo addLike($conn, $id);
//         }
//         break;
// }
