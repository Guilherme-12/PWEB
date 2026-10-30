# FitLife Manager

Aplicação web para gestão do ginásio fictício **FitLife Gym**. Centraliza sócios, planos, aulas, presenças e pagamentos, e apresenta indicadores de gestão no dashboard.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-8.x%20%2F%2010.4%2B-4479A1)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3)

---

## Funcionalidades

### Dashboard
- Total de sócios, receita mensal, aulas e presenças de hoje.
- Receita dos últimos 6 meses (gráfico de barras).
- Distribuição de sócios por plano (gráfico circular).
- Lista dos últimos sócios registados e dos últimos pagamentos.

### Sócios
- Listagem com pesquisa por nome, email ou telefone.
- Filtro por plano.
- Registo e edição com validação de dados (email válido e único, plano obrigatório).
- Eliminação protegida: impede apagar sócios com pagamentos associados.
- As presenças do sócio são removidas automaticamente ao eliminar o registo.

### Planos
- Criação, edição e eliminação de planos.
- Número de sócios e receita mensal potencial por plano.
- Só é possível eliminar planos sem sócios associados.
- Preço aceita vírgula ou ponto decimal (ex.: `39,90`).

### Aulas
- Criação, edição e eliminação de aulas (nome, instrutor, horário e capacidade).
- Indicador de ocupação de hoje com barra de progresso.
- Total de presenças por aula, com acesso direto ao histórico.
- Só é possível eliminar aulas sem presenças registadas.

### Presenças
- Registo de presenças por sócio, aula e data.
- Validação de duplicados (o mesmo sócio não pode ter duas presenças na mesma aula/dia).
- Controlo automático da capacidade da aula.
- Bloqueio de presenças em datas futuras.
- Filtros por data e por aula; listagem de todas as datas.

### Pagamentos
- Registo de pagamentos com valor, data e estado (`Pago` / `Pendente`).
- Sugestão automática do valor com base no plano do sócio.
- Ações para marcar como pago e eliminar.
- Filtros por sócio, estado e mês.
- Totais de recebido, pendente e número de registos para o filtro ativo.

---

## Tecnologias

- **PHP 8.x** (mysqli, sessões)
- **MySQL 8** / **MariaDB 10.4+**
- **Bootstrap 5.3** (CDN)
- **Font Awesome 6.4** (CDN)
- **Chart.js 4.4** (dashboard)

---

## Instalação (XAMPP)

1. **Copiar o projeto** para a pasta do servidor web, por exemplo:
   `C:\xampp\htdocs\PWEB`

2. **Iniciar** o Apache e o MySQL no painel do XAMPP.

3. **Criar a base de dados**:
   - Abrir `http://localhost/phpmyadmin`
   - Separador **Importar**
   - Escolher o ficheiro `database.sql`
   - Executar

   > O script `database.sql` recria as tabelas e apaga os dados existentes. Faz uma cópia de segurança antes de o reutilizar numa base com dados reais.

   Também é possível pela linha de comandos:
   ```bash
   mysql -u root -p < database.sql
   ```

4. **Configurar a ligação** (se necessário) em `config_db.php`:
   ```php
   $host = 'localhost';
   $user = 'root';
   $password = '';
   $database = 'fitlife_gym';
   ```
   As credenciais também podem ser definidas por variáveis de ambiente:
   `FITLIFE_DB_HOST`, `FITLIFE_DB_USER`, `FITLIFE_DB_PASS`, `FITLIFE_DB_NAME`.

5. **Abrir a aplicação**:
   `http://localhost/PWEB/` (redireciona para o dashboard)

### Requisitos
- PHP 8.0 ou superior
- Extensões `mysqli` e `mbstring` ativas
- MySQL 8 ou MariaDB 10.4+

As tabelas `aulas` e `presencas` são criadas automaticamente caso não existam (`check_db.php`). Ainda assim, o `database.sql` é a forma recomendada de preparar a base de dados, porque inclui também os dados de exemplo.

---

## Dados de exemplo

O `database.sql` inclui:
- 3 planos (Basic, Premium, Gold)
- 10 sócios
- 6 aulas
- 24 pagamentos distribuídos pelos últimos meses
- 25 presenças

Assim, o dashboard e os gráficos aparecem preenchidos logo após a instalação.

---

## Estrutura do projeto

```
PWEB/
├── index.php                 Página inicial (landing) com CTA para o dashboard
├── dashboard.php             Painel com indicadores e gráficos
├── socios.php                Listagem e pesquisa de sócios
├── adicionar_socio.php       Formulário de novo sócio
├── editar_socio.php          Edição de sócio
├── eliminar_socio.php        Eliminação (POST + CSRF)
├── planos.php                Gestão de planos
├── aulas.php                 Gestão de aulas
├── presencas.php             Registo e listagem de presenças
├── pagamentos.php            Registo e histórico de pagamentos
├── check_db.php              Cria tabelas auxiliares se necessário
├── config_db.php             Ligação à base de dados
├── database.sql              Esquema + dados de exemplo
├── config/
│   └── db.php                Compatibilidade com includes antigos
├── includes/
│   ├── init.php              Arranque comum (BD, funções, tabelas)
│   ├── funcoes.php           Utilitários, CSRF, flashes, formatação
│   ├── cabecalho.php         Layout: head, navbar e mensagens
│   └── rodape.php            Layout: footer e scripts
└── assets/
    ├── css/style.css         Identidade visual FitLife
    └── img/logo.png          Logótipo
```

---

## Segurança implementada

- **Prepared statements** em todas as consultas com dados do utilizador (previne SQL injection).
- **Tokens CSRF** em todos os formulários POST.
- **Validação no servidor** de nomes, emails, preços, datas, capacidades e estados.
- **Escaping de saída** com `htmlspecialchars` (previne XSS).
- **Eliminações por POST**, nunca por links GET.
- **Sessão** com cookie `HttpOnly` e `SameSite=Lax`.

---

## Regras de negócio

| Regra | Comportamento |
|---|---|
| Sócio com pagamentos | Não pode ser eliminado |
| Sócio com presenças | Presenças removidas em cascata |
| Plano com sócios | Não pode ser eliminado |
| Aula com presenças | Não pode ser eliminada |
| Presença duplicada | Bloqueada (sócio + aula + data) |
| Aula cheia | Bloqueada ao atingir a capacidade |
| Data futura | Bloqueada em presenças e pagamentos |
| Email repetido | Bloqueado |

---

## Documentação do projeto

- **Apresentação:** [`apresentacao.md`](apresentacao.md) — conteúdo e estrutura dos slides.
- **Relatório:** [`relatorio.md`](relatorio.md) — introdução, requisitos, implementação, testes e conclusão.
- **Manual de instruções:** [`manual_de_instrucoes.md`](manual_de_instrucoes.md) — instalação e guia de utilização.

## Possíveis melhorias futuras

- Autenticação de funcionários (login e perfis).
- Exportação de listagens para CSV/PDF.
- Gestão de estados dos sócios (ativo/suspenso).
- Marcação de aulas com vagas em tempo real.
- Relatórios anuais e por instrutor.
