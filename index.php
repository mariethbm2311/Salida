<?php
session_start();

$usuario_valido = 'admin';
$contrasena_valida = '1234';

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

// --- CRUD LOGIC ---
if (!isset($_SESSION['objetos'])) {
	$_SESSION['objetos'] = [];
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
	$cantidad = intval($_POST['cantidad'] ?? 0);
	$edit_id = $_POST['edit_id'] ?? '';
	if ($nombre !== '' && $cantidad > 0) {
		$objeto = [
      'fecha' => date('Y-m-d H:i:s'),
			'dni' => $dni,
			'nombre' => $nombre,
			'apaterno' => $apaterno,
			'amaterno' => $amaterno,
			'area' => $area,
			'item' => $item,
			'osolicitado' => $osolicitado,
			'descripcion' => $descripcion,
			'cantidad' => $cantidad
		];
		if ($edit_id !== '' && isset($_SESSION['objetos'][$edit_id])) {
			$_SESSION['objetos'][$edit_id] = $objeto;
		} else {
			$_SESSION['objetos'][] = $objeto;
		}
	}
	header('Location: index.php');
	exit();
}

// Eliminar
if (isset($_GET['eliminar'])) {
	$id = $_GET['eliminar'];
	if (isset($_SESSION['objetos'][$id])) {
		unset($_SESSION['objetos'][$id]);
		$_SESSION['objetos'] = array_values($_SESSION['objetos']);
	}
	header('Location: index.php');
	exit();
}

// Editar
$editando = false;
$edit_id = '';
$edit_dni = '';
$edit_nombre = '';
$edit_apaterno = '';
$edit_amaterno = '';
$edit_area = '';
$edit_item = '';
$edit_osolicitado = '';
$edit_descripcion = '';
$edit_cantidad = '';
if (isset($_GET['editar'])) {
	$id = $_GET['editar'];
	if (isset($_SESSION['objetos'][$id])) {
		$editando = true;
		$edit_id = $id;
		$edit_dni = $_SESSION['objetos'][$id]['dni'];
		$edit_nombre = $_SESSION['objetos'][$id]['nombre'];
		$edit_apaterno = $_SESSION['objetos'][$id]['apaterno'];
		$edit_amaterno = $_SESSION['objetos'][$id]['amaterno'];
		$edit_area = $_SESSION['objetos'][$id]['area'];
		$edit_item = $_SESSION['objetos'][$id]['item'];
		$edit_osolicitado = $_SESSION['objetos'][$id]['osolicitado'];
		$edit_descripcion = $_SESSION['objetos'][$id]['descripcion'];
		$edit_cantidad = $_SESSION['objetos'][$id]['cantidad'];
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>SALIDA DE OBJETOS</title>
	<style>
		header {
			position: fixed;
			top: 0;
			left: 0;
			width: 100vw;
			min-width: 100vw;
			margin: 0;
			padding: 0;
			background: #504ABC;
			color: #fff;
			height: 50px;
			display: flex;
			align-items: center;
			font-size: 14px;
			box-sizing: border-box;
			z-index: 1000;
			justify-content: space-between;
		}
		body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
		.container { max-width: 70%; margin: 80px auto 0 auto; padding: 30px; border-radius: 8px; text-align: center; }
		.container-table { max-width: 900px; margin: 20px auto 40px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px #ccc; text-align: center; }
    a { color: #007bff; text-decoration: none; }
		.crud-form { margin: 30px auto 20px auto; background: #eeedf9; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px #ccc; max-width: 100%; }
		.crud-form input, .crud-form textarea, .crud-form select { width: 95%; padding: 8px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; font-size: 15px; }
		.crud-form input[type="submit"] { background: #504ABC; color: #fff; border: none; cursor: pointer; font-weight: bold; }
		.crud-form input[type="submit"]:hover { background: #0056b3; }
		table { width: 95%; margin: 30px auto 0 auto; border-collapse: collapse; background: #fff; }
		th, td { border: 1px solid #bbb; padding: 8px; text-align: center; }
		th { background: #504ABC; color: #fff; }
		tr:nth-child(even) { background: #f4f4f4; }
		.acciones a { margin: 0 5px; color: #504ABC; font-weight: bold; }
		.acciones a.eliminar { color: #d9534f; }
    
	</style>
</head>
<body>
	<header>
		<div style="display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 0 20px;">
			<span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</span>
			<a href="?logout=1" style="color: #fff; text-decoration: none;">Cerrar sesión</a>
		</div>
	</header>
	<div class="container">
		<h2>SALIDA DE OBJETOS</h2>
		<form class="crud-form" method="post">
      <h2>Datos del solicitante</h2>
			<input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id); ?>">
			<div style="display: flex; gap: 10px; margin-bottom: 10px;">
				<input type="dni" name="dni" placeholder="DNI" min="7" value="<?php echo htmlspecialchars($edit_dni); ?>" required style="flex:1;">
				<input type="text" name="nombre" placeholder="Nombre" value="<?php echo htmlspecialchars($edit_nombre); ?>" required style="flex:1;">
				<input type="text" name="apaterno" placeholder="Apellido Paterno" value="<?php echo htmlspecialchars($edit_apaterno); ?>" required style="flex:1;">
			</div>
			<div style="display: flex; gap: 10px; margin-bottom: 10px;">
				<input type="text" name="amaterno" placeholder="Apellido Materno" value="<?php echo htmlspecialchars($edit_amaterno); ?>" required style="flex:1;">
        <select name="area" required style="flex:1;">
					<option value="" disabled <?php echo $edit_area === '' ? 'selected' : ''; ?>>Seleccione un área</option>
					<option value="Administración" <?php echo $edit_area === 'Administración' ? 'selected' : ''; ?>>Administración</option>
					<option value="Sistemas" <?php echo $edit_area === 'Sistemas' ? 'selected' : ''; ?>>Sistemas</option>
					<option value="Recursos Humanos" <?php echo $edit_area === 'Recursos Humanos' ? 'selected' : ''; ?>>Recursos Humanos</option>
				</select>
				<input type="text" name="item" placeholder="Item" value="<?php echo htmlspecialchars($edit_item); ?>" required style="flex:1;">
			</div>
			<div style="display: flex; gap: 10px; margin-bottom: 10px;">
				<select name="osolicitado" required style="flex:1;">
					<option value="" disabled <?php echo $edit_osolicitado === '' ? 'selected' : ''; ?>>Seleccione un objeto</option>
					<option value="Extensión" <?php echo $edit_osolicitado === 'Extensión' ? 'selected' : ''; ?>>Extensión</option>
					<option value="Laptop" <?php echo $edit_osolicitado === 'Laptop' ? 'selected' : ''; ?>>Laptop</option>
					<option value="Proyector" <?php echo $edit_osolicitado === 'Proyector' ? 'selected' : ''; ?>>Proyector</option>
				</select>
				<input name="descripcion" placeholder="Descripción" value="<?php echo htmlspecialchars($edit_descripcion); ?>" style="flex:1;">
				<input type="number" name="cantidad" placeholder="Cantidad" min="1" value="<?php echo htmlspecialchars($edit_cantidad); ?>" required style="flex:1;">
			</div>
			<input type="submit" name="guardar_objeto" value="<?php echo $editando ? 'Actualizar' : 'Agregar'; ?>">
			<?php if ($editando): ?>
				<a href="index.php" style="margin-left:10px; color:#d9534f;">Cancelar</a>
			<?php endif; ?>
		</form>
    </div>
		<div class="container-table">
			<h3>Objetos ingresados</h3>
			<table>
				<tr>
					<th>Item</th>
          <th>Fecha/Hora</th>
					<th>DNI</th>
					<th>Nombre</th>
					<th>Área</th>
					<th>Objetivo Solicitado</th>
					<th>Descripción</th>
					<th>Cantidad</th>
					<th>Acciones</th>
				</tr>
				<?php if (!empty($_SESSION['objetos'])): ?>
					<?php foreach ($_SESSION['objetos'] as $i => $obj): ?>
						<tr>
							<td><?php echo htmlspecialchars($obj['item']); ?></td>
              <td><?php echo isset($obj['fecha']) ? htmlspecialchars($obj['fecha']) : '-'; ?></td>
							<td><?php echo htmlspecialchars($obj['dni']); ?></td>
							<td><?php echo htmlspecialchars($obj['nombre'] . ' ' . $obj['apaterno'] . ' ' . $obj['amaterno']); ?></td>
							<td><?php echo htmlspecialchars($obj['area']); ?></td>
							<td><?php echo htmlspecialchars($obj['osolicitado']); ?></td>
							<td><?php echo htmlspecialchars($obj['descripcion']); ?></td>
							<td><?php echo htmlspecialchars($obj['cantidad']); ?></td>
							<td class="acciones">
								<a href="?editar=<?php echo $i; ?>">Editar</a>
								<a href="?eliminar=<?php echo $i; ?>" class="eliminar" onclick="return confirm('¿Eliminar este objeto?');">Eliminar</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr><td colspan="9">No hay objetos ingresados.</td></tr>
				<?php endif; ?>
			</table>
		</div>
</body>
</html>
<?php else: ?>
	<!DOCTYPE html>
	<html lang="es">
  <head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
      body { font-family: Arial, sans-serif; background: #ffffffff; }
			header {
				position: fixed;
				top: 0;
				left: 0;
				width: 100vw;
				min-width: 100vw;
				margin: 0;
				padding: 0 0 0 20px;
				background: #504ABC;
				color: #fff;
				height: 50px;
				display: flex;
				align-items: center;
				font-size: 12px;
				box-sizing: border-box;
				z-index: 1000;
			}
      h2 { text-align: center; color: #504ABC; font-size: 24px; }
	.login-container { height: 280px; max-width: 360px; margin: 110px auto 0 auto; background: #eeedf9; padding: 30px; border-radius: 49px; box-shadow: 0 2px 8px #ccc; text-align: center; }
      input[type="text"], input[type="password"] { height: 30px; width: 90%; padding: 10px; margin: 20px auto; margin-bottom: 30px; border-radius: 9px; border: 1px solid #eeedf9; display: block; font-size: 15px; }
      input[type="submit"] { height: 40px; width: 60%; padding: 10px; background: #504ABC; color: #fff; border: none; border-radius: 9px; cursor: pointer; font-weight: bold; }
      input[type="submit"]:hover { background: #0056b3; }
      .error { color: red; margin-bottom: 10px; }
    </style>
  </head>
	<body>
    <header>
      <span></span>
    </header>
	<div style="text-align: center; margin-top: 70px;">
      <img src="logo.png" alt="Logo" style="max-width: 220px; margin-bottom: -95px;">
    </div>
		<div class="login-container">
			<h2>INICIO DE SESIÓN</h2>
			<?php if (!empty($error)): ?>
				<div class="error"><?php echo $error; ?></div>
			<?php endif; ?>
			<form method="post">
				<input type="text" id="usuario" name="usuario" required placeholder="USUARIO">
				<input type="password" id="contrasena" name="contrasena" required placeholder="CONTRASEÑA">
				<input type="submit" value="ACCEDER">
			</form>
		</div>
	</body>
	</html>
<?php endif; ?>
