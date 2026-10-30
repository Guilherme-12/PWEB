# FitLife Manager — Relatório

**Plataforma web de gestão do ginásio fictício FitLife Gym**

---

## Sumário

1. Introdução
   1.1. Contexto
   1.2. Problema
   1.3. Motivação
   1.4. Objetivos
2. Metodologia de desenvolvimento
3. Requisitos
   3.1. Requisitos funcionais (detalhados)
   3.2. Requisitos não funcionais
4. Modelação e design
5. Arquitetura e implementação (passos)
6. Segurança
7. Testes
8. Resultados
9. Conclusão
10. Documentos e anexos

---

## 1. Introdução

### 1.1. Contexto

O **FitLife Gym** é um ginásio fictício, no âmbito da disciplina de **Programação Web**, que pretende representar a gestão diária de um negócio de fitness.

Durante o desenvolvimento do projeto, o contexto funcional identificou que a informação do ginásio se encontra, normalmente, dividida por vários processos:

- Sócios registados em fichas, folhas de cálculo ou cadernos;
- Planos de inscrição com valores mensais distintos;
- Aulas fixas com instrutores, horários e capacidades;
- Presenças diárias registadas manualmente;
- Pagamentos associados às mensalidades.

Não existindo uma fonte única de informação, a gestão diária fica fragmentada, as tarefas repetitivas consomem tempo operacional e a obtenção de indicadores de gestão exige trabalho manual adicional.

### 1.2. Problema

O problema principal do FitLife Gym, no contexto do projeto, é a **falta de uma aplicação centralizada** que reúna:

1. **Sócios** — registo, contacto e plano;
2. **Planos** — definição de preços e limites;
3. **Aulas** — oferta, instrutor, capacidade;
4. **Presenças** — marcação e controlo;
5. **Pagamentos** — registo, estados e valores.

Como consequência, surgem:

- dificuldade de obter uma visão consolidada do negócio;
- risco de erros em registos manuais;
- ausência de relatórios estatísticos;
- sobrecarga operacional do staff.

### 1.3. Motivação

A motivação para o desenvolvimento da aplicação foi a necessidade de transformar processos informais em **fluxos estruturados**, automatizados e com controlo.

Foi necessário criar uma solução que permitisse:

- gestão operacional rápida;
- controlo financeiro;
- apoio à decisão através de indicadores.

### 1.4. Objetivos

- **Objetivo geral:** criar a aplicação web FitLife Manager para gestão do FitLife Gym.
- **Objetivos específicos:**
  - desenvolver módulo de sócios;
  - desenvolver módulo de planos;
  - desenvolver módulo de aulas;
  - desenvolver módulo de presenças;
  - desenvolver módulo de pagamentos;
  - desenvolver dashboard com estatísticas.

---

## 2. Metodologia de Desenvolvimento

### 2.1. Fases do ciclo de vida

1. **Análise** — definição de requisitos e modelo de dados.
2. **Desenho** — definição dos fluxos de utilização.
3. **Implementação** — desenvolvimento módulo a módulo.
4. **Teste** — validação funcional e de segurança.
5. **Documentação** — manuais e relatórios.

### 2.2. Iterações

- Criação do esquema da base de dados;
- Implementação dos módulos;
- Correção de vulnerabilidades e refinação.

---

## 3. Requisitos

### 3.1. Requisitos funcionais detalhados

#### RF-01 — Gestão de Sócios

- **Descrição:** permitir registo, edição e eliminação de sócios, com pesquisa e filtro por plano.
- **Ator:** gestor/funcionário.
- **Pré-condições:** existir base de dados com tabelas e planos.
- **Fluxo principal:**
  1. Utilizador abre `socios.php`.
  2. Pesquisa aplica `LIKE` sobre nome/email/telefone.
  3. Filtro por plano via `plano_id`.
  4. Registo em `adicionar_socio.php`.
  5. Edição em `editar_socio.php`.
  6. Eliminação em `eliminar_socio.php`.
- **Regras de negócio:**
  - email obrigatoriamente único (validação + regra de BD);
  - data de registo automática;
  - eliminação bloqueada quando existem pagamentos.

#### RF-02 — Gestão de Planos

- **Descrição:** permitir criação, edição e eliminação de planos.
- **Fluxo:** formulário com `nome` e `preco`; listagem com `COUNT(socios)`.

#### RF-03 — Gestão de Aulas

- **Descrição:** gestão de aulas com nome, instrutor, horário, capacidade.
- **Regras:** eliminação bloqueada se existirem presenças.

#### RF-04 — Presenças

- **Descrição:** registar presença; validar duplicado e capacidade; evitar datas futuras.

#### RF-05 — Pagamentos

- **Descrição:** registar pagamento com valor, data, estado; estados `Pago`/`Pendente`; ações de marcar/eliminar.

#### RF-06 — Dashboard

- **Descrição:** indicadores e gráficos.

### 3.2. Requisitos não funcionais

- **Segurança:** prepared statements, CSRF, XSS, validação.
- **Desempenho:** limitação de registos (LIMIT 300) e queries otimizadas.
- **Compatibilidade:** PHP 8, MySQL/MariaDB, Bootstrap 5.

---

## 4. Modelação e Design

### 4.1. Entidades e atributos

| Entidade | Campo | Tipo | Chave |
|---|---|---|---|
| planos | id | INT AI PK |  |
| planos | nome | VARCHAR(50) | UNIQUE |
| planos | preco | DECIMAL(10,2) |  |
| socios | id | INT AI PK |  |
| socios | nome | VARCHAR(100) |  |
| socios | email | VARCHAR(100) | UNIQUE |
| socios | telefone | VARCHAR(20) |  |
| socios | plano_id | INT FK → planos |  |
| socios | data_registo | DATE |  |
| aulas | id | INT AI PK |  |
| aulas | nome | VARCHAR(100) |  |
| aulas | instrutor | VARCHAR(100) |  |
| aulas | horario | VARCHAR(50) |  |
| aulas | capacidade | INT |  |
| presencas | id | INT AI PK |  |
| presencas | socio_id | INT FK → socios | CASCADE |
| presencas | aula_id | INT FK → aulas | CASCADE |
| presencas | data_presenca | DATE |  |
| pagamentos | id | INT AI PK |  |
| pagamentos | socio_id | INT FK → socios | RESTRICT |
| pagamentos | valor | DECIMAL(10,2) |  |
| pagamentos | data_pagamento | DATE |  |
| pagamentos | estado | VARCHAR(20) |  |

### 4.2. Relacionamentos

- Plano ↔ Sócio: 1:N.
- Sócio ↔ Aula: N:M através de presenças.
- Sócio ↔ Pagamento: 1:N.

### 4.3. Modelo lógico resumido

```sql
CREATE TABLE socios ( ... );
CREATE TABLE planos ( ... );
...
```

---

## 5. Arquitetura e Implementação — Passos

### 5.1. Preparação

1. Criação do ambiente.
2. Importação do esquema.

### 5.2. Implementação por módulos (passo a passo)

**Módulo Sócios**
1. Criei o `socios.php` com query parametrizada.
2. Adicionei filtro `WHERE socios.nome LIKE ? ...`.
3. Adicionei select planos.

**Módulo Planos**
1. Query com `LEFT JOIN` + `GROUP BY`.

**Módulo Aulas**
1. Subqueries: presenças totais e presenças hoje.

**Módulo Presenças**
1. INSERT com validações.
2. DELETE.

**Módulo Pagamentos**
1. INSERT com estados.
2. UPDATE para marcar pago.

**Dashboard**
1. Queries de soma/contagem.
2. Gráfico.

### 5.3. Segurança — passos

1. `mysqli_report`.
2. Prepared statements.
3. CSRF.
4. Escaping.
5. POST-only deletes.

---

## 6. Testes

### 6.1. Estratégia

...

### 6.2. Casos de teste

Tabelas de casos por RF.

### 6.3. Resultados

...

---

## 7. Resultados

...

## 8. Conclusão

...

## 9. Documentos e anexos

- Apresentação: `apresentacao.md` / `FitLifeManager.pptx`
- Manual: `manual_de_instrucoes.md`
- Código: ficheiros PHP.

---

*Fim do relatório.*
