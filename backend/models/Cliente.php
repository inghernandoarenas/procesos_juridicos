<?php
require_once __DIR__ . '/../config/database.php';

class Cliente {
    private $conn;
    private $table = 'clientes';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (nombre, apellido, email, telefono, direccion) VALUES (:nombre, :apellido, :email, :telefono, :direccion)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " SET nombre=:nombre, apellido=:apellido, email=:email, telefono=:telefono, direccion=:direccion WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAllPaginated($inicio, $por_pagina) {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC LIMIT :inicio, :por_pagina";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
        $stmt->bindParam(':por_pagina', $por_pagina, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener total de registros
        $total = $this->conn->query("SELECT COUNT(*) as total FROM " . $this->table)->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'data' => $data,
            'total' => $total,
            'pagina' => ($inicio / $por_pagina) + 1,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina)
        ];
    }   
}
?>