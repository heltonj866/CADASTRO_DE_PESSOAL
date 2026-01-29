# 🔰 SISMIL - Sistema de Gerenciamento Militar

> **Sistema de Gestão de Efetivo e Trânsito para Organizações Militares (OM)**

O **SISMIL** é uma solução web desenvolvida para a gestão digital do efetivo e controle de trânsito (S2). O sistema centraliza dados de militares, veículos e habilitações (CNH), fornecendo ferramentas de homologação e impressão de selos veiculares com controle hierárquico e visualização rápida de dados.

---

## 🚀 Funcionalidades Principais

### 1. Gestão de Efetivo (Sargenteação)
- Cadastro completo de militares (Dados Pessoais, Endereço, Contatos de Emergência).
- Registro de dados militares (Posto/Grad, Nome de Guerra, Subunidade, Pelotão/Seção, QMG).
- Edição e Exclusão de registros.
- **Carômetro Digital:** Visualização rápida com fotos para identificação.

### 2. Controle de Transporte e Trânsito (2ª Seção - S2)
- **Cadastro de Veículos:** Placa, Modelo, Cor, Validade CRLV.
- **Controle de CNH:** Categoria e Validade da habilitação.
- **Fluxo de Homologação:**
  - O S2 inspeciona os dados do veículo e condutor.
  - Aprovação digital (Homologação) via sistema.
  - O sistema bloqueia a emissão do selo para veículos não homologados.
- **Selo Veicular Automatizado:**
  - Geração de selo para impressão com cores hierárquicas (Ex: Vermelho/Oficiais, Azul/Graduados, Verde/Praças).
  - Brasões da Instituição e da OM integrados.

### 3. Painel de Controle (Dashboard)
- Indicadores em tempo real:
  - Efetivo Total Cadastrado.
  - Tamanho da Frota Veicular da OM.
  - **Pendências do S2:** Contagem automática de veículos aguardando homologação.

### 4. Gestão de Acesso (IAM)
- Login seguro via Identidade/CPF e Senha.
- Perfis de acesso com visões distintas:
  - **Admin:** Acesso total (Gestão de Usuários + Backup BD).
  - **Sargenteação:** Cadastro e Edição de Pessoal.
  - **S2 / Transporte:** Homologação e Selos Veiculares.
  - **Operador (Consulta):** Apenas visualização de fichas (Read-only).

### 5. Relatórios e Buscas
- Busca inteligente por Nome de Guerra, Posto ou QMG.
- Filtro específico de CNH (Categorias A, B, Profissional).
- Exportação de listagens para Excel.
- Impressão de Ficha Individual.

---

## 🛠️ Tecnologias Utilizadas

- **Front-end:** HTML5, CSS3, JavaScript (Vanilla).
- **Framework Visual:** Bootstrap 5.
- **Ícones:** FontAwesome 6.
- **Back-end:** PHP 7.4+ (Nativo, sem frameworks).
- **Banco de Dados:** MySQL / MariaDB.
- **Ambiente:** XAMPP (Apache).

---

## ⚙️ Instalação e Configuração

### Pré-requisitos
- Servidor Web (Apache/Nginx) com PHP.
- Banco de Dados MySQL.

### Passo a Passo

1. **Deploy:**
   Copie os arquivos para a pasta pública do servidor web.

2. **Banco de Dados:**
   - Crie um banco de dados (ex: `sismil_db`).
   - Importe o script SQL fornecido na pasta `database/` para criar a estrutura das tabelas.

3. **Conexão:**
   - Configure o arquivo `backend/db_connect.php` com as credenciais do seu ambiente local ou servidor.

4. **Personalização Visual:**
   - Adicione os brasões na pasta `uploads/` para correta geração dos selos:
     - `brasao.png` (Brasão da Unidade/OM).
     - `brasao_eb.png` (Brasão da Instituição Superior).

---

## 🔐 Regras de Negócio e Perfis

| Perfil | Cadastro de Pessoal | Edição de Dados | Homologação Veicular | Impressão de Selo |
| :--- | :---: | :---: | :---: | :---: |
| **Admin** | ✅ | ✅ | ❌ | ✅ |
| **Sargenteação** | ✅ | ✅ | ❌ | ❌ |
| **S2 / Transp.** | ❌ | ❌ | ✅ | ✅ |
| **Consulta** | ❌ | ❌ | ❌ | ❌ |

> **Observação:** O perfil S2 possui visão de "Auditoria". Ele não altera dados pessoais, apenas valida as informações de trânsito inseridas pela Sargenteação e libera a emissão do selo.

---

## 📂 Estrutura do Projeto

/
├── backend/          # API e Lógica PHP
├── css/              # Estilos
├── js/               # Scripts do Front-end
├── uploads/          # Armazenamento de Fotos e Brasões
├── index.html        # Interface do Usuário
└── README.md         # Documentação

---

*Versão 1.0 - Uso Interno*