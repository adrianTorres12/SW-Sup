const audio = document.getElementById("miAudio");
const controlVolumen = document.getElementById("volumen");

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

// Cuando termina el audio, esperar 3s y volver a reproducir
audio.addEventListener("ended", () => {
    setTimeout(() => {
    audio.currentTime = 0;
    audio.play();
    }, 2000); 
});

// Manejo del autoplay bloqueado por el navegador
document.addEventListener("click", () => {
    if (audio.paused) {
    audio.play().catch(err => console.log("Autoplay bloqueado:", err));
    }
});

document.getElementById("arrow").addEventListener("click", (e) => {
    e.preventDefault(); // evita que intente seguir el href vacío
    window.location.replace("../sections/audiotest.html");
});

