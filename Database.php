<?php
class MockStatement
{
    public function fetch(...$args)
    {
        return false;
    }
    public function fetchAll(...$args)
    {
        return [];
    }
    public function fetchColumn(...$args)
    {
        return false;
    }
    public function rowCount()
    {
        return 0;
    }
}

class Database
{
    private $pdo;
    private $manual_host = 'sql100.infinityfree.com';

    public function __construct()
    {
        $u = 'if0_41134868';
        $p = 'QQEdikbTFOdqmo';
        $d = 'if0_41134868_miu';
        try {
            $this->pdo = new PDO("mysql:host={$this->manual_host};dbname=$d;charset=utf8mb4", $u, $p);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Check if it's a "headers already sent" situation, but we'll try to output regardless
            die("Database Connection failed: " . $e->getMessage());
        }
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Global Handling for Missing Tables/Columns (SQLSTATE 42S02, 42S22)
            if ($e->getCode() == '42S02' || $e->getCode() == '42S22') {
                return new MockStatement();
            }
            throw $e;
        }
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function initialize()
    {
        // Handled via external tool to avoid execution timeouts
    }
}
?>