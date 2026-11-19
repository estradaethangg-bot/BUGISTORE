let precio;
let precioI;
let totalNeto;

let refPrecio;
let refPrecioI;

addEvent(window,'load',cargar, false);

function addEvent(ele,eve,fun,cap){
 	if(window.attachEvent)
		ele.addAttachEvent('on'+eve,fun);
	else
		ele.addEventListener(eve,fun,cap);
}

// FUNCIONES
function cargar() {
    addEvent(document.getElementById('btn_calcular'),'click',calculaTotal,false);

    addEvent(document.getElementById('txt_pagoI'),'keypress',muestraNumeros,false);

}
function calculaTotal(){
    if(document.getElementById('txt_precio').value == "" || document.getElementById('txt_pagoI').value == ""){
        Swal.fire({
            title: 'Error!',
            text: 'Campos vacíos',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }else{
        precio = document.getElementById('txt_precio').value;
        precioI = document.getElementById('txt_pagoI').value;
    
        totalNeto = precio - precioI;
    
        document.getElementById("txt_totalNeto").value=totalNeto;
    }
}
function muestraNumeros(){
    if((event.keyCode>=48 && event.keyCode<=57)){
        event.returnValue=true;
    }else{
        event.returnValue=false;
    }
}