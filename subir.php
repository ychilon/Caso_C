<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];
    $nombreOriginal = $archivo['name'];
    $archivoTmp = $archivo['tmp_name'];
    $tamano = $archivo['size'];
    $error = $archivo['error'];

    // Validar errores al subir
    if ($error !== UPLOAD_ERR_OK) {
        die("Error al subir el archivo.");
    }

    // Validar extensiones permitidas
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'zip', 'rar'];
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        die("Extensión no permitida.");
    }

    // Validar tipo MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivoTmp);
    finfo_close($finfo);

    $mimesPermitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'text/plain',
        'application/zip',
        'application/x-rar-compressed'
    ];

    if (!in_array($mime, $mimesPermitidos)) {
        die("Tipo MIME no permitido.");
    }

    // Obtener parámetro 'nombre' desde la URL (si existe)
    $nombreURL = isset($_GET['nombre']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['nombre']) : 'archivo';

    // Crear nombre nuevo con fecha y hora
    $fechaHora = date("Ymd_His"); // Formato seguro
    $nuevoNombre = $nombreURL . '_' . $fechaHora . '.' . $extension;

    // Ruta destino
    $carpetaDestino = 'uploads/';
    if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0755, true);
    }

    $rutaFinal = $carpetaDestino . $nuevoNombre;

    // Mover el archivo
    if (move_uploaded_file($archivoTmp, $rutaFinal)) {
        echo "Archivo subido exitosamente como: $nuevoNombre";
    } else {
        echo "Error al guardar el archivo.";
    }
} else {
    echo "No se ha enviado ningún archivo.";
}
?>