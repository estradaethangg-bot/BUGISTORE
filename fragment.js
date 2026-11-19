let dato;
let lista_modelos; 
let caja_precio;         
     addEvent(window,'load',cargar, false);
     function addEvent(ele,eve,fun,cap){
         if(window.attachEvent)
          addAttachEvent('on'+eve,fun);
        else
          ele.addEventListener(eve,fun,cap);
     }
      function cargar(){      
       //addEvent(document.getElementById("lista"),'onchange',mostrarPrecioModelo,false);  
       lista_modelos= document.getElementById("slt_modelos"); 
       caja_precio= document.getElementById("txt_precio");
       addEvent(lista_modelos,'change',conexionServidor2,false);
       conexionServidor1();    
     }
    function conexionServidor2(){
       alert("cs2");
       //caja_precio.value =lista_modelos.value;
       conexion = xmlhttprequest();
             conexion.onreadystatechange = esperaRespuesta2;
             conexion.open("POST","obtener_precio.php",true);   
       conexion.setRequestHeader("Content-type","application/x-www-form-urlencoded");  
              conexion.send("clave="+lista_modelos.value);

    }
    function conexionServidor1(){
             conexion = xmlhttprequest();
             conexion.onreadystatechange = esperaRespuesta1;
             conexion.open("POST","obtener_datos.php",true);   
      conexion.setRequestHeader("Content-type","application/x-www-form-urlencoded");  
             conexion.send();
     }
   function esperaRespuesta1(){ 
       if(conexion.readyState == 4){
          alert(conexion.responseText);
             //dato = eval('('+conexion.responseText+')');
          dato = eval(conexion.responseText);
          agregarOpciones();     
       }
   }  

   function esperaRespuesta2(){
      if(conexion.readyState == 4){
          alert(conexion.responseText);
             dato = eval('('+conexion.responseText+')');
          caja_precio.value=dato.precio;
                  
       } 
   }   
   function agregarOpciones(){
      //lista_modelos
      alert("agregar");
      let fragment1=document.createDocumentFragment();

      for(let i=1;i<=dato[0]["tam"];i++){
         let opcion1=document.createElement('OPTION');
         opcion1.setAttribute('value',dato[0]["clave"+i]);
         opcion1.textContent=dato[0]["clave"+i]+ " "+ dato[0]["modelo"+i] ;
         fragment1.appendChild(opcion1);
      }

      lista_modelos.appendChild(fragment1);
  }
   function xmlhttprequest(){
          return new XMLHttpRequest();
   }
  
  