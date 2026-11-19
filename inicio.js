
let usuario;
let conexion;


addEvent(window,'load',cargar, false);

function addEvent(ele,eve,fun,cap){
 	if(window.attachEvent)
		ele.addAttachEvent('on'+eve,fun);
	else
		ele.addEventListener(eve,fun,cap);
}

// FUNCIONES
function cargar() {
    conexionServidor2();
}


// AJAX
function xmlhttprequest() {
    return new XMLHttpRequest();
}

function conexionServidor2() {
    //alert("conexionServidor2");
        conexion = xmlhttprequest();
        conexion.onreadystatechange = esperaRespuesta2;
        conexion.open("POST", "sesion.php", true);
        conexion.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        conexion.send();
}

function esperaRespuesta2() {
    if (conexion.readyState == 4) {
        //alert(conexion.responseText);
       
        usuario = conexion.responseText;
        document.getElementById('txt_user').value = usuario;

    }
}

