<?php

function getTableColumns($conn, $table)
{
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
    ");
    $stmt->bindValue(':table', $table);
    $stmt->execute();

    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
}

function convertToSQLColumns($conn, $table, $columns)
{
    $columns_in_table = getTableColumns($conn, $table);

    $sql = [];

    if ($columns === "*" || empty($columns)) {

        foreach ($columns_in_table as $col) {
            $sql[] = "`$table`.`$col` AS `{$table}_{$col}`";
        }
        return implode(", ", $sql);
    }

    return implode(", ", array_map(function ($col) use ($table, $columns_in_table) {
        if (in_array($col, $columns_in_table)) {
            return "`$table`.`$col` AS `{$table}_{$col}`";
        } else {
            throw new PDOException("Column '$col' does not exist in table '$table'.");
        }
    }, $columns));
}
