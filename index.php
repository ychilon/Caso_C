<?php
date_default_timezone_set('America/Lima'); // Zona horaria GMT-5
$carpetaNombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$carpetaRuta = "./descarga/" . $carpetaNombre;

if (!file_exists($carpetaRuta)) {
    mkdir($carpetaRuta, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = '';
    $extensionesProhibidas = ['php','xml','bat','iso','war','ear','jar','zip','rar','7z','tar','gz',
                              'xhtml','json','svg','html','htm','ps1','exe','cmd','com','sh','py',
                              'cgi','pl','jsp','jspx','asp','aspx','php3','php4','phtml'];

    if (isset($_FILES['archivos']) && is_array($_FILES['archivos']['name'])) {
        $extensionesBloqueadas = [];
        $archivosSubidos = 0;

        foreach ($_FILES['archivos']['name'] as $key => $nombreOriginal) {
            $nombreVisible = str_replace(' ', '_', $nombreOriginal);
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $rutaTemp   = $_FILES['archivos']['tmp_name'][$key];

            // Evitar doble extensión como archivo.php.jpg
            $partesNombre = explode('.', $nombreOriginal);
            if (count($partesNombre) > 2) {
                $intermedias = array_slice($partesNombre, 0, -1);
                foreach ($intermedias as $intermedia) {
                    if (in_array(strtolower($intermedia), $extensionesProhibidas)) {
                        $mensaje .= "El archivo $nombreVisible contiene una extensión peligrosa oculta. ";
                        continue 2; // Saltar al siguiente archivo
                    }
                }
            }

            // Verificación de extensiones prohibidas
            if (in_array($extension, $extensionesProhibidas)) {
                $extensionesBloqueadas[] = $extension;
                continue;
            }

            // Verificación si es imagen válida
            if (in_array($extension, ['jpg','jpeg','png','gif','bmp','webp'])) {
                if (!@getimagesize($rutaTemp)) {
                    $mensaje .= "El archivo $nombreVisible no es una imagen válida. ";
                    continue;
                }
            }

            $timestamp = date('Ymd_His');
            $nombreServidor = $timestamp . '_' . $nombreVisible;
            $rutaDestino = $carpetaRuta . '/' . $nombreServidor;

            if (move_uploaded_file($rutaTemp, $rutaDestino)) {
                $archivosSubidos++;
            }
        }

        if ($archivosSubidos > 0) {
            $mensaje .= "Archivos subidos con éxito. ";
        }

        if (count($extensionesBloqueadas) > 0) {
            $unicas = array_unique($extensionesBloqueadas);
            foreach ($unicas as $ext) {
                $mensaje .= "No se permite subir archivos con extensión .$ext. ";
            }
        }

        if ($archivosSubidos === 0 && count($extensionesBloqueadas) === 0 && empty($mensaje)) {
            $mensaje = "No se subió ningún archivo.";
        }
    }

    if (isset($_POST['eliminarArchivo'])) {
        $archivoAEliminar = $_POST['eliminarArchivo'];
        $archivoRutaAEliminar = $carpetaRuta . '/' . $archivoAEliminar;
        if (file_exists($archivoRutaAEliminar)) {
            if (unlink($archivoRutaAEliminar)) {
                $mensaje = "Archivo '$archivoAEliminar' eliminado con éxito.";
            } else {
                $mensaje = "Error al eliminar el archivo.";
            }
        } else {
            $mensaje = "El archivo '$archivoAEliminar' no existe.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Compartir archivos</title>
    <script src="parametro.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="estilo.css" />
</head>
<body>
    <h1>Compartir archivos <sup class="beta">BETA</sup></h1>
    <div class="content">
        <h3>Sube tus archivos y comparte este enlace temporal: 
            <span>aress.site/<?php echo $carpetaNombre; ?></span>
        </h3>

        <div class="container">
            <div class="drop-area" id="drop-area">
                <form id="form" method="POST" enctype="multipart/form-data">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" style="fill:#0730c5;">
                        <path d="M13 19v-4h3l-4-5-4 5h3v4z"></path>
                        <path d="M7 19h2v-2H7c-1.654 0-3-1.346-3-3 
                                 0-1.404 1.199-2.756 2.673-3.015l.581-.102.192-.558
                                 C8.149 8.274 9.895 7 12 7c2.757 0 5 2.243 5 5v1h1c1.103 
                                 0 2 .897 2 2s-.897 2-2 2h-3v2h3c2.206 0 4-1.794 4-4
                                 a4.01 4.01 0 0 0-3.056-3.888C18.507 7.67 15.56 5 12 5
                                 9.244 5 6.85 6.611 5.757 9.15 3.609 9.792 2 11.82 2 14
                                 c0 2.757 2.243 5 5 5z"></path>
                    </svg>
                    <br />
                    <input type="file" class="file-input" name="archivos[]" id="archivo" multiple />
                    <label>Arrastra tus archivos aquí<br />o</label>
                    <p><b>Abre el explorador</b></p>
                </form>
            </div>

            <div class="container2">
                <div id="mensaje" 
                     <?php 
                         if (empty($mensaje)) { 
                             echo 'style="display:none;"'; 
                         } else { 
                             echo (stripos($mensaje,'no se permite') !== false || 
                                   stripos($mensaje,'imagen válida') !== false ||
                                   stripos($mensaje,'peligrosa oculta') !== false ||
                                   stripos($mensaje,'Error') !== false) 
                                  ? 'class="error"' 
                                  : 'class="success"'; 
                         } ?>>
                    <?php if (!empty($mensaje)) echo $mensaje; ?>
                </div>

                <div id="progress-container" style="display:none;">
                    <div id="progress-bar"></div>
                </div>

                <div id="file-list" class="pila">
                    <?php
                    $files = array_diff(scandir($carpetaRuta), ['.', '..']);
                    if (count($files) > 0) {
                        echo "<h3 style='margin-bottom:10px;'>Archivos Subidos:</h3>";
                        foreach ($files as $file) {
                            $nombreVisible = preg_replace('/^\d{8}_\d{6}_/', '', $file);
                            $ext = strtolower(pathinfo($nombreVisible, PATHINFO_EXTENSION));
                            $iconClass = 'fa-file';

                            if (in_array($ext, ['png','jpg','jpeg','gif','bmp','svg','webp'])) {
                                $iconClass = 'fa-file-image';
                            } elseif ($ext === 'pdf') {
                                $iconClass = 'fa-file-pdf';
                            } elseif (in_array($ext, ['doc','docx'])) {
                                $iconClass = 'fa-file-word';
                            } elseif (in_array($ext, ['xls','xlsx'])) {
                                $iconClass = 'fa-file-excel';
                            } elseif (in_array($ext, ['ppt','pptx'])) {
                                $iconClass = 'fa-file-powerpoint';
                            } elseif (in_array($ext, ['zip','rar','7z','tar','gz'])) {
                                $iconClass = 'fa-file-archive';
                            }

                            echo "<div class='archivos_subidos'>"
                               . "<div><a href='$carpetaRuta/$file' download='$nombreVisible'><i class='fas $iconClass'></i> $nombreVisible</a></div>"
                               . "<div><form method='POST' style='display:inline;'>"
                               . "<input type='hidden' name='eliminarArchivo' value='$file'/>"
                               . "<button type='submit' class='btn_delete'>"
                               . "<svg xmlns='http://www.w3.org/2000/svg' class='icon icon-tabler icon-tabler-trash' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='none' stroke-linecap='round' stroke-linejoin='round'>"
                               . "<path stroke='none' d='M0 0h24v24H0z' fill='none'/>"
                               . "<path d='M4 7l16 0' />"
                               . "<path d='M10 11l0 6' />"
                               . "<path d='M14 11l0 6' />"
                               . "<path d='M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12' />"
                               . "<path d='M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3' />"
                               . "</svg>"
                               . "</button></form></div></div>";
                        }
                    } else {
                        echo "No se han subido archivos.";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
