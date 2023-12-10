<?php

namespace MyApp;

use PDO;
class Database
{
    public $query_count = 0;
    protected $connection;
    protected $query;
    protected $show_errors = true;
    protected $query_closed = true;

    public function __construct($dbhost = DB_HOST, $dbuser = DB_USER, $dbpass = DB_PASS, $dbname = DB_NAME)
    {
        $dsn = DB_TYPE === 'mysql' ? "mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4" : "pgsql:host=$dbhost;dbname=$dbname";

        try {
            $this->connection = new PDO($dsn, $dbuser, $dbpass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Failed to connect to the database: ' . $e->getMessage());
        }
    }


    public function dataQuery($query, array $values = []): array
    {
        $limit = !empty($_GET["limit"]) ? (int)$_GET["limit"] : PAGINATION_LIMIT;
        $limit = ($limit > 1000) ? 1000 : max($limit, 20);

        $page = !empty($_GET["page"]) ? (int)$_GET["page"] : 1;

        $start = ($page == 0 ? 0 : ($page - 1) * $limit);
        $page_num = ($page == 0 ? 1 : $page);

        $sql = "$query OFFSET $start LIMIT $limit";

        if (empty($values)) {

            $content = $this->query($sql)->fetchAll();
            $totalCount = $this->query($query)->numRows();
        } else {

            $content = $this->query($sql, ...$values)->fetchAll();
            $totalCount = $this->query($query, ...$values)->numRows();
        }

        return [
            "items" => $content,
            "item_count" => $totalCount,
            "page_number" => $page_num,
            "item_limit" => $limit
        ];
    }

    public function fetchAll($query = null, array $values = [], $callback = null): array
    {
        if ($query !== null) {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($values);
        } else {
            $stmt = $this->query;
        }

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($callback !== null && is_callable($callback)) {
                $value = $callback($row);
                if ($value === 'break') {
                    break;
                }
            } else {
                $result[] = $row;
            }
        }

        return $result;
    }

    private function replacePlaceholders($query)
    {
        if (DB_TYPE === 'pgsql') {
            // Replace backticks around identifiers with double quotes for PostgreSQL
            $query = preg_replace('/`([^`]*?)`/', '"$1"', $query);
        } elseif (DB_TYPE === 'mysql') {
            // Replace double quotes around identifiers with backticks for MySQL
            $query = preg_replace('/"([^"]*?)"/', '`$1`', $query);

            // Replace single quotes around identifiers with backticks for MySQL
            $query = preg_replace('/\'([^\']*?)\'/', '`$1`', $query);
        }

        return $query;
    }


    public function query($query, ...$values)
    {
        if (!$this->query_closed) {
            $this->query->closeCursor();
        }

        $query = $this->replacePlaceholders($query);

        try {
            $this->query = $this->connection->prepare($query);

            // Bind parameters with data types, including BLOBs
            foreach ($values as $key => $value) {
                if (is_array($value)) {

                    foreach ($value as $key2 => $item) {
                        $dataType = $this->getDataType($item);

                        if ($dataType === PDO::PARAM_LOB) {
                            $this->query->bindParam($key + 1, $item, $dataType);
                        } else {
                            $this->query->bindValue($key + 1, $item, $dataType);
                        }
                    }

                } else {
                    $dataType = $this->getDataType($value);

                    if ($dataType === PDO::PARAM_LOB) {
                        $this->query->bindParam($key + 1, $value, $dataType);
                    } else {
                        $this->query->bindValue($key + 1, $value, $dataType);
                    }
                }
            }

            $this->query->execute();

            $this->query_count++;
            $this->query_closed = false;
        } catch (PDOException $e) {
            $this->error('Error executing query: ' . $e->getMessage());
        }

        return $this;
    }

    // Helper function to determine the data type, including BLOBs
    private function getDataType($value): int
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        if (is_resource($value)) {
            return PDO::PARAM_LOB;
        }

        return PDO::PARAM_STR;
    }

    public function numRows($query = null, array $values = []): int
    {
        if ($query !== null) {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($values);
        } else {
            $stmt = $this->query;
        }

        return $stmt->rowCount();
    }

    public function fetchArray($query = null, array $values = [])
    {
        if ($query !== null) {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($values);
        } else {
            $stmt = $this->query;
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function prepare($query)
    {
        return $this->connection->prepare($query);
    }

    public function close()
    {
        $this->connection = null;
        $this->query_closed = true;
    }

    public function affectedRows()
    {
        return $this->query->rowCount();
    }

    public function lastInsertID()
    {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function rollback()
    {
        $this->connection->rollBack();
    }

    private function error($error)
    {
        if ($this->show_errors) {
            throw new \RuntimeException($error);
        }
    }


    public function __destruct()
    {
        $this->close();
    }
}
