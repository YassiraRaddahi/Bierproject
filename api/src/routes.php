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
        // endpoint api/<collection>     ---- e.g. api/beers
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/?$#', $clean_path, $matches)) {
            $collection = $matches['collection'];
            $table = getTableName($allowed_tables, $collection);

            if (!$table) {
                break;
            }

            echo all($conn, $table);

            break;
        }

        // endpoint api/<collection>/<id>   ---- e.g. api/beers/10
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<id>\d+)/?$#', $clean_path, $matches)) {
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

        //endpoint api/<collection_a>/<collection_b>                                  ---- e.g. api/beers/likes
        //endpoint api/<collection_a>/<collection_b>?include=<value>                  ---- e.g. api/beers/likes?include=relation_counts
        //endpoint api/<collection_a>/<collection_b>?search=<value>                   ---- e.g. api/beers/likes?search=keyword
        //endpoint api/<collection_a>/<collection_b>?include=<value>&search=<value>   ---- e.g. api/beers/likes?include=relation_counts&search=Abondance
        
        if (preg_match('#^(?P<collection_a>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<collection_b>[A-Za-z_$][A-Za-z0-9_$]{0,63})?$#', $clean_path, $matches)) {
            $collection_a = $matches['collection_a'];
            $collection_b = $matches['collection_b'];

            $left_table = getTableName($allowed_tables, $collection_a);
            $right_table = getTableName($allowed_tables, $collection_b);

            if(!$left_table || !$right_table)
            {
                break;
            }

            if (isset($_GET['include']) && $_GET['include'] === 'relation_counts' && isset($_GET['search'] ) && !empty(trim($_GET['search']))) {
                $search_on_name = trim($_GET['search']);
                echo findWithRelationCounts($conn, $left_table, $right_table, $search_on_name);
                
            }
            else if(isset($_GET['include']) && $_GET['include'] === 'relation_counts') {
                echo allWithRelationCounts($conn, $left_table, $right_table);
            }
            else if (isset($_GET['search'] ) && !empty(trim($_GET['search']))) {
                $search_on_name = trim($_GET['search']);
                echo findWithRelation($conn, $left_table, $right_table, $search_on_name);
            }
            else {
                echo allWithRelation($conn, $left_table, $right_table);
            }

            break;
        }

        //endpoint api/<collection_a>/<collection_b>/<id>                 ---- e.g. api/beers/likes/10
        //endpoint api/<collection_a>/<collection_b>/<id>?include=<value> ---- e.g. api/beers/likes/10?include=relation_counts
        if (preg_match('#^(?P<collection_a>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<collection_b>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<id>\d+)/?$#', $clean_path, $matches)) {
            $collection_a = $matches['collection_a'];
            $collection_b = $matches['collection_b'];
            $id = (int) $matches['id'];

            $left_table = getTableName($allowed_tables, $collection_a);
            $right_table = getTableName($allowed_tables, $collection_b);

            if(!$left_table || !$right_table)
            {
                break;
            }


            if ($id > 0) 
            {
                if (isset($_GET['include']) && $_GET['include'] === 'relation_counts') 
                {
                    echo showWithRelationCounts($conn, $left_table, $right_table, $id);
                } 
                else 
                {
                    echo showWithRelation($conn, $left_table, $right_table, $id);
                }

            } 
            else 
            {
                http_response_code(404); // Not Found
                echo json_encode(["error" => "This resource does not exist"]);
            }

            break;
        }
        
        //endpoint api/<collection_a>/<collection_b>/top/<topx>         ---- e.g. api/bieren/likes/top/([0-9]+)
        if (preg_match('#^(?P<collection_a>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<collection_b>[A-Za-z_$][A-Za-z0-9_$]{0,63})/top/(?P<topx>\d+)/?$#', $clean_path, $matches)) {
            $collection_a = $matches['collection_a'];
            $collection_b = $matches['collection_b'];
            $topx = (int) $matches['topx'];

            $left_table = getTableName($allowed_tables, $collection_a);
            $right_table = getTableName($allowed_tables, $collection_b);

            if(!$left_table || !$right_table)
            {
                break;
            }

            if ($topx > 0) 
            {
                echo showTopxWithRelationCounts($conn, $left_table, $right_table, $topx);
            } 
            else 
            {
                http_response_code(400); // Bad Request
                echo json_encode(["error" => "Invalid integer for the top parameter. It should be a positive integer."]);
            }

            break;
        }

        http_response_code(404); // Not Found
        echo json_encode(["message" => "No valid endpoint is inserted"]);
        break;
    case "POST":
        // endpoint api/<collection>                                  ---- e.g. api/likes
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/?$#', $clean_path, $matches)) {
            $collection = $matches['collection'];
            $table = getTableName($allowed_tables, $collection);

            if(!$table)
            {
                break;
            }

            echo create($conn, $table);
            break;
        }

        http_response_code(404); // Not Found
        echo json_encode(["message" => "No valid endpoint is inserted"]);
        break;
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
    case "DELETE":
        if (preg_match('#^(?P<collection>[A-Za-z_$][A-Za-z0-9_$]{0,63})/(?P<id>\d+)/?$#', $clean_path, $matches)) {
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
                echo json_encode(["error" => "This resource does not exist"]);
            }

            break;
        }

        http_response_code(400); // Bad Request
        echo json_encode(["error" => "deletion failed", "message" => "You need to specify which resource you want to remove"]);
        break;
    default:
        http_response_code(405); // Method Not Allowed 
        echo json_encode(["error" => "This HTTP method is not allowed"]);
        break;
}
