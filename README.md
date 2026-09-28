# 🚀 CARDOXIS - Gestão Inteligente de Frotas

![CARDOXIS Banner](https://cardoxis.com/assets/img/og-image.jpg)

**CARDOXIS** é uma plataforma SaaS (Software as a Service) de gestão de frotas que utiliza Inteligência Artificial para otimizar operações, reduzir custos e aumentar a eficiência. Desenvolvida para empresas que buscam modernizar sua gestão de veículos, documentos e manutenções.

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
- [Contribuição](#-contribuição)
- [Licença](#-licença)
- [Contato](#-contato)

---

## 📖 Sobre o Projeto

O CARDOXIS nasceu da necessidade de simplificar a gestão de frotas veiculares, um processo que tradicionalmente é complexo e burocrático. Nossa plataforma combina:

- **Inteligência Artificial** para análise preditiva e automação
- **OCR (Reconhecimento Óptico de Caracteres)** para extração automática de dados
- **Dashboards interativos** para tomada de decisão estratégica
- **Alertas inteligentes** para prevenção de problemas

### 🎯 **Objetivo**
Capacitar empresas a gerenciar sua frota de forma inteligente, reduzindo custos operacionais em até 40% e aumentando a eficiência em 60%.

---

## ✨ Funcionalidades

### 🔐 **Autenticação e Segurança**
- ✅ Registro de usuários com validação de senha forte
- ✅ Login seguro com proteção CSRF
- ✅ "Remember Me" (manter sessão)
- ✅ Recuperação de senha (em desenvolvimento)
- ✅ Sessões persistentes com timeout
- ✅ Proteção contra ataques de força bruta (Rate Limiting)

### 📊 **Dashboard**
- ✅ Métricas em tempo real (veículos, documentos, manutenções)
- ✅ Gráficos interativos com Chart.js
- ✅ Atividades recentes
- ✅ Alertas de documentos a vencer
- ✅ Ações rápidas (adicionar veículo, upload, etc.)

### 🚗 **Gestão de Veículos**
- ✅ Cadastro completo de veículos
- ✅ Histórico de manutenções
- ✅ Documentos associados
- ✅ Status de cada veículo

### 📄 **Gestão de Documentos**
- ✅ Upload com OCR e IA
- ✅ Alertas de vencimento
- ✅ Categorização automática
- ✅ Visualização e download

### 🔧 **Manutenção**
- ✅ Agendamento de serviços
- ✅ Histórico de manutenções
- ✅ Alertas preventivos
- ✅ Controle de custos

### 📈 **Relatórios**
- ✅ Relatórios personalizáveis
- ✅ Exportação em CSV/PDF
- ✅ Análise de custos

### 📱 **Responsividade**
- ✅ Totalmente responsivo (Desktop, Tablet, Mobile)
- ✅ Menu hamburger para dispositivos móveis
- ✅ Dark mode automático

---

## 🛠 Tecnologias Utilizadas

### **Backend**
| Tecnologia | Versão | Descrição |
|------------|--------|-----------|
| PHP | 8.0+ | Linguagem principal |
| MySQL | 5.7+ | Banco de dados relacional |
| PDO | - | Camada de abstração de banco de dados |
| Composer | 2.0+ | Gerenciador de dependências |

### **Frontend**
| Tecnologia | Versão | Descrição |
|------------|--------|-----------|
| HTML5 | - | Estrutura das páginas |
| CSS3 | - | Estilização e animações |
| JavaScript | ES6+ | Interatividade |
| Chart.js | 4.4.0 | Gráficos interativos |
| Font Awesome | 6.4.0 | Ícones vetoriais |
| Google Fonts (Inter) | - | Tipografia profissional |

### **Ferramentas de Desenvolvimento**
- **XAMPP** / **WAMP** / **LAMP** - Ambiente de desenvolvimento
- **phpMyAdmin** - Gerenciamento do banco de dados
- **Git** - Controle de versão

---

## 🏗 Arquitetura do Sistema
┌─────────────────────────────────────────────────────────────┐
│ USER INTERFACE (UI)                                         │
│ Landing Page → Login → Dashboard → Sistema                  │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│ APPLICATION LAYER                                           │
│ PHP Controllers (index.php, login.php, register.php,        │
│ dashboard.php, logout.php)                                  │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│ BUSINESS LAYER                                              │
│ User Model, Security Core, Database Class                   │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│ DATA LAYER (MySQL)                                          │
│ users, vehicles, documents, maintenance, sessions           │
└─────────────────────────────────────────────────────────────┘


text
### **Fluxo de Dados**
1. O usuário acessa a Landing Page (`/`)
2. Faz login (`/login`) ou registra (`/register`)
3. Após autenticação, é redirecionado para o Dashboard (`/dashboard`)
4. As ações no dashboard interagem com o banco de dados via Models
5. Os dados são exibidos em tempo real nas views

---

## 💻 Instalação e Configuração

### **Pré-requisitos**

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache/Nginx)
- Composer (opcional)

### **Passos para Instalação**

#### 1. Clone o repositório

```bash
git clone https://github.com/cardoxis/cardoxis.git
cd cardoxis
2. Configure o ambiente
Copie o arquivo de configuração de exemplo:
```
```bash
cp .env.example .env
Edite o arquivo .env com suas configurações:
```
env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cardoxis_db
DB_USER=root
DB_PASSWORD=

# Application Configuration
APP_NAME=CARDOXIS
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/cardoxis

# Security
SECURITY_KEY=your_security_key
3. Configure o servidor web
Apache (.htaccess):

apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/index.php [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/index.php [QSA,L]
</IfModule>
Nginx (nginx.conf):

nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
4. Execute no servidor
XAMPP/WAMP:

text
Copie a pasta cardoxis para C:\xampp\htdocs\cardoxis
Linux/Mac:

bash
sudo cp -r cardoxis /var/www/html/
sudo chown -R www-data:www-data /var/www/html/cardoxis
sudo chmod -R 755 /var/www/html/cardoxis/storage
🗄 Configuração do Banco de Dados
Opção 1: Usando phpMyAdmin
Acesse http://localhost/phpmyadmin

Crie um novo banco de dados: cardoxis_db

Selecione o banco de dados criado

Vá em "Importar" e selecione o arquivo database.sql

Clique em "Executar"

Opção 2: Usando linha de comando
bash
# Acesse o MySQL
mysql -u root -p

# Crie o banco de dados
CREATE DATABASE cardoxis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Use o banco
USE cardoxis_db;

# Execute o script SQL
SOURCE /caminho/para/cardoxis/database.sql;
Opção 3: Script automático
Crie um arquivo setup.php:

php
<?php
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $sql = file_get_contents('database.sql');
    $db->exec($sql);
    echo "Banco de dados criado com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
##📁 Estrutura de Diretórios
text
cardoxis/
├── .htaccess                      # Configuração de rotas Apache
├── README.md                      # Documentação do projeto
├── database.sql                   # Script SQL do banco de dados
│
├── public/                        # Diretório público (acessível)
│   ├── index.php                  # Landing Page
│   ├── login.php                  # Página de Login
│   ├── register.php               # Página de Registro
│   ├── dashboard.php              # Dashboard
│   ├── logout.php                 # Logout
│   │
│   └── assets/                    # Recursos estáticos
│       ├── css/
│       │   ├── style.css          # Landing Page
│       │   ├── auth.css           # Login/Register
│       │   └── dashboard.css      # Dashboard
│       ├── js/
│       │   ├── app.js             # Landing Page
│       │   ├── auth.js            # Login/Register
│       │   └── dashboard.js       # Dashboard
│       └── img/                   # Imagens
│
├── views/                         # Views do sistema
│   ├── layout/
│   │   └── header.php             # Header global
│   └── dashboard/
│       ├── sidebar.php            # Menu lateral
│       ├── header.php             # Header do dashboard
│       ├── stats.php              # Cards de estatísticas
│       ├── quick-actions.php      # Ações rápidas
│       ├── charts.php             # Gráficos
│       ├── activities.php         # Atividades recentes
│       ├── maintenance.php        # Manutenções
│       ├── documents.php          # Documentos
│       └── footer.php             # Footer do dashboard
│
├── app/                           # Código principal (não acessível)
│   ├── core/
│   │   ├── Database.php           # Conexão com banco de dados
│   │   └── Security.php           # Funções de segurança
│   └── models/
│       └── User.php               # Modelo de usuário
│
└── storage/                       # Arquivos privados
    ├── logs/                      # Logs de erro
    ├── cache/                     # Cache
    └── uploads/                   # Uploads de documentos
        └── temp/                  # Arquivos temporários

        
####🔄 Fluxo do Sistema



| Rota          | Descrição                              | Autenticação |
|---------------|-----------                             |--------------|
| `/`           | Landing Page - Apresentação do sistema |    ❌        |
| `/register`   | Criar nova conta gratuita              |    ❌        |
| `/login`      | Entrar no sistema                      |    ❌        |
| `/dashboard`  | Painel principal com métricas          |    ✅        |
| `/vehicles`   | Gerenciar veículos da frota            |    ✅        |
| `/documents`  | Gerenciar documentos com IA/OCR        |    ✅        |
| `/maintenance`| Agendar e controlar manutenções        |    ✅        |
| `/reports`    | Visualizar relatórios e análises       |    ✅        |
| `/settings`   | Configurações da conta                 |    ✅        |
| `/logout`     | Sair do sistema                        |    ✅        |





Fluxo de Autenticação
Usuário não autenticado → Landing Page (/)

Cria conta → Registro (/register)

Faz login → Login (/login)

Autenticado → Dashboard (/dashboard)

Sai do sistema → Logout (/logout) → Landing Page

🔑 Credenciais de Teste
Administrador
Campo	Valor
Email	admin@cardoxis.com
Senha	Admin@123
Role	Administrador
Usuário Demo
Campo	Valor
Email	demo@cardoxis.com
Senha	Demo@123
Role	Usuário
🔒 Segurança
Implementado
✅ CSRF Protection - Tokens anti-falsificação

✅ Password Hashing - Bcrypt com custo 12

✅ Rate Limiting - 5 tentativas em 5 minutos

✅ Session Security - Cookies seguros, HttpOnly

✅ Input Sanitization - Prevenção XSS

✅ Prepared Statements - Prevenção SQL Injection

✅ Security Headers - X-Frame-Options, XSS-Protection

✅ HTTPS Ready - Cookies com flag Secure

Recomendações para Produção
🔐 HTTPS obrigatório (SSL/TLS)

🔐 Configurar firewall no servidor

🔐 Limitar acessos por IP (se necessário)

🔐 Monitoramento de logs em tempo real

🔐 Backups automáticos do banco de dados

🔐 Atualização regular de dependências

🔐 2FA (Two-Factor Authentication) - em desenvolvimento

🤝 Contribuição
Contribuições são bem-vindas! Siga os passos abaixo:

Fork o projeto

Crie uma branch para sua feature:

bash
git checkout -b feature/nova-feature
Commit suas mudanças:

bash
git commit -m 'Adiciona nova feature'
Push para a branch:

bash
git push origin feature/nova-feature
Abra um Pull Request

Padrões de Código
Seguir PSR-12 para PHP

Utilizar camelCase para JavaScript

Utilizar kebab-case para CSS classes

Comentar código complexo

Escrever mensagens de commit descritivas

📄 Licença
Este projeto é propriedade exclusiva da CARDOXIS. Todos os direitos reservados.

Termos de Uso:

❌ Não é permitido uso comercial sem autorização

❌ Não é permitida distribuição do código fonte

❌ Não é permitida cópia ou reprodução do design

✅ Uso educacional permitido (com atribuição)

Para licenciamento comercial, entre em contato.

####📞 Contato
CARDOXIS

🌐 Website: https://cardoxis.com

📧 Email: contato@cardoxis.com

📱 WhatsApp: +351 900 000 000

📍 Localização: Portugal 🇵🇹



####📊 Roadmap

Versão 1.0.0 (Atual)

✅ Sistema de autenticação completo

✅ Dashboard com métricas

✅ Gestão de veículos

✅ Gestão de documentos

✅ Gestão de manutenções

✅ Responsividade total

Versão 1.1.0 (Em desenvolvimento)

⏳ API RESTful

⏳ App Mobile (React Native)

⏳ Integração com Google Maps

⏳ OCR Avançado com IA

Versão 2.0.0 (Planejado)

⏳ Machine Learning preditivo

⏳ IoT integração com sensores

⏳ Dashboard personalizável

⏳ Multi-tenant

####❓ FAQ

Como posso resetar minha senha?

Atualmente em desenvolvimento. Envie um email para suporte@cardoxis.com

O sistema é compatível com mobile?

Sim! Totalmente responsivo e adaptado para smartphones e tablets.

Os dados são seguros?

Sim. Utilizamos criptografia de ponta a ponta e seguimos as melhores práticas de segurança.

Posso exportar os dados do meu dashboard?

Sim. Os relatórios podem ser exportados em CSV e PDF.

Como faço upgrade de plano?

Entre em contato com nossa equipe comercial para planos empresariais.

####🔗 Links Úteis
Documentação Oficial

Suporte Técnico

Status do Sistema

Blog



CARDOXIS - Gestão Inteligente de Frotas
