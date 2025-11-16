const audio = document.getElementById("miAudio");
    const controlVolumen = document.getElementById("volumen");
    const ultimaGrabacion = document.getElementById("ultimaGrabacion");
    const DURACION = 15;

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

    function cargarUltimaGrabacion(){
    ultimaGrabacion.src = "../php/obtener_audio.php";
    }
    cargarUltimaGrabacion();

    let time = 15;
    let intervalo = null;
    const cont = document.getElementById("seconds");

     function formatoTiempo(segundos) {
        const minutos = Math.floor(segundos / 60);
        const seg = segundos % 60;
        return `${String(minutos).padStart(2, '0')}:${String(seg).padStart(2, '0')}`;
      }

    audio.addEventListener("ended", async () => {
    let time = DURACION;
    cont.textContent = formatoTiempo(time);

    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch(err){
        alert("No se pudo acceder al micrófono: " + err);
        return;
    }

    const mediaRecorder = new MediaRecorder(stream);
    const chunks = [];
    mediaRecorder.ondataavailable = e => chunks.push(e.data);
    mediaRecorder.start();

    const intervalo = setInterval(()=>{
        time--;
        cont.textContent = formatoTiempo(time);
        if(time<=0) clearInterval(intervalo);
    }, 1000);

    setTimeout(() => {
        mediaRecorder.stop();
    }, DURACION*1000);

    mediaRecorder.onstop = async () => {
        const blob = new Blob(chunks, { type:'audio/webm' });
        const formData = new FormData();
        formData.append('audio', blob, 'grabacion_'+Date.now()+'.webm');

        try{
            const res = await fetch('../php/subir_audio.php', { method:'POST', body:formData });
            const text = await res.text();
            console.log(text);

            cargarUltimaGrabacion();
        } catch(err){
            console.error("Error subiendo audio:", err);
        }
    };
});