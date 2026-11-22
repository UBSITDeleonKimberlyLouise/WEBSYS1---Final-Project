<?php
chdir(dirname(__DIR__));

// Small class containing common database actions used by the module pages.
class ELTNHS {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($sql, $values = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }

    public function getOne($sql, $values = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetch();
    }

    public function save($sql, $values = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }
}
?>
