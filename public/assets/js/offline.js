/**
 * CARDOXIS - OFFLINE RF
 */

const style = document.createElement("style");

style.textContent = `
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Segoe UI,Tahoma,sans-serif;
}

.offline-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    backdrop-filter:blur(8px);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.offline-overlay.active{
    display:flex;
}

.offline-box{
    background:#fff;
    border-radius:20px;
    padding:40px;
    width:90%;
    max-width:420px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.3);
}

.offline-title{
    font-size:24px;
    color:#2c3e50;
    margin-bottom:15px;
}

.offline-text{
    color:#7f8c8d;
    line-height:1.6;
    margin-bottom:25px;
}

.retry-button{
    background:#3498db;
    color:#fff;
    border:none;
    border-radius:50px;
    padding:14px 30px;
    font-size:16px;
    cursor:pointer;
}

.retry-button:hover{
    background:#2980b9;
}
`;

document.head.appendChild(style);

// =======================
// HTML
// =======================
const overlay = document.createElement("div");
overlay.className = "offline-overlay";
overlay.innerHTML = `
<div class="offline-box">
    <h2 class="offline-title">Sem ligação à Internet</h2>
    <p class="offline-text">
        Parece que não estás ligado à Internet.
        Verifica a tua ligação e tenta novamente.
    </p>
    <button class="retry-button" id="retryButton">
        Tentar novamente
    </button>
</div>
`;

document.body.appendChild(overlay);

// =======================
// Funções
// =======================
const retryButton = document.getElementById("retryButton");

function verificarInternet() {
    overlay.classList.toggle("active", !navigator.onLine);
}

retryButton.addEventListener("click", () => {
    retryButton.disabled = true;
    retryButton.textContent = "A verificar...";

    setTimeout(() => {
        verificarInternet();
        retryButton.disabled = false;
        retryButton.textContent = "Tentar novamente";
    }, 1500);
});

window.addEventListener("online", verificarInternet);
window.addEventListener("offline", verificarInternet);

verificarInternet();