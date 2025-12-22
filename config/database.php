<?php
// =====================================================
// DATABASE CONNECTION - PDO
// =====================================================

$host = 'localhost';
$dbname = 'ghkrylxe_hopubnd';
$username = 'ghkrylxe_hopubnd';
$password = ']xi8G$0]0x#ij&p%';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
