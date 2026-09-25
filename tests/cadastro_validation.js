/* Testes da lógica de eventos, sem dependências. Node é opcional para repetir localmente. */
function testCadastro(source) {
    const results = [];
    function assert(ok, label) { if (!ok) throw new Error(label); results.push(label); }
    class Element {
        constructor(value = '') { this.value = value; this.events = {}; this.attributes = {}; this.classes = new Set(); this.classList = { toggle: (name, active) => active ? this.classes.add(name) : this.classes.delete(name) }; }
        addEventListener(event, handler) { this.events[event] = handler; }
        setAttribute(name, value) { this.attributes[name] = value; }
        setCustomValidity(value) { this.validityMessage = value; }
        fire(event) { const e = { prevented: false, preventDefault() { this.prevented = true; } }; this.events[event](e); return e; }
    }
    for (const profile of ['paciente', 'psicologo']) {
        const senha = new Element(), confirmarSenha = new Element();
        const errors = { 'senha-erro': new Element(), 'confirmacao-erro': new Element(), papel: new Element(profile) };
        const buttons = ['paciente', 'psicologo'].map(value => { const e = new Element(); e.dataset = { perfil: value }; return e; });
        const crp = new Element(); crp.name = 'crp';
        const professionalFields = new Element(); professionalFields.querySelectorAll = () => [crp];
        const form = new Element(); form.elements = { senha, confirmarSenha }; form.reportValidity = () => true;
        const document = { querySelector: () => form, querySelectorAll: () => buttons, getElementById: id => id === 'campos-profissionais' ? professionalFields : errors[id] };
        const Encoder = class { encode(text) { return { length: unescape(encodeURIComponent(text)).length }; } };
        new Function('document', 'TextEncoder', source)(document, Encoder);
        assert(crp.required === (profile === 'psicologo'), 'perfil restaurado: ' + profile);
        senha.value = '1234'; senha.fire('input');
        assert(errors['senha-erro'].textContent === 'A senha deve ter no mínimo 8 caracteres.' && !errors['senha-erro'].hidden, 'erro ao digitar quatro caracteres: ' + profile);
        assert(errors['confirmacao-erro'].hidden, 'não antecipa confirmação antes da digitação');
        assert(form.fire('submit').prevented, 'envio de senha curta impedido');
        senha.value = '12345678'; senha.fire('input');
        assert(errors['senha-erro'].hidden && senha.validityMessage === '', 'erro de senha removido ao atingir oito caracteres');
        confirmarSenha.value = '12345679'; confirmarSenha.fire('input');
        assert(errors['confirmacao-erro'].textContent === 'As senhas não coincidem' && form.fire('submit').prevented, 'confirmação divergente impede envio');
        confirmarSenha.value = senha.value; confirmarSenha.fire('input');
        assert(errors['confirmacao-erro'].hidden && !form.fire('submit').prevented, 'confirmação corrigida libera envio');
        senha.value = 'áááá'; senha.fire('input'); assert(!errors['senha-erro'].hidden, 'caracteres multibyte contados corretamente');
        senha.value = '😀😀😀😀'; senha.fire('input'); assert(!errors['senha-erro'].hidden, 'quatro emojis não contam como oito caracteres');
        senha.value = 'áááááááá'; confirmarSenha.value = senha.value; senha.fire('input'); assert(!form.fire('submit').prevented, 'oito caracteres acentuados aceitos');
        senha.value = 'a'.repeat(73); senha.fire('input'); assert(!errors['senha-erro'].hidden, 'limite do hash também validado');
        senha.value = ''; senha.fire('input'); assert(!errors['senha-erro'].hidden, 'apagar a senha reapresenta orientação de erro');
        buttons[0].fire('click'); assert(!crp.required && crp.disabled && professionalFields.hidden, 'paciente dispensa campos profissionais');
        buttons[1].fire('click'); assert(crp.required && !crp.disabled && !professionalFields.hidden, 'psicólogo exige CRP');
    }
    return results;
}
if (typeof module !== 'undefined' && require.main === module) {
    const fs = require('node:fs'); const path = require('node:path');
    const checks = testCadastro(fs.readFileSync(path.join(__dirname, '../js/cadastro.js'), 'utf8'));
    console.log(checks.length + ' verificações de JavaScript passaram.');
}
