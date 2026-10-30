# FitLife Manager — Manual de Instruções

## 1. Introdução

O **FitLife Manager** é a aplicação web do ginásio fictício FitLife Gym destinada a
operacionalizar a gestão de sócios, planos, aulas, presenças e pagamentos.

Este manual explica como instalar, configurar e utilizar a aplicação.

## 2. Pré-requisitos

- **Servidor web:** Apache (XAMPP) ou similar
- **PHP:** 8.0+ com extensões `mysqli` e `mbstring`
- **Base de dados:** MySQL 8 ou MariaDB 10.4+
- **Navegador:** browser moderno

## 3. Instalação

1. Copiar o projeto para a pasta pública, por exemplo `C:\xampp\htdocs\PWEB`.
2. Iniciar Apache e MySQL no XAMPP.
3. Importar o ficheiro `database.sql` no phpMyAdmin (Importar) ou via linha de comandos.
4. Ajustar credenciais em `config_db.php` se necessário (host, user, password, database).
5. Aceder em `http://localhost/PWEB/` — redireciona para o dashboard.

## 4. Acesso ao sistema

- O dashboard é o ponto de entrada (`index.php` redireciona para `dashboard.php`).
- Se as tabelas não existirem, são criadas automaticamente por `check_db.php`; a forma recomendada de instalação continua a ser a importação do `database.sql`.

## 5. Guia de utilização

### 5.1 Dashboard

- **Sócios:** total, novos este mês.
- **Receita mensal:** total recebido + valores pendentes.
- **Aulas:** quantidade e contexto de gestão.
- **Presenças:** presenças hoje e total registado.
- Gráficos: receita últimos 6 meses e sócios por plano.

### 5.2 Sócios

1. **Pesquisar:** introduzir texto no campo e clicar em *Filtrar*.
2. **Filtro por plano:** escolher plano ou *Todos os planos*.
3. **Novo sócio:** `adicionar_socio.php` (nome, email, telefone, plano).
4. **Editar:** `editar_socio.php?id=X`.
5. **Eliminar:** ação no registo; **bloqueado** se existirem pagamentos associados (deve-se eliminar os pagamentos primeiro).

### 5.3 Planos

- Criar, editar e eliminar planos.
- Mostrar número de sócios e receita mensal potencial.
- Eliminar só permitido quando não houver sócios associados.

### 5.4 Aulas

- Criar/editar aula (nome, instrutor, horário, capacidade).
- Ver ocupação diária e histórico de presenças.
- Eliminar apenas aulas sem presenças registadas.

### 5.5 Presenças

- Registar presença: sócio, aula, data.
- O sistema valida duplicados e capacidade.
- Não é permitido registar datas futuras.
- Filtros: data e aula.

### 5.6 Pagamentos

- Registar pagamento: sócio (valor sugerido pelo plano), valor, data, estado.
- Filtrar por sócio, estado e mês.
- Marcar como pago ou eliminar registos.

## 6. Regras de comportamento principais

| Caso | Regra |
|---|---|
| Email duplicado | Bloqueado |
| Sócio com pagamentos | Não eliminável |
| Sócio com presenças | Presenças removidas em cascata |
| Plano com sócios | Não eliminável |
| Aula com presenças | Não eliminável |
| Presença duplicada | Bloqueada |
| Aula cheia | Bloqueada |
| Data futura | Bloqueada |

## 7. Solução de problemas

- **Erro de ligação à base de dados:** confirmar MySQL em execução e `database.sql` importado; ajustar `config_db.php`.
- **Página em branco:** verificar PHP/Mysqli e importação do esquema.
- **Erros de formulário:** mensagens de validação são apresentadas no topo do formulário.

## 8. Fluxo recomendado no primeiro dia

1. Importar `database.sql`.
2. Registar novos sócios e planos.
3. Registar pagamentos.
4. Registar presenças do dia.
5. Consultar dashboard para validar indicadores.

## 9. Avisos

- Nunca fornecer tokens CSRF ou passwords por terceiros.
- Não partilhar o ficheiro `config_db.php` com credenciais públicas.
