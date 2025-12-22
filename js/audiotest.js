const audio = document.getElementById("miAudio");
const beep = new Audio("../audio/Beep.mp3");
const controlVolumen = document.getElementById("volumen");
const ultimaGrabacion = document.getElementById("ultimaGrabacion");
const cont = document.getElementById("counter");


const PRE_BEEP = 3;  // Timer antes del beep
const GRABACION = 15;  // Grabación de 15s

// Cargar volumen guardado (si existe)
const volumenGuardado = localStorage.getItem("volumenAudio");
if (volumenGuardado !== null) {
    audio.volume = volumenGuardado;
    controlVolumen.value = volumenGuardado;
}

// Ajustar volumen con el slider
controlVolumen.addEventListener("input", () => {
    audio.volume = controlVolumen.value;

    // Guardar volumen en el navegador
    localStorage.setItem("volumenAudio", controlVolumen.value);
});

// Formato mm:ss para los timers
function formatoTiempo(segundos) {
    const minutos = Math.floor(segundos / 60);
    const seg = segundos % 60;
    return `${String(minutos).padStart(2, '0')}:${String(seg).padStart(2, '0')}`;
}


    
    // ----------------------------
    //   CUANDO MI AUDIO TERMINA
    // ----------------------------
    audio.addEventListener("ended", () => {
        
        console.log("miAudio terminó → iniciando timer de 3s");
        
        let tiempo = PRE_BEEP;
        cont.textContent = formatoTiempo(tiempo);
        
        // TIMER DE 3 SEGUNDOS
        const intervaloPreBeep = setInterval(() => {
            tiempo--;
            cont.textContent = formatoTiempo(tiempo);
            console.log("Timer 3s:", tiempo);  // Para verificar el tiempo
            
            if (tiempo <= 0) {
                clearInterval(intervaloPreBeep);  // Detener el intervalo de los 3s
                console.log("Beep suena ahora");  // Verificamos si se ejecuta
                beep.play();   // → SONAR EL BEEP
                iniciarGrabacion(); // → EMPEZAR TIMER + GRABACIÓN DE 15s
            }
        }, 1000);
    });
    
    
    // ----------------------------
    //  GRABACIÓN 15S
    // ----------------------------
    async function iniciarGrabacion() {
        
        console.log("Iniciando grabación de 15s…");
        
        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (err) {
            alert("No se pudo acceder al micrófono: " + err);
            return;
        }
        
        const mediaRecorder = new MediaRecorder(stream);
        const chunks = [];
        
        mediaRecorder.ondataavailable = e => chunks.push(e.data);
        
        mediaRecorder.start();
        
        let tiempo = GRABACION;
        cont.textContent = formatoTiempo(tiempo);
        
        const intervaloGrab = setInterval(() => {
            tiempo--;
            cont.textContent = formatoTiempo(tiempo);
            
            if (tiempo <= 0) {
                clearInterval(intervaloGrab);
                mediaRecorder.stop();
            }
        }, 1000);
        
        mediaRecorder.onstop = async () => {
            const blob = new Blob(chunks, { type: "audio/webm" });
        const formData = new FormData();
        formData.append("audio", blob, "grab" + ".webm");
        
        try {
            const res = await fetch("../php/subir_audio.php", {
                method: "POST",
                body: formData
            });
            console.log(await res.text());
            cargarUltimaGrabacion();
            document.getElementById('popup').classList.add('show');
            document.getElementById('foot').style.display("flex");

        } catch (err) {
            console.error("Error subiendo audio:", err);
        }
    };
}

function cargarUltimaGrabacion() {
    ultimaGrabacion.src = "../php/obtener_audio.php";
}

// Función para eliminar el archivo vía backend
function eliminarArchivo() {
    return axios.post('../php/eliminar.php')
        .then(res => {
            console.log('Respuesta del servidor:', res.data);
        })
        .catch(err => {
            console.error('Error al eliminar el archivo:', err);
        });
}

// Botón "start" que dispara la acción
document.getElementById("start").addEventListener("click", () => {
    const popup = document.getElementById("popup");

    // Animación: ocultar popup
    popup.classList.remove("show");
    popup.classList.add("hide");
    document.getElementById('foot').style.display("none");

    // Esperar animación (300ms) y luego eliminar archivo
    setTimeout(() => {
        eliminarArchivo().then(() => {
            // Recargar la página después de eliminar
            location.reload();
        });
    }, 300); // Coincide con la duración de la animación CSS
});

const arrow = document.getElementById("arrow");
arrow.addEventListener("click", () => {
    window.location.replace("../sections/instructions.html")
});