
'use strict';

window.addEventListener('DOMContentLoaded', () => {

    // Obtener los elementos del formulario
    const $ = id => document.getElementById(id);

    const campos = {
        nombre: $('txt_nombre'),
        ap1: $('txt_ap1'),
        ap2: $('txt_ap2'),
        edad: $('txt_edad'),
        user: $('txt_user'),
        correo: $('txt_correo'),
        pass: $('txt_contraseña')
    };

    const boton = $('btn_registrate');
    const limpiar = $('btn_limpiar');

    // Comprobar que existan todos los elementos
    if (
        !boton ||
        !limpiar ||
        Object.values(campos).some(campo => !campo)
    ) {
        console.error('No se encontraron todos los campos del registro.');
        return;
    }

    // BOTÓN PARA LIMPIAR LOS CAMPOS
    limpiar.addEventListener('click', () => {

        Object.values(campos).forEach(campo => {
            campo.value = '';
        });

        document.querySelectorAll('input[name="sexo"]').forEach(radio => {
            radio.checked = false;
        });

        campos.nombre.focus();
    });

    // BOTÓN PARA REGISTRAR AL USUARIO
    boton.addEventListener('click', async () => {

        // Obtener los valores escritos
        const nombre = campos.nombre.value.trim();
        const ap1 = campos.ap1.value.trim();
        const ap2 = campos.ap2.value.trim();
        const edad = campos.edad.value.trim();
        const user = campos.user.value.trim();
        const correo = campos.correo.value.trim();
        const pass = campos.pass.value;

        // Obtener la opción de sexo seleccionada
        const seleccionado = document.querySelector(
            'input[name="sexo"]:checked'
        );

        const genero = seleccionado
            ? ({
                '1': 'Masculino',
                '2': 'Femenino',
                '3': 'Indefinido'
            }[seleccionado.value] || '')
            : '';

        // VALIDAR CAMPOS OBLIGATORIOS
        if (
            !nombre ||
            !ap1 ||
            !ap2 ||
            !edad ||
            !user ||
            !correo ||
            !pass ||
            !genero
        ) {
            await Swal.fire({
                title: 'Campos incompletos',
                text: 'Completa todos los campos y selecciona una opción de sexo.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });

            return;
        }

        // VALIDAR CORREO ELECTRÓNICO
        if (!campos.correo.checkValidity()) {
            await Swal.fire({
                title: 'Correo inválido',
                text: 'Escribe un correo electrónico válido.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });

            return;
        }

        // VALIDAR EDAD
        if (
            !Number.isInteger(Number(edad)) ||
            Number(edad) < 1 ||
            Number(edad) > 120
        ) {
            await Swal.fire({
                title: 'Edad inválida',
                text: 'La edad debe estar entre 1 y 120 años.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });

            return;
        }

        // VALIDAR CONTRASEÑA
        if (pass.length < 6) {
            await Swal.fire({
                title: 'Contraseña corta',
                text: 'Usa al menos 6 caracteres.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });

            return;
        }

        // Desactivar el botón mientras se registra
        boton.disabled = true;

        const textoOriginal = boton.value;
        boton.value = 'Registrando...';

        try {

            // Preparar los datos para enviarlos a PHP
            const datos = new URLSearchParams({
                nombre_php: nombre,
                ap1_php: ap1,
                ap2_php: ap2,
                edad_php: edad,
                genero_php: genero,
                user_php: user,
                correo_php: correo,
                pass_php: pass
            });

            // Enviar los datos al servidor
            const respuesta = await fetch('./registro.php', {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: datos.toString(),
                credentials: 'same-origin',
                cache: 'no-store'
            });

            // Leer la respuesta UNA sola vez
            const texto = (await respuesta.text()).trim();

            console.log('Estado HTTP:', respuesta.status);
            console.log('Respuesta del servidor:', texto);

            // COMPROBAR SI PHP DEVOLVIÓ UN ERROR
            if (texto.startsWith('ERROR:')) {
                throw new Error(
                    texto.replace(/^ERROR:\s*/, '') ||
                    'No se pudo registrar la cuenta.'
                );
            }

            // COMPROBAR LA RESPUESTA HTTP
            if (!respuesta.ok) {
                throw new Error(
                    'El servidor respondió con el error HTTP ' +
                    respuesta.status + '.'
                );
            }

            // COMPROBAR QUE EL REGISTRO SE GUARDÓ
            if (
                !texto.includes(
                    'El registro se guardó correctamente'
                )
            ) {
                throw new Error(
                    'El servidor no confirmó el registro. Respuesta: ' +
                    (texto || 'vacía')
                );
            }

            // MOSTRAR ALERTA VERDE DE ÉXITO
            await Swal.fire({
                title: '¡Registro exitoso!',
                text: 'Tu cuenta se creó correctamente. Ahora puedes iniciar sesión.',
                icon: 'success',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            // AL PULSAR OK, REDIRIGIR AL INICIO DE SESIÓN
            window.location.replace('./Login.html');

        } catch (error) {

            // MOSTRAR EL ERROR SI EL REGISTRO FALLÓ
            console.error('Error durante el registro:', error);

            await Swal.fire({
                title: 'No se pudo registrar',
                text: error.message ||
                    'Comprueba la conexión con la base de datos.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });

        } finally {

            // Restaurar el botón
            boton.disabled = false;
            boton.value = textoOriginal;
        }
    });

});
