<div align="center">

# 🚀 CARDOXIS — Gestão Inteligente de Frotas

![CARDOXIS Banner](https://cardoxis.ct.ws/public/assets/img/logo/favicon.png)

<br/>

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)](https://cardoxis.com)
[![Status](https://img.shields.io/badge/Status-v1.0.0-success?style=for-the-badge)](#-roadmap)

<p align="center">
  <b>Plataforma SaaS moderna para automação, análise preditiva e otimização de frotas operacionais.</b>
</p>

[Visitar Website](https://cardoxis.com) • [Reportar Bug](https://github.com/cardoxis/cardoxis/issues) • [Solicitar Feature](https://github.com/cardoxis/cardoxis/issues)

</div>

---

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Arquitetura do Sistema](#-arquitetura-do-sistema)
- [Instalação e Configuração](#-instalação-e-configuração)
- [Configuração do Banco de Dados](#-configuração-do-banco-de-dados)
- [Estrutura de Diretórios](#-estrutura-de-diretórios)
- [Fluxo do Sistema](#-fluxo-do-sistema)
- [Credenciais de Teste](#-credenciais-de-teste)
- [Segurança](#-segurança)
- [Roadmap](#-roadmap)
- [FAQ](#-faq)
- [Contribuição](#-contribuição)
- [Licença](#-licença)
- [Contato](#-contato)

---

## 📖 Sobre o Projeto

O **CARDOXIS** nasceu da necessidade de simplificar a gestão de frotas veiculares, transformando um processo tradicionalmente burocrático em uma operação automatizada e orientada a dados.

A plataforma combina:
* 🤖 **Inteligência Artificial:** Análise preditiva e automação de processos.
* 📄 **OCR Avançado:** Extração automática de dados de documentos e faturas.
* 📊 **Dashboards Interativos:** Indicadores em tempo real para tomadas de decisão rápidas.
* 🔔 **Alertas Inteligentes:** Prevenção proativa de vencimentos e manutenções.

### 🎯 Objetivo
Capacitar empresas a gerenciar suas frotas de forma inteligente, reduzindo **custos operacionais em até 40%** e aumentando a **eficiência em 60%**.

---

## ✨ Funcionalidades

| Módulo | Recursos Principais |
| :--- | :--- |
| **🔐 Autenticação & Segurança** | • Validação de senhas fortes<br>• Login com proteção CSRF e Rate Limiting<br>• Sessões persistentes e "Remember Me"<br>• Proteção contra brute-force |
| **📊 Dashboard** | • Indicadores de desempenho em tempo real<br>• Gráficos interativos (Chart.js)<br>• Feed de atividades recentes<br>• Alertas preventivos e atalhos rápidos |
| **🚗 Gestão de Veículos** | • Cadastro e histórico detalhado<br>• Vínculo com manutenções e documentos<br>• Controle de status em tempo real |
| **📄 Gestão de Documentos** | • Leitura automática via OCR e IA<br>• Categorização inteligente<br>• Alertas automatizados de vencimento |
| **🔧 Manutenção** | • Agendamento preventivo e corretivo<br>• Histórico completo de intervenções<br>• Controle e projeção de custos |
| **📈 Relatórios & Exportação** | • Relatórios personalizáveis<br>• Exportação nativa em CSV e PDF |
| **📱 Interface & UX** | • Layout 100% responsivo<br>• Dark Mode automático |

---

## 🛠 Tecnologias Utilizadas

### **Backend**
| Tecnologia | Versão | Aplicação |
| :--- | :---: | :--- |
| **PHP** | `8.0+` | Linguagem principal do servidor |
| **MySQL** | `5.7+` | Banco de dados relacional |
| **PDO** | - | Camada de abstração e segurança do banco |
| **Composer** | `2.0+` | Gerenciamento de dependências PHP |

### **Frontend**
| Tecnologia | Versão | Aplicação |
| :--- | :---: | :--- |
| **HTML5 & CSS3** | - | Estrutura semântica e estilização |
| **JavaScript** | `ES6+` | Lógica do client-side e interatividade |
| **Chart.js** | `4.4.0` | Visualização gráfica de dados |
| **Font Awesome** | `6.4.0` | Ícones vetoriais |
| **Google Fonts** | Inter | Tipografia principal |

---

## 🏗 Arquitetura do Sistema

```
┌─────────────────────────────────────────────────────────────┐
│ USER INTERFACE (UI)                                         │
│ Landing Page → Login → Dashboard → Módulos do Sistema       │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│ APPLICATION LAYER                                           │
│ PHP Controllers (index, login, register, dashboard, logout) │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│ BUSINESS LAYER                                              │
│ User Model, Security Core, Database Engine                  │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│ DATA LAYER (MySQL)                                          │
│ users, vehicles, documents, maintenance, sessions           │
└─────────────────────────────────────────────────────────────┘
```

---

## 💻 Instalação e Configuração

### **Pré-requisitos**
* PHP >= 8.0
* MySQL >= 5.7
* Servidor Web (Apache/Nginx)
* Composer (Opcional)

### **Passos para Instalação**

1. **Clonar o repositório:**
   ```bash
   git clone https://github.com/cardoxis/cardoxis.git
   cd cardoxis
   ```

2. **Configurar as Variáveis de Ambiente:**
   ```bash
   cp .env.example .env
   ```
   Ajuste as configurações no seu arquivo `.env`:
   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=cardoxis_db
   DB_USER=root
   DB_PASSWORD=your_password

   # Application Configuration
   APP_NAME=CARDOXIS
   APP_ENV=development
   APP_DEBUG=true
   APP_URL=http://localhost/cardoxis

   # Security
   SECURITY_KEY=your_security_key
   ```

3. **Configuração do Servidor Web:**

   * **Apache (`.htaccess`):**
     ```apache
     <IfModule mod_rewrite.c>
         RewriteEngine On
         RewriteRule ^$ public/index.php [L]
         RewriteCond %{REQUEST_FILENAME} !-f
         RewriteCond %{REQUEST_FILENAME} !-d
         RewriteRule ^(.*)$ public/index.php [QSA,L]
     </IfModule>
     ```

   * **Nginx (`nginx.conf`):**
     ```nginx
     location / {
         try_files $uri $uri/ /index.php?$query_string;
     }
     ```

4. **Permissões de Diretório (Linux/macOS):**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/cardoxis
   sudo chmod -R 755 /var/www/html/cardoxis/storage
   ```

---

## 🗄 Configuração do Banco de Dados

### **Opção 1: Via Linha de Comando (CLI)**
```bash
# Acessar o MySQL
mysql -u root -p

# Criar banco e importar schema
CREATE DATABASE cardoxis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cardoxis_db;
SOURCE /caminho/para/cardoxis/database.sql;
```

### **Opção 2: Via phpMyAdmin**
1. Acesse `http://localhost/phpmyadmin`
2. Crie a base de dados `cardoxis_db`.
3. Vá na aba **Importar**, selecione o arquivo `database.sql` e execute.

### **Opção 3: Script de Instalação Automática**
Crie e execute temporariamente o arquivo `setup.php`:
```php
<?php
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents('database.sql');
    $db->exec($sql);
    echo "✅ Banco de dados configurado com sucesso!";
} catch (Exception $e) {
    echo "❌ Erro ao configurar banco: " . $e->getMessage();
}
```

---

## 📁 Estrutura de Diretórios

```text
cardoxis/
├── .htaccess                      # Reescreve rotas para o Apache
├── README.md                      # Documentação oficial
├── database.sql                   # Schema e dados iniciais do banco
│
├── public/                        # Raiz pública do servidor
│   ├── index.php                  # Landing Page
│   ├── login.php                  # Autenticação de usuários
│   ├── register.php               # Cadastro de contas
│   ├── dashboard.php              # Painel principal
│   ├── logout.php                 # Encerramento de sessão
│   └── assets/                    # Recurso estáticos públicos
│       ├── css/
│       ├── js/
│       └── img/
│
├── views/                         # Camada de Visualização (Templates)
│   ├── layout/                    # Layouts globais
│   └── dashboard/                 # Componentes do Dashboard
│
├── app/                           # Lógica de Aplicação (Protegida)
│   ├── core/                      # Classes base (Database, Security)
│   └── models/                    # Modelos de dados (User, Vehicle, etc.)
│
└── storage/                       # Arquivos privados do sistema
    ├── logs/                      # Registro de erros e eventos
    ├── cache/                     # Cache de performance
    └── uploads/                   # Uploads seguros
```

---

## 🔄 Fluxo do Sistema

### **Rotas Principais**

| Rota | Descrição | Autenticação Requerida |
| :--- | :--- | :---: |
| `/` | Landing Page informativa | ❌ |
| `/register` | Cadastro de nova conta | ❌ |
| `/login` | Acesso à plataforma | ❌ |
| `/dashboard` | Painel de controle e métricas | ✅ |
| `/vehicles` | Gestão da frota veicular | ✅ |
| `/documents` | Processamento de documentos via IA | ✅ |
| `/maintenance`| Agendamento e custos de manutenção | ✅ |
| `/reports` | Relatórios e exportação de dados | ✅ |
| `/settings` | Configurações de perfil e sistema | ✅ |
| `/logout` | Encerramento seguro da sessão | ✅ |

---

## 🔑 Credenciais de Teste

| Perfil | E-mail | Senha | Nível de Acesso |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@cardoxis.com` | `Admin@123` | Acesso Total |
| **Usuário Demo** | `demo@cardoxis.com` | `Demo@123` | Leitura e Operação |

---

## 🔒 Segurança

O CARDOXIS foi projetado com foco em boas práticas de segurança cibernética:

* ✅ **CSRF Protection:** Tokens únicos para validação de formulários.
* ✅ **Criptografia de Senhas:** Hashing via Bcrypt com custo 12.
* ✅ **Rate Limiting:** Bloqueio temporário após 5 tentativas de login com erro.
* ✅ **Session Hardening:** Cookies configurados como `HttpOnly`, `SameSite` e `Secure`.
* ✅ **Prevenção SQLi & XSS:** Consultas preparadas (PDO) e higienização estrita de inputs.

> **💡 Recomendações para Ambientes de Produção:**
> - Forçar conexões HTTPS/TLS.
> - Configurar Web Application Firewall (WAF).
> - Configurar rotinas diárias de backup para a base de dados.

---

## 📊 Roadmap

- [x] **v1.0.0 (Atual)**
  - Auth completo, Dashboard com Chart.js, Módulos de Veículos, Documentos e Manutenções.
- [ ] **v1.1.0 (Em Desenvolvimento)**
  - RESTful API para integrações de terceiros.
  - Aplicativo Mobile nativo em React Native.
  - Integração com Google Maps para geolocalização.
- [ ] **v2.0.0 (Planejado)**
  - Módulo de Machine Learning para manutenção preditiva.
  - Telemetria e integração IoT com sensores veiculares.

---

## ❓ FAQ

<details>
<summary><b> O sistema é totalmente compatível com dispositivos móveis?</b></summary>
<br/>
Sim! A interface é 100% adaptativa e otimizada para smartphones, tablets e desktops.
</details>

<details>
<summary><b> Como funciona a exportação de dados?</b></summary>
<br/>
Dentro do módulo de relatórios, você pode filtrar os dados desejados e baixá-los instantaneamente nos formatos CSV ou PDF.
</details>

<details>
<summary><b> Posso utilizar o projeto para fins comerciais?</b></summary>
<br/>
O código-fonte é proprietário. Consulte a seção de Licença para maiores informações sobre contratos comerciais.
</details>

---

## 🤝 Contribuição

Contribuições para o desenvolvimento do projeto são muito bem-vindas!

1. Faça o **Fork** do projeto
2. Crie uma Branch para sua Feature:
   ```bash
   git checkout -b feature/minha-nova-feature
   ```
3. Faça o **Commit** das alterações (sigamos o padrão PSR-12):
   ```bash
   git commit -m 'feat: adiciona nova funcionalidade de relatórios'
   ```
4. Envie as alterações para o repositório remoto:
   ```bash
   git push origin feature/minha-nova-feature
   ```
5. Abra um **Pull Request** para análise.

---

## 📄 Licença

Direitos autorais reservados © CARDOXIS.

* ❌ Proibida reprodução comercial sem expressa autorização.
* ❌ Proibida redistribuição do código-fonte original.
* ✅ Permitido uso educacional e de testes acadêmicos com devida atribuição.

Para licenciamento corporativo ou comercial, solicite atendimento via contato.

---

## 📞 Contato
<div align="left">

🌐 cardoxis.ct.ws
📞 cardoxis.ct.ws/contact

</div>
