
// ==========================================
// VARIABLES
// ==========================================
let conexion = null;

// ==========================================
// CARGAR ELEMENTOS Y EVENTOS
// ==========================================
window.addEventListener("DOMContentLoaded", function () {
    const correoInput = document.getElementById("txt_correo");
    const passInput = document.getElementById("txt_contraseña");
    const botonLogin = document.getElementById("btn_login");
    const botonLimpiar = document.getElementById("btn_limpiar");

    console.log("Archivo login.js cargado correctamente.");

    // Comprobar que los elementos existan
    if (!correoInput || !passInput || !botonLogin || !botonLimpiar) {
        console.error("Error: No se encontraron los campos o botones de Login.html.");
        return;
    }

    // Evento del botón Iniciar sesión
    botonLogin.addEventListener("click", conexionServidor2);

    // Evento del botón Limpiar
    botonLimpiar.addEventListener("click", function () {
        correoInput.value = "";
        passInput.value = "";
        correoInput.focus();
    });
});

// ==========================================
// INICIAR SESIÓN
// ==========================================
function conexionServidor2() {
    const correo = document.getElementById("txt_correo").value.trim();
    const pass = document.getElementById("txt_contraseña").value;

    // Comprobar campos vacíos
    if (correo === "" || pass === "") {
        Swal.fire({
            title: "Campos vacíos",
            text: "Por favor, escribe tu correo y contraseña.",
            icon: "warning",
            confirmButtonText: "Aceptar"
        });
        return;
    }

    // Comprobar formato del correo
    if (!document.getElementById("txt_correo").checkValidity()) {
        Swal.fire({
            title: "Correo inválido",
            text: "Escribe una dirección de correo válida.",
            icon: "warning",
            confirmButtonText: "Aceptar"
        });
        return;
    }

    // Evitar solicitudes duplicadas
    const botonLogin = document.getElementById("btn_login");
    botonLogin.disabled = true;

    // Crear solicitud AJAX
    conexion = new XMLHttpRequest();

    conexion.open("POST", "./login.php", true);

    conexion.setRequestHeader(
        "Content-Type",
        "application/x-www-form-urlencoded; charset=UTF-8"
    );

    // Procesar respuesta
    conexion.onreadystatechange = function () {
        if (conexion.readyState !== 4) {
            return;
        }

        botonLogin.disabled = false;

        console.log("Código HTTP:", conexion.status);
        console.log("Respuesta de PHP:", conexion.responseText);

       
 // Comprobar respuesta HTTP
if (conexion.status < 200 || conexion.status >= 300) {
    console.error("Error HTTP:", conexion.status);
    console.error("Respuesta del servidor:", conexion.responseText);
    console.error("URL solicitada:", conexion.responseURL);

    Swal.fire({
        title: "Error de conexión",
        html: `
            <p>No se pudo procesar el inicio de sesión.</p>
            <p><b>Estado HTTP:</b> ${conexion.status}</p>
            <p>Revisa la consola del navegador con F12.</p>
        `,
        icon: "error",
        confirmButtonText: "Aceptar"
    });
    return;
}


        // Comprobar respuesta vacía
        const respuesta = conexion.responseText.trim();

        if (respuesta === "") {
            Swal.fire({
                title: "Respuesta vacía",
                text: "El servidor no devolvió información. Revisa login.php.",
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return;
        }

        // Convertir respuesta a JSON
        let dato;

        try {
            dato = JSON.parse(respuesta);
        } catch (error) {
            console.error("Error al interpretar JSON:", error);

            Swal.fire({
                title: "Error del servidor",
                text: "login.php no devolvió JSON válido. Consulta la consola del navegador.",
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return;
        }

        // Comprobar estructura de respuesta
        if (!Array.isArray(dato) || dato.length === 0) {
            Swal.fire({
                title: "Error",
                text: "El servidor devolvió una respuesta inesperada.",
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return;
        }

        // Credenciales incorrectas o error de PHP
        if (dato[0].error) {
            Swal.fire({
                title: "No se pudo iniciar sesión",
                text: dato[0].error,
                icon: "error",
                confirmButtonText: "Aceptar"
            });
            return;
        }

        // Inicio de sesión correcto
        if (dato[0].user) {
            Swal.fire({
                title: "¡Bienvenido!",
                text: "Bienvenido usuario: " + dato[0].user,
                icon: "success",
                confirmButtonText: "Continuar"
            }).then(function () {
                window.location.href = "./inicio.html";
            });

            return;
        }

        // Respuesta inesperada
        Swal.fire({
            title: "Error",
            text: "El servidor no devolvió el usuario esperado.",
            icon: "error",
            confirmButtonText: "Aceptar"
        });
    };

    // Detectar errores de red
    conexion.onerror = function () {
        botonLogin.disabled = false;

        Swal.fire({
            title: "Error de conexión",
            text: "No se pudo conectar con el servidor. Comprueba que AppServ esté funcionando.",
            icon: "error",
            confirmButtonText: "Aceptar"
        });
    };

    // Enviar los datos a PHP
    const datos = new URLSearchParams();
    datos.append("correo_php", correo);
    datos.append("pass_php", pass);

    conexion.send(datos.toString());
}
