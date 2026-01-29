// ARQUIVO: js/script.js (Versão Final Corrigida - Botões no Lugar)

const postos = ["Cel", "Ten Cel", "Maj", "Cap", "1º Ten", "2º Ten", "Asp", "Subten", "1º Sgt", "2º Sgt", "3º Sgt", "Cb", "Sd EP", "Sd EV", "SC"];
const qmgs = {
    "Carreira": ["04 - Engenharia", "01 - Infantaria", "05 - Comunicações", "06 - Mat Bel", "07 - Intendência", "08 - Saúde"],
    "Temporários": ["QM 05-01 - Combatente", "QM 05-15 - Auxiliar de Topógrafo", "QM 05-23 - Pessoal de Construções e Instalações", "QM 07-01 - Infantaria-Combatente", "QM 11-71 - Comunicações", "QM 08-33 - Auxliar de Saúde", "QM 09-46 - Mec Arm", "QM 09-47 - Mec Eletricista", "QM 09-50 - Mec Operador", "QM 09-51 - Mec Viatura Auto", "QM 10-61 - Pessoal de Aprovisionamento", "QM 00-10 - Corneteiro/Clarim", "0000 - Não Qualificado"]
};
let currentUserRole = '';

console.log("✅ Sistema Carregado: Versão Final Corrigida");

// --- 1. MÁSCARAS ---
document.addEventListener('input', function(e) {
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
    if (el.name === 'cep') el.value = el.value.replace(/\D/g, "").substring(0, 8).replace(/^(\d{5})(\d{3})/, "$1-$2");
    if (el.name === 'placa') el.value = el.value.toUpperCase().replace(/[^A-Z0-9]/g, "").substring(0, 8);
});

// --- 2. INICIALIZAÇÃO ---
document.addEventListener('DOMContentLoaded', () => {
    popularSelects();
    verificarSessao();

    const formLogin = document.getElementById('formLogin');
    if (formLogin) formLogin.addEventListener('submit', handleLogin);

    // Listener para Salvar (apenas quando o botão salvar existe e está habilitado)
    const formMilitar = document.getElementById('militaryForm');
    if (formMilitar) {
        formMilitar.addEventListener('submit', function(e) {
            e.preventDefault();
            // Verifica se o botão de salvar está visível/ativo (para não salvar se for S2 clicando enter)
            const btnSave = document.querySelector('#formFooterButtons button[type="submit"]');
            
            // Só salva se o botão de salvar existir (Admin/Sargenteação)
            if(btnSave) {
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
    if(tabNovo) tabNovo.addEventListener('click', resetarFormularioUsuario);
});

// --- 3. FUNÇÕES PRINCIPAIS ---
function getEl(k) { return document.getElementById(k) || document.querySelector(`[name="${k}"]`); }
function setVal(k, v) { const el = getEl(k); if(el) el.value = v || ''; }

// LOGIN
async function handleLogin(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const txt = btn.innerText; btn.innerText="Verificando..."; btn.disabled=true;
    try {
        const fd = new FormData(e.target);
        const res = await fetch('backend/login.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({identidade:fd.get('usuario'), senha:e.target.querySelectorAll('input')[1].value})
        });
        const json = await res.json();
        if(json.status==='sucesso') {
            document.getElementById('loginScreen').classList.add('hidden');
            document.getElementById('appScreen').classList.remove('hidden');
            currentUserRole = json.role;
            aplicarPermissoes(json.role);
            atualizarDashboard();
        } else Swal.fire('Erro', json.msg, 'error');
    } catch(err){console.error(err);} finally{btn.innerText=txt;btn.disabled=false;}
}

// SALVAR (Admin/Sargenteação)
async function salvarMilitar() {
    const fd = new FormData(document.getElementById('militaryForm'));
    const chk = document.getElementById('homologado');
    if(chk) fd.append('homologado', chk.checked ? '1' : '0');
    
    const btn = document.querySelector('#militaryForm button[type="submit"]');
    if(btn) { btn.innerText='Salvando...'; btn.disabled=true; }

    try {
        const res = await fetch('backend/save_militar.php', {method:'POST', body:fd});
        const json = await res.json();
        
        if(json.status==='sucesso') {
            Swal.fire({
                title: 'Sucesso!',
                text: 'Dados salvos corretamente.',
                icon: 'success',
                timer: 1500, // Fecha sozinho em 1.5s
                showConfirmButton: false
            });

            limparFormulario();     // Limpa/Fecha o form
            atualizarDashboard();   // Atualiza os números lá em cima
            atualizarListagem();    // <--- O PULO DO GATO: Atualiza a tabela
            
        } else {
            Swal.fire('Erro', json.msg, 'error');
        }
    } catch(e){
        console.error(e);
        Swal.fire('Erro','Falha técnica ao salvar.','error');
    } finally { 
        if(btn) { btn.innerText='Salvar Dados'; btn.disabled=false; } 
    }
}

// --- CORE: CARREGAR DADOS NA FICHA ---
async function carregarMilitarNoForm(id, modo) {
    // 1. Reset visual
    limparFormulario(); 
    
    try {
        const res = await fetch(`backend/get_militar.php?id=${id}&v=${Date.now()}`);
        const json = await res.json();
        if(json.status !== 'sucesso') throw new Error(json.msg);
        
        const d = json.dados;
        
        // 2. Preenchimento de Campos
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
        setVal('tipo_veic', d.tipo_veiculo); setVal('placa', d.placa); setVal('modelo', d.modelo);
        setVal('cor', d.cor); setVal('validade_crlv', d.validade_crlv);

        // Checkbox Visual (apenas visual)
        const chkHomolog = document.getElementById('homologado');
        if(chkHomolog) chkHomolog.checked = (d.homologado == 1);
        atualizarBadgeVisual(d.homologado == 1);
        
        if(d.foto_path) document.getElementById('imgPreview').src = `uploads/${d.foto_path}`;

        // 3. Exibir o Formulário
        document.getElementById('fullRegistrationCard').classList.remove('hidden');
        document.getElementById('fullRegistrationCard').scrollIntoView({ behavior: 'smooth' });

        // 4. LÓGICA DE BOTÕES E PERFIL
        const badge = document.getElementById('formModeBadge');
        const footerBtns = document.getElementById('formFooterButtons');
        const role = currentUserRole ? currentUserRole.toLowerCase() : '';
        const ehS2 = (role === 's2' || role === 'transporte');

        // Layout Padrão de Botões (Admin/Sargenteação)
        let htmlButtonsPadrao = `
            <button type="button" id="btnExcluir" class="btn btn-outline-danger ${currentUserRole === 'admin' ? '' : 'd-none'}" onclick="excluirMilitar()">
                <i class="fas fa-trash-alt me-1"></i> Excluir
            </button>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2" onclick="limparFormulario()">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Salvar Dados</button>
            </div>
        `;

        if(ehS2) {
            // --- MODO S2: INSPEÇÃO E HOMOLOGAÇÃO ---
            badge.innerText = "Inspeção Veicular (S2)"; 
            badge.className = "badge bg-warning text-dark";
            
            // Foca na aba de veículos automaticamente
            try { const tab = document.querySelector('button[data-bs-target="#vehicle"]'); if(tab) new bootstrap.Tab(tab).show(); } catch(e){}

            // Monta os botões Especiais do S2
            let buttonsS2 = `<div class="w-100 d-flex justify-content-between align-items-center">`;
            buttonsS2 += `<button type="button" class="btn btn-secondary" onclick="limparFormulario()">Fechar Ficha</button>`;
            buttonsS2 += `<div>`;
            
            if (d.placa && d.placa.trim() !== "") {
                if (d.homologado == 1) {
                    // JÁ HOMOLOGADO
                    buttonsS2 += `
                    <button type="button" class="btn btn-success me-2 fw-bold" onclick="toggleHomologacaoForm(${d.id})">
                        <i class="fas fa-check-double me-1"></i> Homologado
                    </button>
                    <button type="button" class="btn btn-dark fw-bold" onclick="imprimirSelo(${d.id})">
                        <i class="fas fa-print me-1"></i> Imprimir Selo
                    </button>`;
                } else {
                    // PENDENTE
                    buttonsS2 += `
                    <button type="button" class="btn btn-warning me-2 fw-bold" onclick="toggleHomologacaoForm(${d.id})">
                        <i class="fas fa-stamp me-1"></i> HOMOLOGAR VEÍCULO
                    </button>
                    <button type="button" class="btn btn-secondary" disabled title="Aprovação necessária">
                        <i class="fas fa-lock me-1"></i> Selo Bloqueado
                    </button>`;
                }
            } else {
                buttonsS2 += `<span class="badge bg-light text-secondary border p-2">Veículo não cadastrado</span>`;
            }
            buttonsS2 += `</div></div>`;

            // Aplica os botões S2
            footerBtns.innerHTML = buttonsS2;

            // Bloqueia edição de campos para o S2 (apenas leitura)
            const inputs = document.querySelectorAll('#militaryForm input, #militaryForm select');
            inputs.forEach(el => el.setAttribute('readonly', true)); 
            // Para selects, o readonly não funciona bem em alguns browsers, então desabilitamos o pointer events ou usamos disabled
            document.querySelectorAll('#militaryForm select').forEach(s => s.style.pointerEvents = 'none');

        } else {
            // --- MODO ADMIN/SARGENTEAÇÃO ---
            badge.innerText = "Edição de Cadastro"; 
            badge.className = "badge bg-primary";
            footerBtns.innerHTML = htmlButtonsPadrao;
            
            // Destrava campos
            const inputs = document.querySelectorAll('#militaryForm input, #militaryForm select');
            inputs.forEach(el => el.removeAttribute('readonly'));
            document.querySelectorAll('#militaryForm select').forEach(s => s.style.pointerEvents = 'auto');
            
            if(getEl('cpf')) getEl('cpf').setAttribute('readonly', true); // CPF nunca muda
        }

    } catch (e) { 
        console.error(e);
        Swal.fire('Erro', 'Não foi possível carregar os dados.', 'error'); 
    }
}

// --- TOGGLE DENTRO DO FORMULÁRIO (S2) ---
async function toggleHomologacaoForm(id) {
    // Removemos o "confirm" padrão chato e usamos algo mais fluido ou direto, 
    // mas se quiser manter o confirm, tudo bem. Vamos manter pela segurança.
    if(!confirm("Confirma a alteração do status de homologação?")) return;

    try {
        const res = await fetch('backend/toggle_homolog.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id })
        });
        const json = await res.json();
        
        if(json.status === 'sucesso') {
            // 1. Atualiza a ficha aberta (botoes mudam de cor na hora)
            carregarMilitarNoForm(id, 'reload'); 
            
            // 2. Atualiza os números do Dashboard
            atualizarDashboard(); 
            
            // 3. Atualiza a lista de pesquisa lá no fundo
            atualizarListagem(); 
            
            // Opcional: Um feedback visual rápido (Toast)
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
            });
            Toast.fire({ icon: 'success', title: 'Status atualizado!' });

        } else {
            alert("Erro: " + json.msg);
        }
    } catch(e) { console.error(e); alert("Erro de conexão"); }
}

// --- BUSCA (LISTA DE CARDS) ---
async function realizarBusca(e, tipo) {
    if(e) e.preventDefault();
    const area = document.getElementById('resultsArea');
    area.innerHTML = '<div class="text-center p-3">Buscando...</div>';
    area.classList.remove('hidden');

    let url = `backend/search.php?tipo_busca=${tipo}`;
    if (tipo === 'geral') {
        const t = document.querySelector('#searchFormGeneral input[type="text"]').value;
        const p = document.getElementById('searchPosto').value;
        const q = document.getElementById('searchQMG').value;
        url += `&termo=${t}&posto=${p}&qmg=${q}`;
    } else {
        const f = document.querySelector('input[name="filtroCnh"]:checked')?.value || 'TODAS';
        url += `&filtro_cnh=${f}`;
    }

    try {
        const res = await fetch(url);
        const json = await res.json();
        area.innerHTML = '';

        if(json.status==='sucesso' && json.dados.length > 0) {
            document.getElementById('resultsCount').innerText = json.dados.length + " encontrados";
            json.dados.forEach(m => {
                const foto = m.foto_path ? `uploads/${m.foto_path}` : 'assets/sem_foto.png';
                
                // 1. Botão de Ação (Depende do Perfil)
                let btnAcao = '';
                
                if(['admin', 'sargenteacao'].includes(currentUserRole.toLowerCase())) {
                    // Admin/Sarg: Editar (Azul)
                    btnAcao = `<button class="btn btn-sm btn-outline-primary w-100 mb-1" onclick="carregarMilitarNoForm(${m.id}, 'edit')">
                                    <i class="fas fa-edit me-1"></i> Editar
                               </button>`;
                } 
                else if(['s2', 'transporte'].includes(currentUserRole.toLowerCase())) {
                    // S2: Inspecionar (Amarelo)
                    btnAcao = `<button class="btn btn-sm btn-warning w-100 mb-1 fw-bold" onclick="carregarMilitarNoForm(${m.id}, 'homolog')">
                                    <i class="fas fa-search me-1"></i> Inspecionar
                               </button>`;
                }

                // 2. Botão Ver Ficha (Sempre presente para todos)
                const btnVerFicha = `<button class="btn btn-sm btn-info text-white w-100" onclick="verDetalhesMilitar(${m.id})">
                                        <i class="fas fa-id-card me-1"></i> Ver Ficha
                                     </button>`;
                
                area.innerHTML += `
                <div class="col-md-3">
                    <div class="card h-100 shadow-sm">
                        <div style="height:200px;overflow:hidden;background:#f0f0f0;"><img src="${foto}" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='assets/sem_foto.png'"></div>
                        <div class="card-body text-center p-2">
                            <h6 class="fw-bold m-0">${m.posto_grad} ${m.nome_guerra}</h6>
                            <small class="text-muted">${m.subunidade}</small>
                            <div class="mt-2">
                                ${btnAcao}
                                ${btnVerFicha}
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        } else {
            area.innerHTML = '<div class="alert alert-warning text-center">Nenhum registro.</div>';
            document.getElementById('resultsCount').innerText = "0";
        }
    } catch(err) { area.innerHTML = '<div class="alert alert-danger">Erro na busca.</div>'; }
}

// VISUALIZAR FICHA (Read-Only Modal)
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
            
            document.getElementById('visGuerra').innerText = txt(d.nome_guerra);
            document.getElementById('visPosto').innerText = txt(d.posto_grad);
            document.getElementById('visNumero').innerText = txt(d.numero);
            document.getElementById('visNomeCompleto').innerText = txt(d.nome_completo);
            document.getElementById('visIdtMil').innerText = txt(d.idt_militar);
            document.getElementById('visCpf').innerText = txt(d.identidade);
            document.getElementById('visNascimento').innerText = fmt(d.dt_nascimento);
            document.getElementById('visSangue').innerText = txt(d.tipo_sanguineo);
            document.getElementById('visSu').innerText = txt(d.subunidade);
            document.getElementById('visPelotaoSecao').innerText = `${d.pelotao||''} / ${d.secao||''}`;
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

// OUTRAS AUXILIARES
function popularSelects() {
    const sp=document.getElementById('selectPosto'), sc=document.getElementById('searchPosto');
    postos.forEach(p=>{if(sp)sp.add(new Option(p,p));if(sc)sc.add(new Option(p,p))});
    const sq=document.getElementById('selectQMG'), sqc=document.getElementById('searchQMG');
    const add=(el)=>{for(const[g,l] of Object.entries(qmgs)){const o=document.createElement('optgroup');o.label=g;l.forEach(q=>o.appendChild(new Option(q,q)));el.appendChild(o)}};
    if(sq)add(sq); if(sqc)add(sqc);
}
function verificarSessao() { fetch('backend/check_session.php').then(r=>r.json()).then(j=>{if(j.status==='logado'){document.getElementById('loginScreen').classList.add('hidden');document.getElementById('appScreen').classList.remove('hidden');currentUserRole=j.role;aplicarPermissoes(j.role);atualizarDashboard();}})}

// APLICAR PERMISSÕES
// APLICAR PERMISSÕES (Corrigido: Esconde cadastro para Consulta e S2)
function aplicarPermissoes(role) {
    const adminBtn = document.getElementById('btnAdminUsers');
    const display = document.getElementById('displayUserRole');
    const formCard = document.getElementById('fullRegistrationCard'); // O formulário de cadastro

    if(display) display.innerText = role.toUpperCase();
    
    // Normaliza para minúsculo para evitar erros
    const r = role ? role.toLowerCase() : '';

    // 1. ADMIN
    if (r === 'admin') {
        if(adminBtn) adminBtn.classList.remove('hidden');
        if(formCard) formCard.classList.remove('hidden'); // Admin vê o cadastro sempre
        carregarListaUsuarios(); 
    } 
    // 2. SARGENTEAÇÃO
    else if (r === 'sargenteacao') {
        if(adminBtn) adminBtn.classList.add('hidden');
        if(formCard) formCard.classList.remove('hidden'); // Sargenteação vê o cadastro sempre
    }
    // 3. S2 e USUÁRIO COMUM (Consulta)
    else {
        // Esconde botão de admin
        if(adminBtn) adminBtn.classList.add('hidden');
        
        // IMPORTANTE: Esconde o formulário de cadastro inicial
        // (O S2 só verá o formulário quando clicar em "Inspecionar" na busca)
        if(formCard) formCard.classList.add('hidden');
    }
}

function atualizarBadgeVisual(check) {
    const b=document.getElementById('vehicleStatusBadge');
    if(b){b.className=check?"status-badge status-ok":"status-badge status-analise";b.innerHTML=check?'<i class="fas fa-check-circle"></i> HOMOLOGADO':'<i class="fas fa-clock"></i> PENDENTE';}
}
function previewImage(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function(e) { document.getElementById('imgPreview').src = e.target.result; }; reader.readAsDataURL(input.files[0]); } }
function atualizarDashboard() { fetch('backend/dashboard_stats.php').then(r=>r.json()).then(j=>{if(j.status==='sucesso'){document.getElementById('dashMilitares').innerText=j.militares;document.getElementById('dashVeiculos').innerText=j.veiculos;document.getElementById('dashPendentes').innerText=j.pendentes;document.getElementById('dashboardPanel').classList.remove('hidden');}})}
async function realizarLogout() { await fetch('backend/logout.php'); location.reload(); }

// Gestão de Usuários (Apenas Admin)
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
async function excluirUsuario(id) { if(confirm('Apagar usuário?')) { await fetch('backend/delete_user.php',{method:'POST',body:JSON.stringify({id})}); carregarListaUsuarios(); } }
async function criarUsuario(e) { e.preventDefault(); const fd=new FormData(document.getElementById('formCreateUser')); const url=document.getElementById('edit_id').value?'backend/update_user.php':'backend/create_user.php'; await fetch(url,{method:'POST',body:JSON.stringify(Object.fromEntries(fd))}); document.getElementById('formCreateUser').reset(); carregarListaUsuarios(); }
function resetarFormularioUsuario() { document.getElementById('formCreateUser').reset(); document.getElementById('edit_id').value=''; document.querySelector('[name="new_user_idt"]').removeAttribute('readonly'); document.querySelector('#formCreateUser button[type="submit"]').innerHTML='<i class="fas fa-save me-1"></i> Salvar Dados'; }
function prepararEdicao(id, p, g, s, i, r) { new bootstrap.Tab(document.querySelector('button[data-bs-target="#tab-novo"]')).show(); document.getElementById('edit_id').value=id; const f=document.forms['formCreateUser']; f.new_user_posto.value=p; f.new_user_guerra.value=g; f.new_user_subunidade.value=s; f.new_user_idt.value=i; f.new_user_role.value=r; f.new_user_idt.setAttribute('readonly',true); document.querySelector('#formCreateUser button[type="submit"]').innerHTML='<i class="fas fa-sync me-1"></i> Atualizar'; }
function exportarParaExcel() { const tipo = document.querySelector('#searchTabs .active') ? (document.querySelector('#searchTabs .active').id === 'li-tab-cnh' ? 'cnh' : 'geral') : 'geral'; window.open(`backend/export_excel.php?tipo_busca=${tipo}`, '_blank'); }
// Função Corrigida: Fecha a ficha com segurança
function limparFormulario() {
    const form = document.getElementById('militaryForm');
    if(form) form.reset();
    
    // Limpa campos específicos com verificação de existência
    if(document.getElementById('militarId')) document.getElementById('militarId').value='';
    if(document.getElementById('imgPreview')) document.getElementById('imgPreview').src='assets/sem_foto.png';
    
    // --- CORREÇÃO DO ERRO ---
    // Só tenta esconder o botão Excluir SE ele existir na tela
    const btnExcluir = document.getElementById('btnExcluir');
    if(btnExcluir) btnExcluir.classList.add('d-none');
    // ------------------------

    // Esconde o card principal
    const card = document.getElementById('fullRegistrationCard');
    if(card) card.classList.add('hidden'); 
    
    // Restaura botões padrão (Admin/Sargenteação) para a próxima vez
    const footerBtns = document.getElementById('formFooterButtons');
    if(footerBtns) {
        footerBtns.innerHTML = `
            <button type="button" id="btnExcluir" class="btn btn-outline-danger d-none" onclick="excluirMilitar()">
                <i class="fas fa-trash-alt me-1"></i> Excluir Cadastro
            </button>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2" onclick="limparFormulario()">Limpar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Salvar Dados
                </button>
            </div>
        `;
    }
    
    // Opcional: Rola a tela de volta para o topo ou para a busca
    const resultsArea = document.getElementById('resultsArea');
    if(resultsArea && !resultsArea.classList.contains('hidden')) {
        resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
function imprimirSelo(id) {
    const width = 600; const height = 400;
    const left = (screen.width - width) / 2; const top = (screen.height - height) / 2;
    window.open(`backend/print_selo.php?id=${id}`, 'ImprimirSelo', `width=${width},height=${height},top=${top},left=${left},scrollbars=yes`);
}

// Função auxiliar para atualizar a lista sem recarregar a página
function atualizarListagem() {
    const areaResultados = document.getElementById('resultsArea');
    
    // Só atualiza se a lista de resultados estiver visível
    if (areaResultados && !areaResultados.classList.contains('hidden')) {
        // Verifica qual aba está ativa (Geral ou CNH)
        const tabCnh = document.getElementById('tab-cnh');
        const tipo = (tabCnh && tabCnh.classList.contains('active')) ? 'cnh' : 'geral';
        
        // Chama a busca novamente silenciosamente (passando null no evento)
        realizarBusca(null, tipo);
    }
}

async function excluirMilitar() {
    const id = document.getElementById('militarId').value;
    if(!id) return;

    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: "Essa ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const res = await fetch('backend/delete_militar.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            const json = await res.json();

            if(json.status === 'sucesso') {
                Swal.fire('Excluído!', 'O registro foi removido.', 'success');
                limparFormulario();
                atualizarDashboard();
                atualizarListagem(); // <--- Atualiza a lista
            } else {
                Swal.fire('Erro', json.msg, 'error');
            }
        } catch(e) {
            Swal.fire('Erro', 'Erro de conexão.', 'error');
        }
    }
}