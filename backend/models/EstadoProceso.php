<?php
require_once __DIR__ . '/../config/database.php';

class EstadoProceso {
    private $conn;
    private $table = 'estados_proceso';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1 ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>