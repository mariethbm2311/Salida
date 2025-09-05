<?php
session_start();

$usuario_valido = 'ADMIN';
$contrasena_valida = '1234';

$host = 'localhost';
$db   = 'salidaobjetos';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (isset($_POST['usuario']) && isset($_POST['contrasena'])) {
    if ($_POST['usuario'] === $usuario_valido && $_POST['contrasena'] === $contrasena_valida) {
        $_SESSION['usuario'] = $usuario_valido;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['usuario'])):


if (!isset($_SESSION['objetos'])) {
    $_SESSION['objetos'] = [];
}

// Cargar datos desde la base de datos al iniciar
if (empty($_SESSION['objetos'])) {

// Carga solo los últimos 200 objetos en sesión para no sobrecargar
$result = $conn->query("SELECT * FROM objetos ORDER BY fecha DESC LIMIT 200");

    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $_SESSION['objetos'][] = [
                'id' => $row['id'],
                'fecha' => $row['fecha'],
                'dni' => $row['dni'],
                'nombre' => $row['nombre'],
                'apaterno' => $row['apaterno'],
                'amaterno' => $row['amaterno'],
                'area' => $row['area'],
                'item' => $row['item'],
                'osolicitado' => $row['osolicitado'],
                'descripcion' => $row['descripcion'],
                'cantidad' => $row['cantidad'],
                'estado' => $row['estado']
            ];
        }
    }
}

// Agregar o actualizar
if (isset($_POST['guardar_objeto'])) {
    $dni = trim($_POST['dni'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apaterno = trim($_POST['apaterno'] ?? '');
    $amaterno = trim($_POST['amaterno'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $item = trim($_POST['item'] ?? '');
    $osolicitado = trim($_POST['osolicitado'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $estado = 'Prestado';
    $edit_index = $_POST['edit_index'] ?? '';
    
    // Obtener la fecha actual
    $fecha_actual = date('Y-m-d H:i:s');

    // Validaciones
    $errores = [];
    if (strlen($dni) < 7 || strlen($dni) > 8) $errores[] = 'El DNI debe tener entre 7 y 8 dígitos';
    if (strlen($nombre) < 2 || strlen($nombre) > 50) $errores[] = 'El nombre debe tener entre 2 y 50 caracteres';
    if (strlen($apaterno) < 2 || strlen($apaterno) > 50) $errores[] = 'El apellido paterno debe tener entre 2 y 50 caracteres';
    if (strlen($amaterno) > 50) $errores[] = 'El apellido materno no puede exceder los 50 caracteres';
    if (empty($area)) $errores[] = 'Debe seleccionar un área';
    if (empty($osolicitado)) $errores[] = 'Debe seleccionar un objeto solicitado';
    if ($cantidad < 1 || $cantidad > 100) $errores[] = 'La cantidad debe ser entre 1 y 100';

    if (empty($errores)) {
        if ($edit_index !== '') {
            // Actualizar en base de datos 
            $id_real = $_SESSION['objetos'][$edit_index]['id'];
            $stmt = $conn->prepare("UPDATE objetos SET dni=?, nombre=?, apaterno=?, amaterno=?, area=?, item=?, osolicitado=?, descripcion=?, cantidad=?, fecha=? WHERE id=?");
            if ($stmt === false) {
                die("Error en la preparación de la consulta: " . $conn->error);
            }
            $stmt->bind_param("ssssssssisi", $dni, $nombre, $apaterno, $amaterno, $area, $item, $osolicitado, $descripcion, $cantidad, $fecha_actual, $id_real);
            
            if (!$stmt->execute()) {
                die("Error al actualizar el objeto: " . $stmt->error);
            }
            $stmt->close();

            // Actualizar también en sesión
            if (isset($_SESSION['objetos'][$edit_index])) {
                $_SESSION['objetos'][$edit_index] = [
                    'id' => $id_real,
                    'fecha' => $fecha_actual, 
                    'dni' => $dni,
                    'nombre' => $nombre,
                    'apaterno' => $apaterno,
                    'amaterno' => $amaterno,
                    'area' => $area,
                    'item' => $item,
                    'osolicitado' => $osolicitado,
                    'descripcion' => $descripcion,
                    'cantidad' => $cantidad,
                    'estado' => $_SESSION['objetos'][$edit_index]['estado']
                ];
            }
        } else {
            // Insertar en base de datos
            $stmt = $conn->prepare("INSERT INTO objetos (dni, nombre, apaterno, amaterno, area, item, osolicitado, descripcion, cantidad, estado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                die("Error en la preparación de la consulta: " . $conn->error);
            }
            $stmt->bind_param("ssssssssiss", $dni, $nombre, $apaterno, $amaterno, $area, $item, $osolicitado, $descripcion, $cantidad, $estado, $fecha_actual);
            
            if (!$stmt->execute()) {
                die("Error al insertar el objeto: " . $stmt->error);
            }
            $id_insertado = $stmt->insert_id;
            $stmt->close();

            // Guardar también en sesión
            $objeto = [
                'id' => $id_insertado,
                'fecha' => $fecha_actual,
                'dni' => $dni,
                'nombre' => $nombre,
                'apaterno' => $apaterno,
                'amaterno' => $amaterno,
                'area' => $area,
                'item' => $item,
                'osolicitado' => $osolicitado,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'estado' => $estado
            ];
            $_SESSION['objetos'][] = $objeto;
        }
        header('Location: index.php');
        exit();
    }
}
// Cambiar estado
if (isset($_GET['cambiar_estado'])) {
    $index = $_GET['cambiar_estado'];
    
    if (isset($_SESSION['objetos'][$index])) {
        $nuevo_estado = ($_SESSION['objetos'][$index]['estado'] === 'Prestado') ? 'Devuelto' : 'Prestado';
        $id_real = $_SESSION['objetos'][$index]['id'];
        
        // Actualizar en la base de datos
        $stmt = $conn->prepare("UPDATE objetos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $id_real);
        
        if ($stmt->execute()) {
            // Actualizar en sesión si esta correcto
            $_SESSION['objetos'][$index]['estado'] = $nuevo_estado;
        }
        
        $stmt->close();
    }
    
    header('Location: index.php');
    exit();
}

// Eliminar
if (isset($_GET['eliminar'])) {
    $index = $_GET['eliminar'];

    if (isset($_SESSION['objetos'][$index]) && $_SESSION['objetos'][$index]['estado'] === 'Devuelto') {
        $id_real = $_SESSION['objetos'][$index]['id'];
        
        // Borrar en la base de datos
        $stmt = $conn->prepare("DELETE FROM objetos WHERE id = ?");
        $stmt->bind_param("i", $id_real);
        
        if ($stmt->execute()) {
            // Borrar en sesión solo si esta correcto
            unset($_SESSION['objetos'][$index]);
            $_SESSION['objetos'] = array_values($_SESSION['objetos']);
        }
        
        $stmt->close();
    }else{

	   echo "El objeto no está disponible para eliminar.";
    }

    header('Location: index.php');
    exit();
}

// Editar 
$editando = false;
$edit_index = '';
$edit_dni = $edit_nombre = $edit_apaterno = $edit_amaterno = '';
$edit_area = $edit_item = $edit_osolicitado = $edit_descripcion = '';
$edit_cantidad = '1'; 

if (isset($_GET['editar'])) {
    $index = $_GET['editar'];
    
    if (isset($_SESSION['objetos'][$index])) {
        $editando = true;
        $edit_index = $index;
        $edit_dni = $_SESSION['objetos'][$index]['dni'];
        $edit_nombre = $_SESSION['objetos'][$index]['nombre'];
        $edit_apaterno = $_SESSION['objetos'][$index]['apaterno'];
        $edit_amaterno = $_SESSION['objetos'][$index]['amaterno'];
        $edit_area = $_SESSION['objetos'][$index]['area'];
        $edit_item = $_SESSION['objetos'][$index]['item'];
        $edit_osolicitado = $_SESSION['objetos'][$index]['osolicitado'];
        $edit_descripcion = $_SESSION['objetos'][$index]['descripcion'];
        $edit_cantidad = $_SESSION['objetos'][$index]['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	 <meta charset="UTF-8">
    <title>Sistema de Control de Objetos</title>
    <link rel="icon" type="image/png" href="escudo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
		:root {
			--color-primary: #504ABC;
			--color-secondary: #6c757d;
			--color-success: #28a745;
			--color-danger: #dc3545;
			--color-warning: #ffc107;
			--color-info: #17a2b8;
			--color-light: #f8f9fa;
			--color-dark: #343a40;
		}
		
		header {
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			min-width: 100vw;
			margin: 0;
			padding: 0;
			background: var(--color-primary);
			color: #fff;
			height: 60px;
			display: flex;
			align-items: center;
			font-size: 16px;
			box-sizing: border-box;
			z-index: 1000;
			justify-content: space-between;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		
		body { 
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
			background: #f8f9fa; 
			margin: 0; 
			padding-top: 60px;
		}
		
		.container { 
			max-width: 1350px; 
			margin: 20px auto; 
			padding: 20px; 
		}
		
		.section-title {
			color: var(--color-primary);
			border-bottom: 2px solid var(--color-primary);
			padding-bottom: 10px;
			margin-bottom: 20px;
			font-size: 24px;
			font-weight: 600;
		}
		
		.card {
			background: white;
			border-radius: 10px;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			padding: 25px;
			margin-bottom: 30px;
		}
		
		.crud-form input, .crud-form textarea, .crud-form select { 
			width: 100%; 
			padding: 12px; 
			margin: 10px 0; 
			border-radius: 5px; 
			border: 1px solid #ddd; 
			font-size: 16px;
			box-sizing: border-box;
		}
		
		.crud-form input:focus, .crud-form textarea:focus, .crud-form select:focus {
			border-color: var(--color-primary);
			outline: none;
			box-shadow: 0 0 0 3px rgba(80, 74, 188, 0.1);
		}
		
		.form-row {
			display: flex;
			gap: 15px;
			margin-bottom: 15px;
		}
		
		.form-group {
			flex: 1;
		}
		
		.form-label {
			display: block;
			margin-bottom: 5px;
			font-weight: 500;
			color: var(--color-dark);
		}
		
		.btn {
			padding: 12px 20px;
			border: none;
			border-radius: 5px;
			cursor: pointer;
			font-size: 16px;
			font-weight: 500;
			transition: all 0.3s;
		}
		
		.btn-primary {
			background: var(--color-primary);
			color: white;
		}
		
		.btn-primary:hover {
			background: #3f39a0;
			transform: translateY(-2px);
		}
		
		.btn-danger {
			background: var(--color-danger);
			color: white;
		}
		
		.btn-danger:hover {
			background: #bd2130;
		}
		
		.btn-outline {
			background: transparent;
			border: 1px solid var(--color-secondary);
			color: var(--color-secondary);
		}
		
		.btn-outline:hover {
			background: var(--color-secondary);
			color: white;
		}
		
		.table-container {
			overflow-y: auto;
			overflow-x: auto;
			max-height: 600px;
			border-radius: 10px;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
		}
		
		table {
			width: 100%;
			border-collapse: collapse;
			background: white;
		}
		
		th, td {
			padding: 12px 15px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		
		th {
			background: var(--color-primary);
			color: white;
			font-weight: 600;
			position: sticky;
			top: 0;
		}
		
		tr:nth-child(even) {
			background: #f8f9fa;
		}
		
		tr:hover {
			background: #e9daf7ff;
		}
		
		.acciones {
			display: flex;
			gap: 10px;
		}
		
		.btn-icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 36px;
			height: 36px;
			border-radius: 50%;
			color: white;
			text-decoration: none;
			transition: all 0.3s;
		}
		
		.btn-edit {
			background: var(--color-info);
		}
		
		.btn-edit:hover {
			background: #138496;
			transform: scale(1.1);
		}
		
		.btn-status {
			background: var(--color-warning);
		}
		
		.btn-status:hover {
			background: #e0a800;
			transform: scale(1.1);
		}
		
		.btn-delete {
			background: var(--color-danger);
		}
		.btn-lock {
			background: var(--color-danger);
			
		}
		.btn-delete:hover {
			background: #bd2130;
			transform: scale(1.1);
		}
		.btn-lock:hover {
			cursor:not-allowed;
			transform: scale(1.1);
		}
		
		.estado-prestado {
			background-color: #fff3cd;
			color: #856404;
			padding: 5px 10px;
			border-radius: 20px;
			font-weight: 500;
			font-size: 14px;
			display: inline-block;
		}
		
		.estado-devuelto {
			background-color: #d4edda;
			color: #155724;
			padding: 5px 10px;
			border-radius: 20px;
			font-weight: 500;
			font-size: 14px;
			display: inline-block;
		}
		
		.error {
			background: #f8d7da;
			color: #721c24;
			padding: 10px;
			border-radius: 5px;
			margin-bottom: 20px;
			border-left: 4px solid #f5c6cb;
		}
		
		.error div {
			margin: 5px 0;
		}
		
		.login-container {
			max-width: 400px;
			margin: 100px auto;
			background: white;
			padding: 30px;
			border-radius: 10px;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			text-align: center;
		}
		
		.logo {
			max-width: 180px;
			margin-bottom: 20px;
		}
		
		@media (max-width: 768px) {
			.form-row {
				flex-direction: column;
				gap: 10px;
			}
			
			.acciones {
				flex-direction: column;
				gap: 5px;
			}
			
			.container {
				padding: 15px;
			}
		}
	</style>
	<!-- Para las peticiones AJAX -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>
	<a href="consulta_dni.php"></a>
	<header>
		<div style="display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 0 20px;">
			<span style="font-size: 20px; font-weight: bold;"> <i class="fas fa-box"></i> Sistema de Control de Objetos</span>
				<div>
    				<span style="margin-right: 15px;">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</span>
    					<a href="?logout=1" style="color: #fff; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px;">
        			<i class="fas fa-sign-out-alt"></i> 
    			</a>
				</div>
			</div>
	</header>
	
	<div class="container">
		<h1 class="section-title">Registro de Salida de Objetos</h1>
		
		<div class="card">
			<h2 style="color: var(--color-primary); margin-top: 0;">
				<i class="fas fa-user-circle"></i> Datos del Solicitante
			</h2>
			
			<form class="crud-form" method="post">
				<?php if (!empty($errores)): ?>
					<div class="error">
						<?php foreach ($errores as $error): ?>
							<div><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				
				<input type="hidden" name="edit_index" value="<?php echo htmlspecialchars($edit_index); ?>">
				
				<div class="form-row">
					<div class="form-group">
						<label class="form-label">DNI (8 dígitos)</label>
						<input type="text" name="dni" id="id-dni" placeholder="Ej: 71234567" 
							value="<?php echo htmlspecialchars($edit_dni); ?>" required 
							maxlength="8" title="Ingrese un DNI válido de 8 dígitos" pattern="\d{8}">
					</div>
					
					<div class="form-group">
						<label class="form-label">Nombres</label>
						<input type="text" name="nombre" id="nombres" placeholder="Nombres completos" readonly
							required>
					</div>
				</div>
				
				<div class="form-row">
					<div class="form-group">
						<label class="form-label">Apellido Paterno</label>
						<input type="text" name="apaterno" id="apaterno" placeholder="Apellido paterno" readonly
							required>
					</div>
					
					<div class="form-group">
						<label class="form-label">Apellido Materno</label>
						<input type="text" name="amaterno" id="amaterno" placeholder="Apellido materno" readonly
							required>
					</div>
					
					<div class="form-group">
						<label class="form-label">Área</label>
						<select name="area" required>
							<option value="" disabled <?php echo $edit_area === '' ? 'selected' : ''; ?>>Seleccione un área</option>
							<option value="Administración" <?php echo $edit_area === 'Administración' ? 'selected' : ''; ?>>Administración</option>
							<option value="Sistemas" <?php echo $edit_area === 'Sistemas' ? 'selected' : ''; ?>>Sistemas</option>
							<option value="Recursos Humanos" <?php echo $edit_area === 'Recursos Humanos' ? 'selected' : ''; ?>>Recursos Humanos</option>
							<option value="Contabilidad" <?php echo $edit_area === 'Contabilidad' ? 'selected' : ''; ?>>Contabilidad</option>
							<option value="Logística" <?php echo $edit_area === 'Logística' ? 'selected' : ''; ?>>Logística</option>
						</select>
					</div>
				</div>
				
				<h2 style="color: var(--color-primary); margin-top: 30px;">
					<i class="fas fa-box"></i> Datos del Objeto
				</h2>
				
				<div class="form-row">
					<div class="form-group">
						<label class="form-label">Objeto Solicitado</label>
						<select name="osolicitado" id="objetoSelect" required onchange="">
							<option value="" disabled <?php echo $edit_osolicitado === '' ? 'selected' : ''; ?>>Seleccione un objeto</option>
							<option value="Extensión" <?php echo $edit_osolicitado === 'Extensión' ? 'selected' : ''; ?>>Extensión</option>
							<option value="Laptop" <?php echo $edit_osolicitado === 'Laptop' ? 'selected' : ''; ?>>Laptop</option>
							<option value="Proyector" <?php echo $edit_osolicitado === 'Proyector' ? 'selected' : ''; ?>>Proyector</option>
							<option value="Pantalla Interactiva" <?php echo $edit_osolicitado === 'Pantalla Interactiva' ? 'selected' : ''; ?>>Pantalla Interactiva</option>
							<option value="Cable HDMI" <?php echo $edit_osolicitado === 'Cable HDMI' ? 'selected' : ''; ?>>Cable HDMI</option>
						</select>
					</div>
					
					<div class="form-group">
						<label class="form-label">Código</label>
						<input type="text" name="item" placeholder="Código de objeto" 
							value="<?php echo htmlspecialchars($edit_item); ?>" required
							maxlength="20">
					</div>
				</div>
				
				<div class="form-row">
					<div class="form-group">
						<label class="form-label">Descripción (opcional)</label>
						<input name="descripcion" placeholder="Detalles adicionales del objeto (Máximo 200 caracteres)" 
							value="<?php echo htmlspecialchars($edit_descripcion); ?>" 
							maxlength="200">
					</div>
					
					<div class="form-group-small">
						<label class="form-label">Cantidad</label>
						<input type="number" name="cantidad" min="1" max="100" 
							value="<?php echo htmlspecialchars($edit_cantidad); ?>" required>
					</div>
				</div>
				
				<div style="margin-top: 20px; text-align: center;">
					<button type="submit" name="guardar_objeto" class="btn btn-primary">
						<i class="fas fa-<?php echo $editando ? 'sync' : 'save'; ?>"></i> 
						<?php echo $editando ? 'Actualizar' : 'Registrar'; ?>
					</button>
					
					<?php if ($editando): ?>
						<a href="index.php" class="btn btn-outline" style="margin-left: 10px;">
							<i class="fas fa-times"></i> Cancelar
						</a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		
		<div class="card">
			<h2 class="section-title" style="margin-top: 0;">
				<i class="fas fa-list"></i> Objetos Registrados
			</h2>
			<form method="get" style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px; font-family: Arial, sans-serif;">
				<label for="limit" style="font-weight: bold; color: #333;">Mostrar filas:</label>
				<select name="limit" id="limit" onchange="this.form.submit()" 
					style="padding: 5px 10px; border-radius: 6px; border: 1px solid #ccc; background: #f9f9f9; font-size: 14px; cursor: pointer;">
					<?php 
					$limits = [5, 10, 50, 100, 200];
					$selected_limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; 
					foreach ($limits as $l) {
						echo "<option value='$l'" . ($l == $selected_limit ? ' selected' : '') . ">$l</option>";
					}
					?>
				</select>
			</form>
			<div class="table-container">
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Fecha/Hora</th>
							<th>DNI</th>
							<th>Solicitante</th>
							<th>Área</th>
							<th>Código</th>
							<th>Objeto</th>
							<th>Descripción</th>
							<th>Cantidad</th>
							<th>Estado</th>
							<th>Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php if (!empty($_SESSION['objetos'])): ?>	
							<?php 
								usort($_SESSION['objetos'], function($a, $b) {
									return strtotime($b['fecha']) - strtotime($a['fecha']);
								});

							// Limites de tablaa
							$objetos_limitados = array_slice($_SESSION['objetos'], 0, $selected_limit);
							?>
							<?php foreach ($objetos_limitados as $i => $obj): ?>
								<tr>
									<td><?php echo htmlspecialchars($obj['id']); ?></td>
									<td><?php echo isset($obj['fecha']) ? htmlspecialchars($obj['fecha']) : '-'; ?></td>
									<td><?php echo htmlspecialchars($obj['dni']); ?></td>
									<td><?php echo htmlspecialchars($obj['nombre'] . ' ' . $obj['apaterno'] . ' ' . $obj['amaterno']); ?></td>
									<td><?php echo htmlspecialchars($obj['area']); ?></td>
									<td><strong><?php echo htmlspecialchars($obj['item']); ?></strong></td>
									<td><?php echo htmlspecialchars($obj['osolicitado']); ?></td>
									<td><?php echo htmlspecialchars($obj['descripcion']); ?></td>
									<td><?php echo htmlspecialchars($obj['cantidad']); ?></td>
									<td>
										<span class="<?php echo $obj['estado'] === 'Prestado' ? 'estado-prestado' : 'estado-devuelto'; ?>">
											<?php echo htmlspecialchars($obj['estado']); ?>
										</span>
									</td>
									<td>
										<div class="acciones">
											<a href="?editar=<?php echo $i; ?>" class="btn-icon btn-edit" title="Editar">
												<i class="fas fa-edit"></i>
											</a>
											<a href="?cambiar_estado=<?php echo $i; ?>" class="btn-icon btn-status" title="<?php echo $obj['estado'] === 'Prestado' ? 'Marcar como Devuelto' : 'Marcar como Prestado'; ?>">
												<i class="fas fa-<?php echo $obj['estado'] === 'Prestado' ? 'check-circle' : 'undo'; ?>"></i>
											</a>
											<?php if ($obj['estado'] === 'Devuelto'): ?>
											<a href="?eliminar=<?php echo $i; ?>" class="btn-icon btn-delete" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este registro?');">
												<i class="fas fa-trash"></i>
        									</a>
    									<?php else: ?>
											<a class="btn-icon btn-lock" title="Eliminar" onclick="return alert('El objeto no ha sido devuelto');">
												<i class="fas fa-lock"></i>
        									</a>
										<?php endif; ?>
				
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="11" style="text-align: center; padding: 20px; color: var(--color-secondary);">
									<i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
									No hay objetos registrados
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<script>

// busca  al escribir 8 dígitos
document.getElementById('id-dni').addEventListener('input', function() {
    const dni = this.value.trim();
    if (dni.length === 8) {
        consultarDNI(dni);
    }
});

function consultarDNI(dni) {
    if(dni.length != 8 || isNaN(dni)){
        alert('Por favor ingrese un DNI válido de 8 dígitos.');
        return;
    }
    $.ajax({
        url: 'consulta_dni.php',
        type: 'POST',
        data: { dni : dni },
        dataType: 'json',
        success: function(response) {
            if(response && response.dni==dni){
                $('#nombres').val(response.nombres);
                $('#apaterno').val(response.apellidoPaterno);
                $('#amaterno').val(response.apellidoMaterno);
            }
        },
        error: function() {
            alert('Error al consultar el DNI');
        }
    });
}

// Solo números para DNI
function soloNumeros(e) {
	let key = e.key;
	if (!/^[0-9]$/.test(key) && !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(key)) {
		e.preventDefault();
	}
}

document.addEventListener('DOMContentLoaded', function() {
	const dni = document.querySelector('input[name="dni"]');
	if (dni) dni.addEventListener('keydown', soloNumeros);
	
	<?php if ($editando): ?>
	document.getElementById('nombres').value = '<?php echo addslashes($edit_nombre); ?>';
	document.getElementById('apaterno').value = '<?php echo addslashes($edit_apaterno); ?>';
	document.getElementById('amaterno').value = '<?php echo addslashes($edit_amaterno); ?>';
	<?php endif; ?>
});


	
	</script>
</body>
</html>
<?php else: ?>
	<!DOCTYPE html>
	<html lang="es">
	<head>
		<meta charset="UTF-8">
		<title>Inicio de Sesión - Sistema de Objetos</title>
		<link rel="icon" type="image/png" href="escudo.png">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
		<style>
			body { 
				font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
				background: #504ABC;
				height: 100vh;
				display: flex;
				align-items: center;
				justify-content: center;
				margin: 0;
			}
			
			.login-container { 
				width: 100%;
				max-width: 400px; 
				background: white; 
				padding: 40px;
				border-radius: 15px;
				box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
				text-align: center;
			}
			
			.logo {
				max-width: 150px;
				margin-bottom: 20px;
			}
			
			h2 {
				color: #504ABC;
				margin-bottom: 30px;
				font-weight: bold
			}
			
			.input-group {
				margin-bottom: 20px;
				text-align: left;
			}
			
			.input-group label {
				display: block;
				margin-bottom: 5px;
				font-weight: 500;
				color: #495057;
			}
			
			.input-group input {
				width: 100%;
				padding: 12px 15px;
				border: 1px solid #ddd;
				border-radius: 5px;
				font-size: 16px;
				box-sizing: border-box;
				transition: border-color 0.3s;
			}
			
			.input-group input:focus {
				border-color: #504ABC;
				outline: none;
				box-shadow: 0 0 0 3px rgba(80, 74, 188, 0.1);
			}
			
			.btn-login {
				width: 60%;
				padding: 12px;
				background: #504ABC;
				color: white;
				border: none;
				border-radius: 5px;
				font-size: 16px;
				font-weight: 500;
				cursor: pointer;
				transition: background 0.3s;
			}
			
			.btn-login:hover {
				background: #3f39a0;
			}
			
			.error {
				background: #f8d7da;
				color: #721c24;
				padding: 10px;
				border-radius: 5px;
				margin-bottom: 20px;
				border-left: 4px solid #f5c6cb;
			}
		</style>
	</head>
	<body>

	<!-- Para las peticiones AJAX -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script>

    // Solo letras y espacios para nombres y apellidos
    function soloLetras(e) {
        let key = e.key;
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]$/.test(key) &&
            !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(key)) {
            e.preventDefault();
        }
    }

    // Solo números para DNI
    function soloNumeros(e) {
        let key = e.key;
        if (!/^[0-9]$/.test(key) &&
            !['Backspace','Tab','ArrowLeft','ArrowRight','Delete'].includes(key)) {
            e.preventDefault();
        }
    }

    function consultarDNI(dni) {
        if(dni.length !== 8 || isNaN(dni)){
            alert('Por favor ingrese un DNI válido de 8 dígitos.');
            return;
        }

        $.ajax({
            url: 'consulta_dni.php',
            type: 'POST',
            data: { dni: dni },
            dataType: 'json',
            success: function(response) {
                if(response && response.dni === dni){
                    document.getElementById('nombres').value = response.nombres;
                    document.getElementById('apaterno').value = response.apellidoPaterno;
                    document.getElementById('amaterno').value = response.apellidoMaterno;
                } else if(response.error){
                    alert(response.error);
                    document.getElementById('nombres').value = '';
                    document.getElementById('apaterno').value = '';
                    document.getElementById('amaterno').value = '';
                }
            },
            error: function() {
                alert('Error al consultar el DNI');
                document.getElementById('nombres').value = '';
                document.getElementById('apaterno').value = '';
                document.getElementById('amaterno').value = '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {

        // Validación de letras en nombre
        const usuario = document.querySelector('input[name="usuario"]');
        if(usuario) usuario.addEventListener('keydown', soloLetras);

        // Validación y consulta de DNI
        const dni = document.getElementById('id-dni');
        if(dni) {
            dni.addEventListener('keydown', soloNumeros);
            dni.addEventListener('input', function() {
                const val = this.value.trim();
                if(val.length === 8) consultarDNI(val);
            });
        }

        // Si estamos editando, llenar valores
        <?php if ($editando): ?>
        document.getElementById('nombres').value = '<?php echo addslashes($edit_nombre); ?>';
        document.getElementById('apaterno').value = '<?php echo addslashes($edit_apaterno); ?>';
        document.getElementById('amaterno').value = '<?php echo addslashes($edit_amaterno); ?>';
        <?php endif; ?>
    		});


	</script>
    </script>
</body>
</html>
<?php endif; ?>