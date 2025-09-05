<?php
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'salidaobjetos';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión a la base de datos']));
}

if (!isset($_POST['dni'])) {
    echo json_encode(['error' => 'No se recibió el DNI']);
    exit;
}

$dni = $conn->real_escape_string($_POST['dni']);

// Buscar en la tabla personas
$sql = "SELECT nombre, apaterno, amaterno FROM personas WHERE dni='$dni' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'dni' => $dni,
        'nombres' => $row['nombre'],
        'apellidoPaterno' => $row['apaterno'],
        'apellidoMaterno' => $row['amaterno']
    ]);
} else {
    echo json_encode([
        'dni' => $dni,
        'nombres' => '',
        'apellidoPaterno' => '',
        'apellidoMaterno' => ''
    ]);
}

$conn->close();
?>
