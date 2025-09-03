<?php
session_start();

$host = 'localhost';
$db   = 'salidaobjetos';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (isset($_GET['objeto'])) {
    $osolicitado = $_GET['objeto'];
    
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM objetos WHERE osolicitado = ?");
    $stmt->bind_param("s", $osolicitado);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'] + 1;
    $stmt->close();
    
    
    $prefijos = [
        'Extensión' => 'EXT',
        'Laptop' => 'LAP',
        'Proyector' => 'PRO',
        'Mouse' => 'MOU',
        'Teclado' => 'TEC',
        'Monitor' => 'MON'
    ];
    
    $prefijo = $prefijos[$osolicitado] ?? 'OBJ';
    
    
    $numero = str_pad($total, 3, '0', STR_PAD_LEFT);
    
    echo $prefijo . '-' . $numero;
} else {
    echo 'SELECCIONE-OBJETO';
}
?>