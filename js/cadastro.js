const buttons = document.querySelectorAll('[data-perfil]');
const fields = document.getElementById('campos-profissionais');
const form = document.querySelector('.formulario-cadastro');
const password = form.elements.senha;
const confirmation = form.elements.confirmarSenha;
function chooseProfile(profile) {
    const professional = profile === 'psicologo';
    document.getElementById('papel').value = profile;
    buttons.forEach(item => {
        item.classList.toggle('ativa', item.dataset.perfil === profile);
        item.setAttribute('aria-pressed', String(item.dataset.perfil === profile));
    });
    fields.hidden = !professional;
    fields.querySelectorAll('input').forEach(input => {
        input.disabled = !professional;
        input.required = professional && input.name === 'crp';
    });
}
buttons.forEach(button => button.addEventListener('click', () => chooseProfile(button.dataset.perfil)));
chooseProfile(document.getElementById('papel').value);
function fieldError(field, id, message) {
    const error = document.getElementById(id);
    error.textContent = message;
    error.hidden = !message;
    field.setCustomValidity(message);
    field.setAttribute('aria-invalid', String(Boolean(message)));
}
function validatePasswords(force = false, edited = null) {
    let message = '';
    if (force || edited === password || password.value.length) {
        if (Array.from(password.value).length < 8) message = 'A senha deve ter no mínimo 8 caracteres.';
        else if (new TextEncoder().encode(password.value).length > 72 || password.value.includes('\0')) message = 'A senha excede o limite de 72 bytes ou contém caracteres inválidos.';
    }
    fieldError(password, 'senha-erro', message);
    const mismatch = (force || edited === confirmation || confirmation.value.length) && password.value !== confirmation.value;
    fieldError(confirmation, 'confirmacao-erro', mismatch ? 'As senhas não coincidem' : '');
    return !message && !mismatch;
}
password.addEventListener('input', () => validatePasswords(false, password));
confirmation.addEventListener('input', () => validatePasswords(false, confirmation));
form.addEventListener('invalid', () => validatePasswords(true), true);
form.addEventListener('submit', event => {
    if (!validatePasswords(true)) {
        event.preventDefault();
        form.reportValidity();
    }
});
