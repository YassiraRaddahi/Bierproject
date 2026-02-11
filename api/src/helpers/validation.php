<?php

function getTableName($allowed_tables, $collection)
{

    if ($collection !== 'likes') {

        // When the collection in the url is written singularly
        if(in_array(strtolower(string: $collection), $allowed_tables))
        {
            http_response_code(404); // Not Found
            echo json_encode(["error" => "This collection does not exist"]);
            return false;
        }

        $table_name = trim($collection, 's');
    } else {
        $table_name = $collection;
    }

    if (!in_array(strtolower($table_name), $allowed_tables)) {
        http_response_code(404); // Not Found
        echo json_encode(["error" => "This collection does not exist"]);
        return false;
    } else {
        return $table_name;
    }
}
