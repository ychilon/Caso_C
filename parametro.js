// Obtener el nombre de carpeta único desde la URL, o generarlo si no existe
const urlActual = window.location.href;
var parametros = new URLSearchParams(window.location.search);
var carpetaNombre = parametros.get("nombre");
if (!carpetaNombre) {
  carpetaNombre = generarCadenaAleatoria();
  const urlConParametro = urlActual.includes("?")
    ? `${urlActual}&nombre=${carpetaNombre}`
    : `${urlActual}?nombre=${carpetaNombre}`;
  // Redirigir a la nueva URL con 'nombre' para crear la carpeta única
  window.location.href = urlConParametro;
}

// Función para generar una cadena aleatoria de 3 caracteres (letras y números)
function generarCadenaAleatoria() {
  const caracteres = "abcdefghijklmnopqrstuvwxyz0123456789";
  let cadenaAleatoria = "";
  for (let i = 0; i < 3; i++) {
    const indice = Math.floor(Math.random() * caracteres.length);
    cadenaAleatoria += caracteres.charAt(indice);
  }
  return cadenaAleatoria;
}

// Cuando el DOM esté listo, asignar eventos para la carga de archivos
window.addEventListener("DOMContentLoaded", function () {
  const fileInput = document.getElementById("archivo");
  const dropArea = document.getElementById("drop-area");
  const mensajeDiv = document.getElementById("mensaje");
  const progressContainer = document.getElementById("progress-container");
  const progressBar = document.getElementById("progress-bar");

  // Evento al seleccionar archivos mediante el input
  fileInput.addEventListener("change", function () {
    if (fileInput.files.length > 0) {
      uploadFiles(fileInput.files);
    }
  });

  // Eventos para arrastrar y soltar archivos en el área de carga
  dropArea.addEventListener("dragover", function (e) {
    e.preventDefault();
    dropArea.classList.add("dragover");
  });
  dropArea.addEventListener("dragleave", function () {
    dropArea.classList.remove("dragover");
  });
  dropArea.addEventListener("drop", function (e) {
    e.preventDefault();
    dropArea.classList.remove("dragover");
    if (e.dataTransfer.files.length > 0) {
      uploadFiles(e.dataTransfer.files);
    }
  });

  // Función que realiza la subida AJAX de una lista de archivos
  function uploadFiles(files) {
    // Limpiar mensaje anterior
    mensajeDiv.style.display = "none";
    mensajeDiv.textContent = "";

    // Preparar datos de formulario para enviar
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
      formData.append("archivos[]", files[i]);
    }

    // Mostrar barra de progreso
    progressBar.style.width = "0%";
    progressContainer.style.display = "block";

    // Configurar la solicitud AJAX
    const xhr = new XMLHttpRequest();
    xhr.open(
      "POST",
      "index.php?nombre=" + encodeURIComponent(carpetaNombre),
      true
    );

    // Actualizar barra de progreso durante la subida
    xhr.upload.onprogress = function (e) {
      if (e.lengthComputable) {
        const porcentaje = (e.loaded / e.total) * 100;
        progressBar.style.width = porcentaje + "%";
      }
    };

    // Al completar la carga, actualizar la interfaz
    xhr.onload = function () {
      // Asegurar barra llena al 100%
      progressBar.style.width = "100%";
      if (xhr.status === 200) {
        // Parsear la respuesta HTML para extraer la lista de archivos y mensaje actualizados
        const parser = new DOMParser();
        const docRespuesta = parser.parseFromString(
          xhr.responseText,
          "text/html"
        );
        const nuevaLista = docRespuesta.getElementById("file-list");
        const nuevoMensaje = docRespuesta.getElementById("mensaje");
        // Reemplazar la lista de archivos mostrada por la nueva lista
        if (nuevaLista) {
          document.getElementById("file-list").innerHTML = nuevaLista.innerHTML;
        }
        // Actualizar el mensaje mostrado al usuario
        if (nuevoMensaje) {
          mensajeDiv.innerHTML = nuevoMensaje.innerHTML;
          mensajeDiv.className = nuevoMensaje.className;
          mensajeDiv.style.display = nuevoMensaje.style.display;
        }
      } else {
        // Si el servidor respondió con error (status != 200)
        mensajeDiv.textContent = "Error al subir los archivos.";
        mensajeDiv.className = "error";
        mensajeDiv.style.display = "block";
      }
      // Ocultar la barra de progreso tras una breve pausa
      setTimeout(function () {
        progressContainer.style.display = "none";
        progressBar.style.width = "0%";
      }, 500);
    };

    // Manejar errores de red en la petición
    xhr.onerror = function () {
      progressContainer.style.display = "none";
      progressBar.style.width = "0%";
      mensajeDiv.textContent =
        "Error de conexión. No se pudieron subir los archivos.";
      mensajeDiv.className = "error";
      mensajeDiv.style.display = "block";
    };

    // Enviar la petición AJAX con los archivos
    xhr.send(formData);
  }
});
