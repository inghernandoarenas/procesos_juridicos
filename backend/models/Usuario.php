<?php
require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $conn;
    private $table = 'usuarios';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($usuario, $password) {
        $query = "SELECT id, nombre, email, usuario FROM " . $this->table . "
                  WHERE (usuario = :usuario OR email = :usuario)
                  AND password = MD5(:password) AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT id, nombre, email, usuario, telefono, activo, created_at
                  FROM " . $this->table . " WHERE activo = 1 ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT id, nombre, email, usuario, telefono, activo, created_at
                  FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (nombre, email, usuario, password, telefono)
                  VALUES (:nombre, :email, :usuario, MD5(:password), :telefono)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function update($data) {
        // Si viene password la actualizamos, si no la dejamos igual
        if (!empty($data[':password'])) {
            $query = "UPDATE " . $this->table . "
                      SET nombre = :nombre, email = :email, usuario = :usuario,
                          telefono = :telefono, password = MD5(:password)
                      WHERE id = :id";
        } else {
            unset($data[':password']);
            $query = "UPDATE " . $this->table . "
                      SET nombre = :nombre, email = :email, usuario = :usuario,
                          telefono = :telefono
                      WHERE id = :id";
        }
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET activo = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function emailExiste($email, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email AND activo = 1";
        if ($excludeId) $query .= " AND id != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        if ($excludeId) $stmt->bindParam(':id', $excludeId);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }

    public function usuarioExiste($usuario, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table . " WHERE usuario = :usuario AND activo = 1";
        if ($excludeId) $query .= " AND id != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        if ($excludeId) $stmt->bindParam(':id', $excludeId);
        $stmt->execute();
        return $stmt->fetch() !== false;
    }
}
?>