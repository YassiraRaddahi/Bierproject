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
        // endpoint api/<collection>
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/?$#', $clean_path, $matches)) {
            $collection = $matches['collection'];
            $table = getTableName($allowed_tables, $collection);
            
            if($table) 
            {
                 echo all($conn, $table);
            }
           
            break;
            
        }

        // endpoint api/<collection>/<id>
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<id>\d+)/?$#', $clean_path, $matches)) 
        {
            $collection = $matches['collection'];
            $id = (int) $matches['id'];

            $table = getTableName($allowed_tables, $collection);
            if (!$table) {
                break;
            }
            
            if ($id > 0) {
                echo show($conn, $table, $id);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(["error" => "This resource does not exist"]);
            }

            break;
        }

        //endpoint api/<collection_a>/<collection_b>?include=<value> api/bieren/likes?include=relation_counts
        //endpoint api/<collection_a>/<collection_b> api/bieren/likes
        if (preg_match('#^(?P<collection_a>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<collection_b>[A-Za-z_$][A-Za-z0-9_$]{0,63})?$#', $clean_path, $matches)) 
        {
            $collection_a = $matches['collection_a'];
            $collection_b = $matches['collection_b'];

            $table_left = getTableName($allowed_tables, $collection_a);
            $table_right = getTableName($allowed_tables, $collection_b);
            
            if($table_left && $table_right && isset($_GET['include']) && $_GET['include'] === 'relation_counts')
            {
                 echo allWithRelationCounts($conn, $table_left, $table_right);
            }
            else
            {
                echo allWithRelation($conn, $table_left, $table_right);
            }

            break;
        } 
        //endpoint api/<collection_a>/<collection_b>/top/<id> api/bieren/likes/top/([0-9]+)
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

//         if ($id > 0) {
//             echo addLike($conn, $id);
//         }
//         break;
            
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