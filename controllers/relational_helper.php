<?php
function obtenerRelacionesForaneas(PDO $pdo, string $base, string $tabla): array {
    $sql = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla AND REFERENCED_TABLE_NAME IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['base' => $base, 'tabla' => $tabla]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
