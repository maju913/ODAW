const campoNome = document.getElementById('nome');
const saudacao  = document.getElementById('saudacao');

function atualizarSaudacao() {
    const nome = campoNome.value.trim();
    saudacao.textContent = nome ? `Olá, ${nome}!` : 'Olá, visitante!';
}

campoNome.addEventListener('input', atualizarSaudacao);
atualizarSaudacao(); 

const btnCopiar = document.getElementById('btnCopiar');

if (btnCopiar) {
    btnCopiar.addEventListener('click', async () => {
        const senha = document.getElementById('senhaGerada').textContent;
        try {
            await navigator.clipboard.writeText(senha);
            btnCopiar.textContent = 'Copiado!';
        } catch (erro) {
            btnCopiar.textContent = 'Não foi possível copiar';
        }
        setTimeout(() => (btnCopiar.textContent = 'Copiar'), 1500);
    });
}