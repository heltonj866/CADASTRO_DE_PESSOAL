// ARQUIVO: js/script.js

const postos = ["Cel", "Ten Cel", "Maj", "Cap", "1º Ten", "2º Ten", "Asp", "Subten", "1º Sgt", "2º Sgt", "3º Sgt", "Cb", "Sd EP", "Sd EV", "SC"];
const qmgs = {
    "Carreira": ["04 - Engenharia", "01 - Infantaria", "05 - Comunicações", "06 - Mat Bel", "07 - Intendência", "08 - Saúde"],
    "Temporários": ["QM 05-01 - Combatente", "QM 05-15 - Auxiliar de Topógrafo", "QM 05-23 - Pessoal de Construções e Instalações", "QM 07-01 - Infantaria-Combatente", "QM 11-71 - Comunicações", "QM 08-33 - Auxliar de Saúde", "QM 09-46 - Mec Arm", "QM 09-47 - Mec Eletricista", "QM 09-50 - Mec Operador", "QM 09-51 - Mec Viatura Auto", "QM 10-61 - Pessoal de Aprovisionamento", "QM 00-10 - Corneteiro/Clarim", "0000 - Não Qualificado"]
};
let currentUserRole = '';

document.addEventListener('input', function (e) {
    if (!e.target || !e.target.name) return;
    const el = e.target;
    if (['cpf', 'new_user_idt', 'usuario', 'identidade'].includes(el.name)) {
        let v = el.value.replace(/\D/g, "").substring(0, 11);
        el.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    }
    if (['celular_princ', 'celular_sec', 'tel_resp'].includes(el.name)) {
        let v = el.value.replace(/\D/g, "").substring(0, 11);
        el.value = v.length > 10 ? v.replace(/^(\d{2})(\d{5})(\d{4})/, "($1) $2-$3") : v.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
    }
    if (el.name === 'cep') {
        let v = el.value.replace(/\D/g, "");
        el.value = v.substring(0, 8).replace(/^(\d{5})(\d{3})/, "$1-$2");
        if (v.length === 8) buscarCEP(v);
    }
});

async function buscarCEP(cep) {
    try {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const json = await res.json();
        if (!json.erro) {
            const end = document.querySelector('input[name="endereco"]');
            const bai = document.querySelector('input[name="bairro"]');
            const cid = document.querySelector('input[name="cidade"]');
            const uf = document.querySelector('input[name="estado"]');
            
            if (end) end.value = json.logradouro;
            if (bai) bai.value = json.bairro;
            if (cid) cid.value = json.localidade;
            if (uf) uf.value = json.uf;
            
            const numInput = document.querySelector('input[name="num_residencia"]');
            if (numInput) numInput.focus();
        } else {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'CEP não encontrado.', showConfirmButton: false, timer: 2000 });
        }
    } catch (e) { console.error("Erro CEP:", e); }
}

document.addEventListener('DOMContentLoaded', () => {
    popularSelects();
    verificarSessao();

    const formLogin = document.getElementById('formLogin');
    if (formLogin) formLogin.addEventListener('submit', handleLogin);

    const formMilitar = document.getElementById('militaryForm');
    if (formMilitar) {
        formMilitar.addEventListener('submit', function (e) {
            e.preventDefault();
            const btnSave = document.querySelector('#formFooterButtons button[type="submit"]');
            if (btnSave) {
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    Swal.fire('Atenção', 'Preencha os campos obrigatórios!', 'warning');
                    return;
                }
                salvarMilitar();
            }
        });
    }

    const tabNovo = document.querySelector('button[data-bs-target="#tab-novo"]');
    if (tabNovo) tabNovo.addEventListener('click', resetarFormularioUsuario);
});

function getEl(k) { return document.getElementById(k) || document.querySelector(`[name="${k}"]`); }
function setVal(k, v) { const el = getEl(k); if (el) el.value = v || ''; }

async function handleLogin(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const txt = btn.innerText; btn.innerText = "Verificando..."; btn.disabled = true;
    try {
        const fd = new FormData(e.target);
        const res = await fetch('backend/login.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identidade: fd.get('usuario'), senha: e.target.querySelectorAll('input')[1].value })
        });
        const json = await res.json();
        if (json.status === 'sucesso') {
            document.getElementById('loginScreen').classList.add('hidden');
            document.getElementById('appScreen').classList.remove('hidden');
            window.currentUserRole = json.role;
            localStorage.setItem('sismil_role', json.role); 
            window.location.reload();
            aplicarPermissoes(json.role);
            atualizarDashboard();
        } else Swal.fire('Erro', json.msg, 'error');
    } catch (err) { console.error(err); } finally { btn.innerText = txt; btn.disabled = false; }
}

async function salvarMilitar() {
    const fd = new FormData(document.getElementById('militaryForm'));
    const isEdicao = document.getElementById('militarId').value !== "";

    const btn = document.querySelector('#militaryForm button[type="submit"]');
    if (btn) { btn.innerText = 'Salvando...'; btn.disabled = true; }

    try {
        const res = await fetch('backend/save_militar.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (json.status === 'sucesso') {
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            Toast.fire({ icon: 'success', title: 'Salvo com sucesso!' });

            if(typeof atualizarDashboard === 'function') atualizarDashboard();   
            if(typeof atualizarListagem === 'function') atualizarListagem();

            if (!isEdicao) {
                document.getElementById('militaryForm').reset();
                document.getElementById('militarId').value = ''; 
                const img = document.getElementById('imgPreview');
                if(img) img.src = 'assets/sem_foto.png';
                
                const btnAdd = document.getElementById('btnAdicionarVeiculo');
                if(btnAdd) btnAdd.disabled = true;
                const tbody = document.getElementById('listaVeiculosMilitar');
                if(tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-muted py-3">Primeiro salve os dados básicos do militar para poder gerenciar a frota.</td></tr>';

                const firstTabEl = document.querySelector('#myTab button[data-bs-target="#basic"]');
                if(firstTabEl) { const tab = new bootstrap.Tab(firstTabEl); tab.show(); }

                const cpfInput = document.querySelector('input[name="cpf"]');
                if(cpfInput) cpfInput.focus();
            }
        } else {
            Swal.fire('Erro', json.msg, 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Erro', 'Falha técnica ao salvar.', 'error');
    } finally {
        if (btn) { btn.innerText = 'Salvar Dados'; btn.disabled = false; }
    }
}

async function carregarMilitarNoForm(id, modo) {
    limparFormulario();
    try {
        const res = await fetch(`backend/get_militar.php?id=${id}&v=${Date.now()}`);
        const json = await res.json();
        if (json.status !== 'sucesso') throw new Error(json.msg);
        const d = json.dados;

        setVal('militarId', d.id);
        setVal('cpf', d.identidade); setVal('posto_grad', d.posto_grad); setVal('numero', d.numero);
        setVal('nome_guerra', d.nome_guerra); setVal('nome_completo', d.nome_completo);
        setVal('subunidade', d.subunidade); setVal('pelotao', d.pelotao); setVal('secao', d.secao);
        setVal('qmg', d.qmg); setVal('dt_nascimento', d.dt_nascimento); setVal('tipo_sanguineo', d.tipo_sanguineo);
        setVal('dt_praca', d.dt_praca); setVal('idt_militar', d.idt_militar);
        setVal('email', d.email); setVal('celular_princ', d.celular_princ); setVal('celular_sec', d.celular_sec);
        setVal('nome_resp', d.nome_resp); setVal('tel_resp', d.tel_resp); setVal('cep', d.cep);
        setVal('endereco', d.endereco); setVal('num_residencia', d.num_residencia); setVal('bairro', d.bairro);
        setVal('cidade', d.cidade); setVal('estado', d.estado);
        setVal('cat_cnh', d.cat_cnh); setVal('validade_cnh', d.validade_cnh);

        if (d.foto_path) document.getElementById('imgPreview').src = `uploads/${d.foto_path}`;

        const linkCnh = document.getElementById('link_pdf_cnh');
        if (linkCnh) {
            if (d.pdf_habilitacao) {
                linkCnh.innerHTML = `<a href="uploads/documentos/${d.pdf_habilitacao}" target="_blank" class="badge bg-danger text-decoration-none py-1 mt-1"><i class="fas fa-external-link-alt"></i> Visualizar CNH Anexada</a>`;
                linkCnh.classList.remove('d-none');
            } else { linkCnh.classList.add('d-none'); }
        }

        document.getElementById('fullRegistrationCard').classList.remove('hidden');
        document.getElementById('fullRegistrationCard').scrollIntoView({ behavior: 'smooth' });

        const badge = document.getElementById('formModeBadge');
        const footerBtns = document.getElementById('formFooterButtons');
        let role = (window.currentUserRole || localStorage.getItem('sismil_role') || '').toLowerCase().trim();
        const tabS1 = document.getElementById('tab-s1');
        
        // Verifica se o militar está desligado
        const isDesligado = (d.status_ativo == 0);

        if (['admin', 'sargenteacao'].includes(role)) {
            if (tabS1) tabS1.classList.remove('d-none');
            if (typeof carregarHistoricoS1 === 'function') carregarHistoricoS1(d.id);

            badge.innerText = "Edição de Cadastro";
            badge.className = "badge bg-primary";

            // Injeta o botão do Dossier e os botões de Desligamento
            let actBtns = `<a href="backend/dossier_militar.php?id=${d.id}" target="_blank" class="btn btn-dark fw-bold me-2"><i class="fas fa-file-contract me-1"></i> Gerar Dossier (PDF)</a>`;
            
            if (!isDesligado) {
                actBtns += `<button type="button" class="btn btn-outline-danger me-2" onclick="desligarMilitar(${d.id}, '${d.posto_grad} ${d.nome_guerra}')"><i class="fas fa-user-slash me-1"></i> Desligar Militar</button>`;
            } else {
                actBtns += `<span class="badge bg-danger fs-6 align-self-center me-2"><i class="fas fa-user-slash"></i> DESLIGADO</span>`;
                if (d.pdf_nada_consta) {
                    actBtns += `<a href="uploads/documentos/${d.pdf_nada_consta}" target="_blank" class="btn btn-sm btn-outline-danger me-2"><i class="fas fa-file-pdf"></i> PDF Desligamento</a>`;
                }
                // NOVO: Botão de Reativar
                actBtns += `<button type="button" class="btn btn-success fw-bold me-2" onclick="reativarMilitar(${d.id}, '${d.posto_grad} ${d.nome_guerra}')"><i class="fas fa-user-plus me-1"></i> Reativar Militar</button>`;
            }

            if (role === 'admin') {
                actBtns += `<button type="button" class="btn btn-outline-dark border-0" onclick="excluirMilitar()" title="Apagar Permanentemente"><i class="fas fa-trash-alt"></i></button>`;
            }

            footerBtns.innerHTML = `
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <div>${actBtns}</div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" onclick="limparFormulario()">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Salvar Dados</button>
                    </div>
                </div>`;

            const inputs = document.querySelectorAll('#militaryForm input, #militaryForm select');
            inputs.forEach(el => el.removeAttribute('readonly'));
            document.querySelectorAll('#militaryForm select').forEach(s => s.style.pointerEvents = 'auto');
            if (getEl('cpf')) getEl('cpf').setAttribute('readonly', true);

        } else if (['s2', 'transporte'].includes(role)) {
            if (tabS1) tabS1.classList.add('d-none');

            badge.innerText = "Inspeção Veicular (S2)";
            badge.className = "badge bg-warning text-dark";
            try { const tab = document.querySelector('button[data-bs-target="#vehicle"]'); if (tab) new bootstrap.Tab(tab).show(); } catch (e) { }

            footerBtns.innerHTML = `<div class="w-100 text-end"><button type="button" class="btn btn-secondary" onclick="limparFormulario()">Fechar Ficha</button></div>`;

            const inputs = document.querySelectorAll('#militaryForm input, #militaryForm select');
            inputs.forEach(el => el.setAttribute('readonly', true));
            document.querySelectorAll('#militaryForm select').forEach(s => s.style.pointerEvents = 'none');
        } else {
            if (tabS1) tabS1.classList.add('d-none');
        }

        carregarVeiculosMilitar(d.id, modo);

    } catch (e) {
        console.error(e);
        Swal.fire('Erro', 'Não foi possível carregar os dados.', 'error');
    }
}

// -----------------------------------------------------------------------------
// GESTÃO DE VEÍCULOS
// -----------------------------------------------------------------------------

async function carregarVeiculosMilitar(militarId, modo) {
    const btnAdd = document.getElementById('btnAdicionarVeiculo');
    let role = (window.currentUserRole || localStorage.getItem('sismil_role') || '').toLowerCase().trim();
    
    if (btnAdd) {
        if (['admin', 'sargenteacao'].includes(role)) {
            btnAdd.disabled = false;
            btnAdd.setAttribute('onclick', `abrirModalVeiculo(${militarId})`);
            btnAdd.classList.remove('d-none');
        } else { btnAdd.classList.add('d-none'); }
    }

    try {
        const res = await fetch(`backend/get_veiculos.php?militar_id=${militarId}&v=${Date.now()}`);
        const json = await res.json();
        const tbody = document.getElementById('listaVeiculosMilitar');
        tbody.innerHTML = '';

        if (json.status === 'sucesso' && json.dados.length > 0) {
            json.dados.forEach(v => {
                const badgClass = v.homologado == 1 ? 'bg-success' : 'bg-warning text-dark';
                const badgTxt = v.homologado == 1 ? 'HOMOLOGADO' : 'PENDENTE S2';
                const linkPdf = v.pdf_veiculo ? `<a href="uploads/documentos/${v.pdf_veiculo}" target="_blank" class="text-danger" title="Ver CRLV"><i class="fas fa-file-pdf fa-lg"></i></a>` : '<span class="text-muted">-</span>';
                const obsAviso = v.observacao_s2 ? `<div class="text-danger small mt-1 fw-bold" style="line-height:1.1;"><i class="fas fa-exclamation-triangle"></i> ${v.observacao_s2}</div>` : '';
                
                let acoes = '';

                if (['admin', 'sargenteacao'].includes(role)) {
                    acoes = `
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 me-1" onclick='editarVeiculo(${JSON.stringify(v)})' title="Editar"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="excluirVeiculo(${v.id}, ${militarId})" title="Excluir"><i class="fas fa-trash"></i></button>
                    `;
                } else if (['s2', 'transporte'].includes(role)) {
                    acoes = `
                        <button type="button" class="btn btn-sm btn-warning py-0 px-1 fw-bold me-1" onclick="alterarHomologacaoVeiculo(${v.id}, ${militarId}, ${v.homologado}, \`${v.observacao_s2 || ''}\`)" title="Avaliar Veículo"><i class="fas fa-stamp"></i></button>
                        <button type="button" class="btn btn-sm btn-dark py-0 px-1 fw-bold" onclick="imprimirSelo(${v.id})" title="Imprimir Selo"><i class="fas fa-print"></i></button>
                    `;
                }

                const marcaTxt = v.marca ? `${v.marca} / ` : '';
                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-secondary">${v.tipo_veiculo}</td>
                        <td><span class="badge bg-light text-dark border border-dark text-uppercase fs-6">${v.placa}</span></td>
                        <td>${marcaTxt}${v.modelo}</td>
                        <td>${v.cor}</td>
                        <td>${v.validade_crlv ? v.validade_crlv.split('-').reverse().join('/') : '---'}</td>
                        <td>${linkPdf}</td>
                        <td><span class="badge ${badgClass}">${badgTxt}</span>${obsAviso}</td>
                        <td>${acoes}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="text-muted py-4">Nenhum veículo cadastrado para este militar.</td></tr>';
        }
    } catch (e) { console.error(e); }
}

function abrirModalVeiculo(militarId) {
    document.getElementById('formVeiculo').reset();
    document.getElementById('veiculo_id').value = '';
    document.getElementById('v_militar_id').value = militarId;
    document.getElementById('modalVeiculoTitle').innerHTML = '<i class="fas fa-car me-2"></i> Adicionar Veículo';
    document.getElementById('v_link_pdf').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalVeiculo')).show();
}

function editarVeiculo(v) {
    document.getElementById('formVeiculo').reset();
    document.getElementById('veiculo_id').value = v.id;
    document.getElementById('v_militar_id').value = v.militar_id;
    document.getElementById('v_tipo').value = v.tipo_veiculo;
    document.getElementById('v_placa').value = v.placa;
    document.getElementById('v_marca').value = v.marca || '';
    document.getElementById('v_modelo').value = v.modelo;
    document.getElementById('v_cor').value = v.cor;
    document.getElementById('v_validade').value = v.validade_crlv || '';
    
    const link = document.getElementById('v_link_pdf');
    if (v.pdf_veiculo) {
        link.innerHTML = `<a href="uploads/documentos/${v.pdf_veiculo}" target="_blank" class="badge bg-danger text-decoration-none py-1"><i class="fas fa-file-pdf"></i> Ver CRLV Atual</a>`;
        link.classList.remove('d-none');
    } else { link.classList.add('d-none'); }
    
    document.getElementById('modalVeiculoTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Editar Veículo';
    new bootstrap.Modal(document.getElementById('modalVeiculo')).show();
}

async function salvarVeiculo() {
    const form = document.getElementById('formVeiculo');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    
    const fd = new FormData(form);
    const militarId = document.getElementById('v_militar_id').value;
    
    try {
        const res = await fetch('backend/save_veiculo.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status === 'sucesso') {
            const modalEl = document.getElementById('modalVeiculo');
            bootstrap.Modal.getInstance(modalEl).hide();
            carregarVeiculosMilitar(militarId, 'edit');
            atualizarDashboard();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Veículo Salvo!', showConfirmButton: false, timer: 2000 });
        } else { Swal.fire('Erro', json.msg, 'error'); }
    } catch (e) { console.error(e); }
}

async function excluirVeiculo(id, militarId) {
    if (!confirm("Deseja realmente excluir este veículo?")) return;
    try {
        const res = await fetch('backend/excluir_veiculo.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
        const json = await res.json();
        if(json.status === 'sucesso'){
            carregarVeiculosMilitar(militarId, 'edit');
            atualizarDashboard();
        } else { alert("Erro: " + json.msg); }
    } catch (e) { console.error(e); }
}

async function alterarHomologacaoVeiculo(id, militarId, statusAtual, obsAtual) {
    const { value: formValues } = await Swal.fire({
        title: 'Avaliação da S2',
        html:
            `<div class="text-start mb-2 fw-bold">Status do Veículo:</div>
            <select id="swal-status" class="form-select mb-3">
                <option value="1" ${statusAtual == 1 ? 'selected' : ''}>🟢 LIBERADO (Aprovado)</option>
                <option value="0" ${statusAtual == 0 ? 'selected' : ''}>🔴 PENDENTE / REJEITADO</option>
            </select>
            <div class="text-start mb-2 fw-bold">Observações (Notificar Militar):</div>
            <textarea id="swal-obs" class="form-control" placeholder="Ex: Falta anexar a CNH, CRLV ilegível, etc..." rows="3">${obsAtual || ''}</textarea>`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<i class="fas fa-save"></i> Salvar Avaliação',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            return {
                status: document.getElementById('swal-status').value,
                obs: document.getElementById('swal-obs').value
            }
        }
    });

    if (formValues) {
        try {
            const res = await fetch('backend/toggle_homolog_veiculo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: formValues.status, observacao: formValues.obs })
            });
            const json = await res.json();
            
            if(json.status === 'sucesso'){
                carregarVeiculosMilitar(militarId, 's2');
                atualizarDashboard();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Avaliação salva!', showConfirmButton: false, timer: 1500 });
            } else { 
                Swal.fire("Erro", json.msg, "error"); 
            }
        } catch (e) { console.error(e); }
    }
}

// -----------------------------------------------------------------------------
// BUSCAS E DESLIGAMENTO
// -----------------------------------------------------------------------------

async function desligarMilitar(id, postoNome) {
    const { value: arquivoPdf } = await Swal.fire({
        title: 'Desligamento de Militar',
        html: `
            <div class="alert alert-danger text-start small mb-3">
                <strong>Atenção:</strong> Está prestes a desligar o militar <b>${postoNome}</b>. O histórico será mantido. 
                <br><br><b>É obrigatório anexar o NADA DEVE (PDF).</b>
            </div>
            <div class="text-start fw-bold mb-2">Anexar Ficha de Nada Deve:</div>
            <input type="file" id="swal-nada-deve" class="form-control border-danger" accept="application/pdf">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-slash"></i> Confirmar Desligamento',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const arquivo = document.getElementById('swal-nada-deve').files[0];
            if (!arquivo) { Swal.showValidationMessage('Obrigatório anexar o PDF do Nada Deve para prosseguir.'); return false; }
            if (arquivo.type !== 'application/pdf') { Swal.showValidationMessage('O ficheiro tem de ser no formato PDF.'); return false; }
            return arquivo;
        }
    });

    if (arquivoPdf) {
        const formData = new FormData();
        formData.append('militar_id', id);
        formData.append('nada_consta', arquivoPdf);

        Swal.fire({ title: 'A processar desligamento...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

        try {
            const res = await fetch('backend/desligar_militar.php', { method: 'POST', body: formData });
            const json = await res.json();
            
            if (json.status === 'sucesso') {
                Swal.fire('Desligado!', json.msg, 'success');
                atualizarListagem();
                const idAberto = document.getElementById('militarId')?.value;
                if(idAberto == id) carregarMilitarNoForm(id, 'edit');
            } else { Swal.fire('Erro', json.msg, 'error'); }
        } catch (e) { Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error'); }
    }
}

async function reativarMilitar(id, postoNome) {
    const result = await Swal.fire({
        title: 'Reativar Cadastro?',
        html: `Deseja reintegrar o militar <b>${postoNome}</b> ao Efetivo Pronto do Batalhão?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-check"></i> Sim, Reativar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        Swal.fire({ title: 'Reativando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

        try {
            const res = await fetch('backend/reativar_militar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const json = await res.json();
            
            if (json.status === 'sucesso') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Militar Reativado!', showConfirmButton: false, timer: 2000 });
                atualizarListagem();
                
                // Se a ficha desse militar estiver aberta, atualiza a tela na hora
                const idAberto = document.getElementById('militarId')?.value;
                if(idAberto == id) {
                    carregarMilitarNoForm(id, 'edit');
                }
            } else { Swal.fire('Erro', json.msg, 'error'); }
        } catch (e) { Swal.fire('Erro', 'Falha na comunicação.', 'error'); }
    }
}

async function realizarBusca(e, tipo) {
    if (e) e.preventDefault();
    const area = document.getElementById('resultsArea');
    area.innerHTML = '<div class="text-center p-3">Buscando...</div>';
    area.classList.remove('hidden');

    let url = `backend/search.php?tipo_busca=${tipo}`;
    if (tipo === 'geral') {
        const t = document.querySelector('#searchFormGeneral input[type="text"]').value;
        const p = document.getElementById('searchPosto').value;
        const q = document.getElementById('searchQMG').value;
        const incInativos = document.getElementById('chkInativos') && document.getElementById('chkInativos').checked ? 1 : 0;
        url += `&termo=${t}&posto=${p}&qmg=${q}&inativos=${incInativos}`;
    } else {
        const f = document.querySelector('input[name="filtroCnh"]:checked')?.value || 'TODAS';
        url += `&filtro_cnh=${f}`;
    }

    try {
        const res = await fetch(url);
        const json = await res.json();
        area.innerHTML = '';

        let roleRaw = window.currentUserRole || localStorage.getItem('sismil_role') || 'user';
        let role = String(roleRaw).toLowerCase().trim();

        if (json.status === 'sucesso' && json.dados.length > 0) {
            document.getElementById('resultsCount').innerText = json.dados.length + " encontrados";

            json.dados.forEach(m => {
                const foto = m.foto_path ? `uploads/${m.foto_path}` : 'assets/sem_foto.png';
                let btnAcao = '';
                
                const isDesligado = (m.status_ativo == 0);
                const badgeDesligado = isDesligado ? `<div class="mt-1"><span class="badge bg-danger"><i class="fas fa-user-slash"></i> DESLIGADO</span></div>` : '';
                const linkPdf = (isDesligado && m.pdf_nada_consta) ? `<div class="mt-1"><a href="uploads/documentos/${m.pdf_nada_consta}" target="_blank" class="text-danger small text-decoration-none fw-bold"><i class="fas fa-file-pdf"></i> Nada Deve</a></div>` : '';

                if (['admin', 'sargenteacao'].includes(role)) {
                    btnAcao = `<button class="btn btn-sm btn-outline-primary w-100 mb-1" onclick="carregarMilitarNoForm(${m.id}, 'edit')"><i class="fas fa-edit me-1"></i> Editar</button>`;
                    if (!isDesligado) {
                        btnAcao += `<button class="btn btn-sm btn-outline-danger w-100 mb-1" onclick="desligarMilitar(${m.id}, '${m.posto_grad} ${m.nome_guerra}')"><i class="fas fa-user-slash me-1"></i> Desligar</button>`;
                    } else {
                        // NOVO: Botão Verde na lista
                        btnAcao += `<button class="btn btn-sm btn-success w-100 mb-1" onclick="reativarMilitar(${m.id}, '${m.posto_grad} ${m.nome_guerra}')"><i class="fas fa-user-plus me-1"></i> Reativar</button>`;
                    }
                } else if (['s2', 'transporte'].includes(role)) {
                    btnAcao = `<button class="btn btn-sm btn-warning w-100 mb-1 fw-bold" onclick="carregarMilitarNoForm(${m.id}, 'homolog')"><i class="fas fa-search me-1"></i> Inspecionar</button>`;
                } else {
                    btnAcao = `<button class="btn btn-sm btn-primary w-100 mb-1" onclick="abrirModalLeitura(${m.id})"><i class="fas fa-eye me-1"></i> Ver Ficha</button>`;
                }
                const btnResumo = `<button class="btn btn-sm btn-outline-info w-100" onclick="verDetalhesMilitar(${m.id})"><i class="fas fa-id-card me-1"></i> Resumo</button>`;

                area.innerHTML += `
                <div class="col-md-3 mb-3">
                    <div class="card h-100 shadow-sm border-0" style="${isDesligado ? 'opacity: 0.8;' : ''}">
                        <div style="height:200px;overflow:hidden;background:#f0f0f0;">
                             <img src="${foto}" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='assets/sem_foto.png'">
                        </div>
                        <div class="card-body text-center p-2">
                            <h6 class="fw-bold m-0 ${isDesligado ? 'text-danger' : ''}">${m.posto_grad} ${m.nome_guerra}</h6>
                            <small class="text-muted">${m.subunidade}</small>
                            ${badgeDesligado}
                            ${linkPdf}
                            <div class="mt-2">${btnAcao}${btnResumo}</div>
                        </div>
                    </div>
                </div>`;
            });
        } else {
            area.innerHTML = '<div class="alert alert-warning text-center">Nenhum registro encontrado.</div>';
            document.getElementById('resultsCount').innerText = "0";
        }
    } catch (err) {
        console.error(err);
        area.innerHTML = '<div class="alert alert-danger">Erro ao buscar dados.</div>';
    }
}

async function verDetalhesMilitar(id) {
    document.body.style.cursor = 'wait';
    try {
        const res = await fetch(`backend/get_militar.php?id=${id}&v=${Date.now()}`);
        const json = await res.json();

        if (json.status === 'sucesso') {
            const d = json.dados;
            const fmt = (dt) => (dt && dt !== '0000-00-00') ? String(dt).split('-').reverse().join('/') : '---';
            const txt = (t) => (t !== null && t !== undefined && String(t).trim() !== '') ? t : '---';

            const foto = d.foto_path ? `uploads/${d.foto_path}` : 'assets/sem_foto.png';
            document.getElementById('visFoto').src = foto;
            
            const isDesligado = d.status_ativo == 0;
            const aviso = isDesligado ? '<span class="text-danger fw-bold border border-danger px-1 me-1 rounded">DESLIGADO</span> ' : '';

            document.getElementById('visGuerra').innerHTML = aviso + txt(d.nome_guerra);
            document.getElementById('visPosto').innerText = txt(d.posto_grad);
            document.getElementById('visNumero').innerText = txt(d.numero);
            document.getElementById('visNomeCompleto').innerText = txt(d.nome_completo);
            document.getElementById('visIdtMil').innerText = txt(d.idt_militar);
            document.getElementById('visCpf').innerText = txt(d.identidade);
            document.getElementById('visNascimento').innerText = fmt(d.dt_nascimento);
            document.getElementById('visSangue').innerText = txt(d.tipo_sanguineo);
            document.getElementById('visSu').innerText = txt(d.subunidade);
            document.getElementById('visPelotaoSecao').innerText = `${d.pelotao || ''} / ${d.secao || ''}`;
            document.getElementById('visQmg').innerText = txt(d.qmg);
            document.getElementById('visDtPraca').innerText = fmt(d.dt_praca);

            const areaCnh = document.getElementById('visAreaCnh');
            if (d.cat_cnh && String(d.cat_cnh).trim() !== '') {
                areaCnh.classList.remove('d-none');
                document.getElementById('visCatCnh').innerText = "Cat " + d.cat_cnh;
                document.getElementById('visValCnh').innerText = fmt(d.validade_cnh);
            } else { areaCnh.classList.add('d-none'); }

            new bootstrap.Modal(document.getElementById('modalVisualizar')).show();
        } else { Swal.fire("Erro", json.msg, "error"); }
    } catch (e) { Swal.fire("Erro", "Erro técnico.", "error"); }
    finally { document.body.style.cursor = 'default'; }
}

async function abrirModalLeitura(id) {
    document.body.style.cursor = 'wait';

    try {
        const res = await fetch(`backend/get_militar.php?id=${id}&v=${Date.now()}`);
        const json = await res.json();

        if (json.status === 'sucesso') {
            const d = json.dados;
            const txt = (v) => (v && v !== 'null' && String(v).trim() !== '') ? v : '---';
            const data = (dt) => (dt && dt.length === 10) ? dt.split('-').reverse().join('/') : '---';

            document.getElementById('f_foto').src = d.foto_path ? `uploads/${d.foto_path}` : 'assets/sem_foto.png';
            
            const isDesligado = d.status_ativo == 0;
            const aviso = isDesligado ? '<span class="badge bg-danger fs-6 me-2 align-text-bottom">DESLIGADO</span>' : '';
            document.getElementById('f_posto').innerHTML = aviso + txt(d.posto_grad);
            
            document.getElementById('f_guerra').innerText = txt(d.nome_guerra);
            document.getElementById('f_nome_completo').innerText = txt(d.nome_completo);
            document.getElementById('f_su').innerText = txt(d.subunidade);
            document.getElementById('f_secao').innerText = txt(d.pelotao) + (d.secao ? ` / ${d.secao}` : '');
            document.getElementById('f_qmg').innerText = txt(d.qmg);

            document.getElementById('f_cpf').innerText = txt(d.identidade);
            document.getElementById('f_idt').innerText = txt(d.idt_militar);
            document.getElementById('f_numero').innerText = txt(d.numero);
            document.getElementById('f_nasc').innerText = data(d.dt_nascimento);
            document.getElementById('f_sangue').innerText = txt(d.tipo_sanguineo);
            document.getElementById('f_praca').innerText = data(d.dt_praca);

            document.getElementById('f_cel1').innerText = txt(d.celular_princ);
            document.getElementById('f_cel2').innerText = txt(d.celular_sec);
            document.getElementById('f_email').innerText = txt(d.email);
            document.getElementById('f_end').innerText = txt(d.endereco);
            document.getElementById('f_end_num').innerText = txt(d.num_residencia);
            document.getElementById('f_bairro').innerText = txt(d.bairro);
            document.getElementById('f_cidade').innerText = `${txt(d.cidade)} - ${txt(d.estado)}`;
            document.getElementById('f_cep').innerText = txt(d.cep);
            document.getElementById('f_resp_nome').innerText = txt(d.nome_resp);
            document.getElementById('f_resp_tel').innerText = txt(d.tel_resp);

            document.getElementById('f_cnh_cat').innerHTML = txt(d.cat_cnh) + (d.pdf_habilitacao ? ` <a href="uploads/documentos/${d.pdf_habilitacao}" target="_blank" class="text-danger ms-2"><i class="fas fa-file-pdf fa-lg"></i></a>` : '');
            document.getElementById('f_cnh_val').innerText = data(d.validade_cnh);
            
            try {
                const resVeic = await fetch(`backend/get_veiculos.php?militar_id=${id}&v=${Date.now()}`);
                const jsonVeic = await resVeic.json();
                
                const areaVeiculos = document.getElementById('f_lista_veiculos');
                if (areaVeiculos) {
                    areaVeiculos.innerHTML = '';
                    if (jsonVeic.status === 'sucesso' && jsonVeic.dados.length > 0) {
                        jsonVeic.dados.forEach(v => {
                            const badgeStatus = v.homologado == 1 ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> LIBERADO</span>' : '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> PENDENTE</span>';
                            const obsS2 = v.observacao_s2 ? `<div class="text-danger small mt-1 fw-bold"><i class="fas fa-exclamation-triangle"></i> S2: ${v.observacao_s2}</div>` : '';
                            const docCrlv = v.pdf_veiculo ? `<a href="uploads/documentos/${v.pdf_veiculo}" target="_blank" class="text-danger ms-2" title="Visualizar Documento"><i class="fas fa-file-pdf"></i> PDF</a>` : '';
                            const dataCrlv = v.validade_crlv ? v.validade_crlv.split('-').reverse().join('/') : '---';
                            const nomeVeiculo = `${v.marca ? v.marca + ' / ' : ''}${v.modelo}`;

                            areaVeiculos.innerHTML += `
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-2 bg-light h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="bg-white border border-dark rounded text-center fw-bold font-monospace px-2 py-1 fs-6 text-uppercase">${v.placa}</span>
                                        ${badgeStatus}
                                    </div>
                                    <div class="fw-bold text-dark text-uppercase text-truncate" title="${nomeVeiculo}">${nomeVeiculo}</div>
                                    <div class="small text-muted mb-1">Cor: <strong>${v.cor}</strong></div>
                                    <div class="small text-muted">CRLV: <strong>${dataCrlv}</strong> ${docCrlv}</div>
                                    ${obsS2}
                                </div>
                            </div>
                        `;
                        });
                    } else {
                        areaVeiculos.innerHTML = '<div class="col-12"><div class="alert alert-secondary py-2 small text-center mb-0">Nenhum veículo cadastrado.</div></div>';
                    }
                }

                new bootstrap.Modal(document.getElementById('modalFichaLeitura')).show();
            } catch (e) {
                console.error(e);
                alert("Erro de conexão ao buscar dados.");
            } finally {
                document.body.style.cursor = 'default';
            }
        } else {
            alert("Erro: " + json.msg);
        }
    } catch (e) {
        console.error(e);
        alert("Erro de conexão ao buscar dados.");
    } finally {
        document.body.style.cursor = 'default';
    }
}

// -----------------------------------------------------------------------------
// UTILITÁRIOS E HISTÓRICO
// -----------------------------------------------------------------------------

function popularSelects() {
    const sp = document.getElementById('selectPosto'), sc = document.getElementById('searchPosto');
    postos.forEach(p => { if (sp) sp.add(new Option(p, p)); if (sc) sc.add(new Option(p, p)) });
    const sq = document.getElementById('selectQMG'), sqc = document.getElementById('searchQMG');
    const add = (el) => { for (const [g, l] of Object.entries(qmgs)) { const o = document.createElement('optgroup'); o.label = g; l.forEach(q => o.appendChild(new Option(q, q))); el.appendChild(o) } };
    if (sq) add(sq); if (sqc) add(sqc);
}

function verificarSessao() { fetch('backend/check_session.php').then(r => r.json()).then(j => { if (j.status === 'logado') { document.getElementById('loginScreen').classList.add('hidden'); document.getElementById('appScreen').classList.remove('hidden'); currentUserRole = j.role; aplicarPermissoes(j.role); atualizarDashboard(); } }) }

function aplicarPermissoes(role) {
    const adminBtn = document.getElementById('btnAdminUsers');
    const display = document.getElementById('displayUserRole');
    const formCard = document.getElementById('fullRegistrationCard'); 
    const btnRelatorio = document.getElementById('btnRelatorioS2'); // Captura o novo botão

    if (display) display.innerText = role.toUpperCase();
    const r = role ? role.toLowerCase() : '';

    if (btnRelatorio) btnRelatorio.classList.add('hidden');

    if (r === 'admin') {
        if (adminBtn) adminBtn.classList.remove('hidden');
        if (formCard) formCard.classList.remove('hidden'); 
        if (btnRelatorio) btnRelatorio.classList.remove('hidden'); 
        carregarListaUsuarios();
    } else if (r === 'sargenteacao') {
        if (adminBtn) adminBtn.classList.add('hidden');
        if (formCard) formCard.classList.remove('hidden'); 
    } else if (r === 's2' || r === 'transporte') {
        if (adminBtn) adminBtn.classList.add('hidden');
        if (btnRelatorio) btnRelatorio.classList.remove('hidden'); 
        if (formCard) formCard.classList.add('hidden'); 
    } else {
        if (adminBtn) adminBtn.classList.add('hidden');
        if (formCard) formCard.classList.add('hidden');
    }
}

function previewImage(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function (e) { document.getElementById('imgPreview').src = e.target.result; }; reader.readAsDataURL(input.files[0]); } }
function atualizarDashboard() { 
    fetch('backend/dashboard_stats.php')
    .then(r => r.json())
    .then(j => { 
        if (j.status === 'sucesso') { 
            // Atualiza os contadores principais
            document.getElementById('dashMilitares').innerText = j.militares; 
            document.getElementById('dashVeiculos').innerText = j.veiculos; 
            document.getElementById('dashPendentes').innerText = j.pendentes; 
            
            const areaEfetivo = document.getElementById('dashEfetivoDetalhado');
            const labelTotalSU = document.getElementById('dashTotalSU');
            
            if (areaEfetivo && j.efetivo_su) {
                let contagemSU = 0;
                let cartoesHtml = '';
                
                for (const [su, dados] of Object.entries(j.efetivo_su)) {
                    contagemSU++;
                    
                    // Grelha interna com o padrão visual do SISMIL (linhas pontilhadas e texto secundário)
                    let linhasTabela = dados.detalhes.map(d => `
                        <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom: 1px dashed rgba(0,0,0,0.1);">
                            <span class="fw-bold text-secondary" style="font-size: 0.85rem;">${d.posto}</span>
                            <span class="badge bg-light text-dark border shadow-sm" style="min-width: 32px;">${d.qtd}</span>
                        </div>
                    `).join('');
                    
                    // Cartão com borda superior no verde do Exército e fundo branco limpo
                    cartoesHtml += `
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 6px; border-top: 4px solid var(--army-green) !important; background-color: #fff;">
                                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center p-3 pb-2">
                                    <h6 class="mb-0 fw-bold text-dark text-uppercase" style="letter-spacing: 0.5px;">
                                        <i class="fas fa-shield-alt me-2" style="color: var(--army-green);"></i> ${su}
                                    </h6>
                                    <span class="badge bg-success shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i> ${dados.total} PRONTO
                                    </span>
                                </div>
                                <div class="card-body px-3 pt-0 pb-3" style="max-height: 250px; overflow-y: auto;">
                                    <div class="d-flex justify-content-between mb-2 pb-1" style="border-bottom: 2px solid #e9ecef;">
                                        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Posto / Grad</span>
                                        <span class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Efetivo</span>
                                    </div>
                                    ${linhasTabela}
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                if (contagemSU === 0) {
                    areaEfetivo.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i><br>Nenhum efetivo cadastrado.</div>';
                } else {
                    areaEfetivo.innerHTML = cartoesHtml;
                }

                if(labelTotalSU) labelTotalSU.innerText = `${contagemSU} Subunidades`;
            }

            document.getElementById('dashboardPanel').classList.remove('hidden'); 
        } 
    })
    .catch(err => console.error("Erro ao carregar dashboard:", err));
}
async function realizarLogout() { await fetch('backend/logout.php'); location.reload(); }

async function carregarListaUsuarios() {
    const painel = document.getElementById('btnAdminUsers');
    const tbody = document.getElementById('listaUsuariosBody');
    if (!painel || !tbody) return;
    try {
        const res = await fetch(`backend/get_users.php?v=${Date.now()}`);
        const json = await res.json();
        tbody.innerHTML = '';
        if (json.status === 'sucesso') {
            json.data.forEach(u => {
                const badgeClass = u.role === 'admin' ? 'bg-danger' : (u.role === 's2' ? 'bg-warning text-dark' : 'bg-secondary');
                tbody.innerHTML += `<tr><td class="ps-3"><div class="fw-bold">${u.posto_grad} ${u.nome_guerra}</div><small class="text-muted">${u.identidade}</small></td><td><span class="badge ${badgeClass}">${u.role.toUpperCase()}</span></td><td class="text-end pe-3"><button class="btn btn-sm btn-outline-primary me-1" onclick="prepararEdicao(${u.id}, '${u.posto_grad}', '${u.nome_guerra}', '${u.subunidade}', '${u.identidade}', '${u.role}')"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="excluirUsuario(${u.id})"><i class="fas fa-trash"></i></button></td></tr>`;
            });
        }
    } catch (e) { console.error(e); }
}

async function excluirUsuario(id) { if (confirm('Apagar usuário?')) { await fetch('backend/delete_user.php', { method: 'POST', body: JSON.stringify({ id }) }); carregarListaUsuarios(); } }
async function criarUsuario(e) { e.preventDefault(); const fd = new FormData(document.getElementById('formCreateUser')); const url = document.getElementById('edit_id').value ? 'backend/update_user.php' : 'backend/create_user.php'; await fetch(url, { method: 'POST', body: JSON.stringify(Object.fromEntries(fd)) }); document.getElementById('formCreateUser').reset(); carregarListaUsuarios(); }
function resetarFormularioUsuario() { document.getElementById('formCreateUser').reset(); document.getElementById('edit_id').value = ''; document.querySelector('[name="new_user_idt"]').removeAttribute('readonly'); document.querySelector('#formCreateUser button[type="submit"]').innerHTML = '<i class="fas fa-save me-1"></i> Salvar Dados'; }
function prepararEdicao(id, p, g, s, i, r) { new bootstrap.Tab(document.querySelector('button[data-bs-target="#tab-novo"]')).show(); document.getElementById('edit_id').value = id; const f = document.forms['formCreateUser']; f.new_user_posto.value = p; f.new_user_guerra.value = g; f.new_user_subunidade.value = s; f.new_user_idt.value = i; f.new_user_role.value = r; f.new_user_idt.setAttribute('readonly', true); document.querySelector('#formCreateUser button[type="submit"]').innerHTML = '<i class="fas fa-sync me-1"></i> Atualizar'; }
function exportarParaExcel() { const tipo = document.querySelector('#searchTabs .active') ? (document.querySelector('#searchTabs .active').id === 'li-tab-cnh' ? 'cnh' : 'geral') : 'geral'; window.open(`backend/export_excel.php?tipo_busca=${tipo}`, '_blank'); }

function limparFormulario() {
    const form = document.getElementById('militaryForm');
    if (form) form.reset();

    if (document.getElementById('militarId')) document.getElementById('militarId').value = '';
    if (document.getElementById('imgPreview')) document.getElementById('imgPreview').src = 'assets/sem_foto.png';

    const card = document.getElementById('fullRegistrationCard');
    if (card) card.classList.add('hidden');

    const footerBtns = document.getElementById('formFooterButtons');
    if (footerBtns) {
        footerBtns.innerHTML = `
            <div class="d-flex w-100 justify-content-between align-items-center">
                <div></div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="limparFormulario()">Limpar / Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Salvar Dados</button>
                </div>
            </div>
        `;
    }

    const resultsArea = document.getElementById('resultsArea');
    if (resultsArea && !resultsArea.classList.contains('hidden')) {
        resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function imprimirSelo(id) {
    const width = 600; const height = 400;
    const left = (screen.width - width) / 2; const top = (screen.height - height) / 2;
    window.open(`backend/print_selo.php?veiculo_id=${id}`, 'ImprimirSelo', `width=${width},height=${height},top=${top},left=${left},scrollbars=yes`);
}

function atualizarListagem() {
    const areaResultados = document.getElementById('resultsArea');
    if (areaResultados && !areaResultados.classList.contains('hidden')) {
        const tabCnh = document.getElementById('tab-cnh');
        const tipo = (tabCnh && tabCnh.classList.contains('active')) ? 'cnh' : 'geral';
        realizarBusca(null, tipo);
    }
}

async function excluirMilitar() {
    const id = document.getElementById('militarId').value;
    if (!id) return;

    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: "Essa ação apagará permanentemente o militar, seu histórico e toda a sua frota de veículos! Use apenas se o cadastro foi um erro.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch('backend/delete_militar.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
            const json = await res.json();
            if (json.status === 'sucesso') {
                Swal.fire('Excluído!', 'O registro foi removido.', 'success');
                limparFormulario();
                atualizarDashboard();
                atualizarListagem(); 
            } else { Swal.fire('Erro', json.msg, 'error'); }
        } catch (e) { Swal.fire('Erro', 'Erro de conexão.', 'error'); }
    }
}

async function carregarHistoricoS1(idMilitar) {
    try {
        const res = await fetch(`backend/get_historico.php?id=${idMilitar}`);
        const json = await res.json();
        const tbody = document.getElementById('listaS1Body');
        tbody.innerHTML = '';
        let contadorFO = 0;
        document.getElementById('s1_militar_id').value = idMilitar;

        if (json.status === 'sucesso') {
            window.listaHistoricoAtual = json.dados;
            json.dados.forEach(item => {
                if (item.categoria === 'ELOGIO' && item.tipo_detalhe === 'FO+' && item.consumido == 0) {
                    contadorFO++;
                }
                let corBadge = getBadgeClass(item.categoria, item.tipo_detalhe);
                const itemSafe = JSON.stringify(item).replace(/'/g, "&#39;");
                const row = `
                    <tr>
                        <td style="white-space:nowrap;">${new Date(item.data_fato).toLocaleDateString('pt-BR')}</td>
                        <td><span class="badge ${corBadge}">${item.tipo_detalhe}</span></td>
                        <td class="text-truncate" style="max-width: 250px;" title="${item.descricao}">${item.descricao}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1" onclick='prepararEdicaoS1(${item.id})'><i class="fas fa-pencil-alt"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="excluirHistoricoS1(${item.id}, ${idMilitar})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            atualizarBarraRecompensa(contadorFO);
        }
    } catch (e) { console.error(e); }
}

function getBadgeClass(cat, tipo) {
    const c = cat ? cat.toUpperCase() : '';
    const t = tipo ? tipo.toUpperCase() : '';
    if (c === 'DISCIPLINA') return 'bg-danger'; 
    if (t === 'FO+' || c === 'ELOGIO') return 'bg-success'; 
    if (t === 'FO-') return 'bg-warning text-dark'; 
    if (c === 'SAUDE') return 'bg-info text-dark'; 
    if (c === 'ACIDENTE') return 'bg-secondary'; 
    return 'bg-secondary'; 
}

function atualizarBarraRecompensa(contador) {
    const elTexto = document.getElementById('s1_contador_fo');
    const elBarra = document.getElementById('s1_bar_fo');
    const btn = document.getElementById('btnConcederRecompensa');

    if (elTexto) elTexto.innerText = `${contador} / 5`;
    let pct = (contador / 5) * 100;
    if (pct > 100) pct = 100;

    if (elBarra) {
        elBarra.style.width = `${pct}%`;
        elBarra.className = contador >= 5 ? 'progress-bar bg-warning progress-bar-striped progress-bar-animated' : 'progress-bar bg-success';
    }

    if (btn) {
        if (contador >= 5) {
            btn.classList.remove('d-none');
            btn.style.animation = "pulse 1.5s infinite";
        } else {
            btn.classList.add('d-none');
            btn.style.animation = "none";
        }
    }
}

async function salvarHistoricoS1() {
    const idMilitar = document.getElementById('s1_militar_id').value;
    const editId = document.getElementById('s1_edit_id').value; 
    const url = editId ? 'backend/editar_alteracao.php' : 'backend/save_alteracao.php';

    const cat = document.getElementById('s1_cat').value;
    const tipo = document.getElementById('s1_tipo').value;
    const data = document.getElementById('s1_data').value;
    const desc = document.getElementById('s1_desc').value;

    if (!cat || !tipo || !data || !desc) { Swal.fire('Atenção', 'Preencha Categoria, Tipo, Data e Descrição.', 'warning'); return; }

    const fd = new FormData();
    if (editId) fd.append('s1_edit_id', editId); 
    fd.append('s1_militar_id', idMilitar);
    fd.append('s1_cat', cat);
    fd.append('s1_tipo', tipo);
    fd.append('s1_data', data);
    fd.append('s1_desc', desc);
    fd.append('s1_doc', document.getElementById('s1_doc').value);
    fd.append('s1_dias', document.getElementById('s1_dias').value);

    const file = document.getElementById('s1_file').files[0];
    if (file) fd.append('s1_file', file);

    try {
        const res = await fetch(url, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status === 'sucesso') {
            limparFormS1(); carregarHistoricoS1(idMilitar); 
            Swal.fire({ toast: true, icon: 'success', title: editId ? 'Atualizado!' : 'Salvo!', position: 'top-end', showConfirmButton: false, timer: 1500 });
        } else { Swal.fire('Erro', json.msg || 'Erro desconhecido', 'error'); }
    } catch (e) { console.error(e); }
}

function prepararEdicaoS1(item) {
    document.getElementById('s1_edit_id').value = item.id; 
    document.getElementById('s1_cat').value = item.categoria;
    document.getElementById('s1_tipo').value = item.tipo_detalhe;
    document.getElementById('s1_data').value = item.data_fato;
    document.getElementById('s1_desc').value = item.descricao;
    document.getElementById('s1_doc').value = item.documento_ref || '';
    document.getElementById('s1_dias').value = item.qtd_dias || '';

    const btn = document.getElementById('btnSalvarS1');
    btn.innerHTML = 'Salvar Alteração';
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-warning');
    document.getElementById('btnCancelarS1').classList.remove('d-none');
}

function limparFormS1() {
    document.getElementById('s1_edit_id').value = ''; 
    document.getElementById('s1_cat').value = '';
    document.getElementById('s1_tipo').value = '';
    document.getElementById('s1_data').value = '';
    document.getElementById('s1_desc').value = '';
    document.getElementById('s1_doc').value = '';
    document.getElementById('s1_dias').value = '';
    document.getElementById('s1_file').value = '';

    const btn = document.getElementById('btnSalvarS1');
    btn.innerHTML = 'Adicionar';
    btn.classList.add('btn-primary');
    btn.classList.remove('btn-warning');
    document.getElementById('btnCancelarS1').classList.add('d-none');
}

async function excluirHistoricoS1(idItem, idMilitar) {
    if (!confirm("Excluir registro?")) return;
    try {
        const res = await fetch('backend/excluir_alteracao.php', { method: 'POST', body: JSON.stringify({ id: idItem }) });
        const json = await res.json();
        if (json.status === 'sucesso') {
            carregarHistoricoS1(idMilitar);
            Swal.fire({ toast: true, icon: 'success', title: 'Excluído!', position: 'top-end', showConfirmButton: false, timer: 1500 });
        } else { Swal.fire('Erro', json.msg, 'error'); }
    } catch (e) { console.error(e); }
}

async function concederRecompensa() {
    const idMilitar = document.getElementById('s1_militar_id').value;
    const result = await Swal.fire({ title: 'Conceder Recompensa?', text: "O sistema irá consumir 5 Elogios (FO+) e gerar 1 Dispensa Recompensa automaticamente.", icon: 'question', showCancelButton: true, confirmButtonColor: '#ffc107', cancelButtonColor: '#d33', confirmButtonText: 'Sim, conceder!', cancelButtonText: 'Cancelar' });
    if (!result.isConfirmed) return;
    try {
        const res = await fetch('backend/conceder_recompensa.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ militar_id: idMilitar }) });
        const json = await res.json();
        if (json.status === 'sucesso') {
            await Swal.fire({ title: 'Recompensa Gerada!', text: 'Os elogios foram baixados e a dispensa foi lançada no histórico.', icon: 'success' });
            carregarHistoricoS1(idMilitar);
        } else { Swal.fire('Erro', json.msg || 'Não foi possível processar.', 'error'); }
    } catch (e) { console.error(e); Swal.fire('Erro', 'Falha de comunicação com o servidor.', 'error'); }
}