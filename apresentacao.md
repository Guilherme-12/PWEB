# FitLife Manager — Apresentação

> Documento destinado à apresentação oral/visual do projeto FitLife Manager.

## Slides

### Slide 1 — Capa

- **FitLife Manager**
- Plataforma web de gestão integrada para o FitLife Gym
- Projecto de Programação Web

### Slide 2 — Contexto

- O FitLife Gym gere, em processos separados ou folhas informáticas:
  - sócios e planos de inscrição
  - aulas, instrutores e capacidades
  - presenças
  - pagamentos
- Falta uma visão centralizada da informação e de indicadores de gestão.
- Consequência: perda de controlo operacional, relatórios manuais e ausência de analytics.

### Slide 3 — Objetivo

- Criar uma aplicação web simples, intuitiva e centralizada.
- Permitir gestão de:
  - sócios
  - planos de inscrição
  - aulas
  - presenças
  - pagamentos
- Apoiar a gestão diária e as estatísticas do ginásio.

### Slide 4 — Funcionalidades principais

- **Dashboard**
  - total de sócios, receita mensal, aulas e presenças do dia
  - gráficos: receita (últimos 6 meses) e sócios por plano
- **Sócios**
  - registo, edição, eliminação, pesquisa e filtro por plano
- **Planos**
  - criação, edição e eliminação; sócios por plano e receita potencial
- **Aulas**
  - gestão com instrutor, horário e capacidade
  - ocupação diária por aula
- **Presenças**
  - registo, duplicados bloqueados, capacidade respeitada, datas válidas
- **Pagamentos**
  - registar, estados (`Pago`/`Pendente`), totalizadores, filtros

### Slide 5 — Solução tecnológica

- PHP 8 + MySQL / MariaDB (mysqli)
- Bootstrap 5 + Font Awesome + Chart.js
- Arquitetura monolítica com páginas PHP e queries preparadas
- Estrutura: `includes/`, `assets/`, páginas de módulo e esquema `database.sql`

### Slide 6 — Demonstração

- Fluxo típico: adicionar sócio → atribuir plano → registar pagamento → registar presença → ver dashboard atualizado.

### Slide 7 — Conclusão e próximos passos

- Aplicação funcional com dados de demonstração.
- Evoluções sugeridas: autenticação de funcionários, exportação de relatórios, estados de sócio, notificações.

---

## Orador

- Apresentar a problemática: informação fragmentada no dia-a-dia do ginásio.
- Mostrar a solução: uma única plataforma com módulos integrados.
- Destacar os indicadores do dashboard.
- Explicar o modelo de dados e a segurança implementada.

## Estrutura da apresentação

| Minuto | Conteúdo |
|---|---|
| 0–2 | Capa e contexto |
| 2–4 | Objetivos |
| 4–7 | Funcionalidades |
| 7–9 | Arquitetura e dados |
| 9–12 | Demonstração |
| 12–14 | Conclusão |
