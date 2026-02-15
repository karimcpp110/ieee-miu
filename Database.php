<?php
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
            die("Database Connection failed: " . $e->getMessage());
        }
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function initialize()
    {
        // Handled manually via setup scripts to avoid proxy crashes
    }
}
?>