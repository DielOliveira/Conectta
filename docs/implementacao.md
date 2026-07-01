# Implementacao

## Rodada 01 - Fluxo de Clientes

Data: 22/06/2026.

### Base criada

- Aplicacao Laravel em `C:\Conectta\app`.
- Aplicacao Laravel espelhada para execucao rapida em WSL: `~/Conectta/app`.
- Painel administrativo Filament em `/admin`.
- Banco local MySQL: `conectta`.
- Usuario local do banco: `conectta`.
- Senha local do banco: `conectta`.

### Login inicial

Usuario administrador criado pelo seeder:

- Email: definido por `ADMIN_EMAIL`.
- Senha: definida obrigatoriamente por `ADMIN_PASSWORD`.

Observacao: nao manter senha padrao ou senha compartilhada em producao.

### Cliente

Implementado:

- migrations de tabelas auxiliares de cliente;
- migration de `clientes`;
- migration minima de `veiculos`, apenas para permitir contagem futura de rastreadores ativos;
- models principais;
- seeders das listas estaticas usadas por cliente;
- resource Filament de `Cliente`;
- formulario de criar/editar cliente;
- listagem de clientes;
- filtro por status;
- filtro por periodo de adesao/cadastro;
- busca por nome e CPF/CNPJ;
- CPF/CNPJ unico e salvo sem mascara;
- contador `Qtd. Rastreadores` baseado em veiculos ativos;
- delete real;
- acoes de rastreadores/adicionar veiculo desabilitadas ate o modulo de rastreadores existir.

### Decisoes aplicadas

- `StatusClienteId` nao aparece no formulario principal de cliente.
- Cliente novo recebe status inativo por padrao.
- A regra definitiva de status automatico sera aplicada ao salvar veiculos/rastreadores: cliente fica ativo se possuir pelo menos um veiculo/rastreador ativo; caso contrario, inativo.
- `isGrupoZelo` ficou fora da reconstrucao inicial.
- `Anotacoes` e `ReplicarPagamento` ficam para o fluxo financeiro.

### Verificacoes realizadas

- `php -l` em arquivos PHP criados/alterados.
- `php artisan route:list --path=admin`.
- `php artisan filament:about`.
- `php artisan migrate:fresh --seed --force`.
- `php artisan test`.
- Conferencia MySQL:
  - `status_clientes`: 2 registros;
  - `estados`: 27 registros;
  - `users`: 1 registro.

### Pendencias tecnicas

- Para desenvolvimento local, rodar o servidor a partir do caminho nativo do Ubuntu, nao de `/mnt/c`, pois o desempenho e estabilidade sao muito melhores.
- Comando recomendado:
  - `cd ~/Conectta/app && php artisan serve --host=0.0.0.0 --port=8000`
- URL local:
  - `http://127.0.0.1:8000/admin/login`
- Criar o modulo de rastreadores/veiculos para ativar as acoes de carro e `+`.
- Ajustar detalhes visuais finos depois de ver a tela no navegador.

## Padrao de trabalho no WSL

A partir de 22/06/2026, o diretorio principal de desenvolvimento e execucao do Conectta passa a ser o filesystem nativo do Ubuntu/WSL:

- Projeto principal: `~/Conectta`
- Aplicacao Laravel: `~/Conectta/app`
- Documentacao: `~/Conectta/docs`
- Prints/referencias: `~/Conectta/referencias`
- Temporarios: `~/Conectta/tmp`

A pasta `C:\Conectta` fica como espelho/backup e ponto conveniente para acesso pelo Windows, mas Composer, Artisan, servidor Laravel, MySQL e demais comandos de desenvolvimento devem rodar a partir de `~/Conectta/app`.

### Comandos principais

Iniciar MySQL:

```bash
sudo service mysql start
```

Subir o painel local:

```bash
~/Conectta/bin/serve-detached.sh
```

Acessar no navegador:

```text
http://127.0.0.1:8000/admin/login
```

Parar o servidor local:

```bash
~/Conectta/bin/stop.sh
```

## Correcoes aplicadas - Cliente

Data: 22/06/2026.

- `CPF_CNPJ` agora possui validacao real de CPF ou CNPJ antes de salvar.
- `CPF_CNPJ` continua unico e salvo sem mascara.
- `Nascimento` foi alterado para campo texto com mascara `dd/mm/aaaa`, sem calendario.
- `Nascimento` na edicao aceita valor vazio, data do Laravel ou string do banco sem quebrar a tela.
- `Email` do cliente deve ser validado como email valido quando preenchido.
- `CEP` do cliente usa mascara `00.000-000` no formulario e e salvo sem mascara.
- `Telefone Celular` do cliente usa mascara `(00) 0.0000-0000` no formulario e e salvo sem mascara.
- `Status Cliente` foi removido do formulario de inclusao/edicao.
- Cliente novo recebe status inativo automaticamente no momento da criacao.
- A regra de status automatico por veiculos/rastreadores ativos permanece para o fluxo de veiculos.
- Adicionados testes unitarios para a regra de CPF/CNPJ.
- Painel Filament configurado com navegacao superior, seguindo o padrao visual do sistema OutSystems atual.
- Interface, botoes e mensagens do sistema devem ficar em portugues. O app foi configurado com locale `pt_BR` e validacoes locais em portugues.
- Tema visual padrao definido como amber/laranja do Filament: botoes primarios laranja, botoes secundarios brancos, links/icones de acao e foco de campos sem azul fixo.
- Busca da lista de clientes ajustada para filtrar corretamente por nome. A busca por CPF/CNPJ so participa quando o texto pesquisado possui numeros, evitando `CPF_CNPJ like '%%'`.

## Preparacao - Fluxo de Veiculos/Rastreadores

Data: 22/06/2026.

- O proximo recurso sera implementado como `Veiculo`, com nome visual/menu `Rastreadores`.
- `Veiculo` e texto livre.
- `IMEI` vem de `Rastreador`.
- `Numero Chip` vem de `Chip`.
- Campos legados `Veiculo.IMEI` e `Veiculo.NumeroChip` nao serao usados na reconstrucao inicial; usar `Veiculo.RastreadorId` e `Veiculo.ChipId`.
- `Login`, `Senha`, `Associado` e `Contato` sao digitados/textos livres.
- Status operacionais na tela de `Rastreadores`: `Ativo`, `Cancelado` e `Disponivel`.
- `Lixo` existe, mas fica fora desta tela e sera tratado no fluxo de estoque.
- Apenas `Ativo` conta para ativar o cliente.
- Ao salvar, excluir ou cancelar veiculo/rastreador, recalcular automaticamente o status do cliente.
- Se status do veiculo for `Cancelado`, exigir `Data Retirada` e `Tecnico Remocao`.
- Ao cancelar veiculo, status do veiculo fica `Cancelado` e status do rastreador vinculado volta para `Disponivel`.
- Ao cancelar veiculo, atualizar tambem `Rastreador.TecnicoId` para o tecnico selecionado na remocao.
- Impedir mesmo IMEI em dois veiculos ativos.
- Impedir mesmo chip em dois veiculos ativos.
- Para vincular IMEI a outro veiculo, o rastreador precisa estar `Disponivel`.
- Ao selecionar IMEI, preencher tecnico instalador a partir de `Rastreador.TecnicoId` e deixar bloqueado.
- `Contratos` fica para o final do fluxo.

### Implementado nesta rodada

- Migrations de `tipo_veiculos`, `tecnicos`, `chips` e `rastreadores`.
- Expansao da tabela `veiculos` para o fluxo de rastreadores.
- Models `TipoVeiculo`, `Tecnico`, `Chip` e `Rastreador`.
- Resource Filament `Rastreadores`, usando `Veiculo` como model principal.
- Rotas:
  - `/admin/rastreadores`
  - `/admin/rastreadores/create`
  - `/admin/rastreadores/{record}/edit`
- Lista com colunas de rastreador, cliente, veiculo, tipo, placa, status, instalacao e remocao.
- Os cabecalhos da lista de rastreadores foram encurtados para `Status`, `Instalacao` e `Remocao` para economizar largura e evitar rolagem horizontal quando possivel.
- Filtros por cliente, status, periodo de instalacao e periodo de remocao.
- Formulario de criar/editar rastreador/veiculo.
- Atalho na lista de clientes para abrir rastreadores filtrados pelo cliente.
- O atalho de rastreadores na lista de clientes usa `?cliente_id=...` e a query da tela de rastreadores filtra diretamente por esse cliente.
- Atalho `+` na lista de clientes para abrir novo rastreador com cliente pre-preenchido.
- Quando o cadastro de rastreador vem pelo `+` de um cliente, o campo `Cliente` fica bloqueado e o `cliente_id` e forÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¾ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§ado no salvamento.
- Ao criar ou editar clientes e rastreadores, o sistema redireciona para a respectiva lista apos salvar.

## Fluxo de Estoque - Tecnicos

Data: 22/06/2026.

- Criado CRUD de `Tecnico`.
- Menu agrupado em `Estoque`.
- Campos: `Nome`, `CPF`, `Telefone` e `Ativo`.
- `CPF` e opcional, mas quando preenchido deve ser um CPF valido. O valor e salvo sem mascara.
- Lista com busca por nome, CPF e telefone.
- Filtro por ativo/inativo/todos.
- Ao criar ou editar tecnico, redirecionar para a lista de tecnicos.

## Fluxo de Estoque - Rastreadores

Data: 22/06/2026.

- Criada pagina customizada `Estoque de Rastreadores`.
- Menu agrupado em `Estoque`, item `Rastreadores`.
- A listagem e a inclusao/edicao ficam na mesma tela.
- Campos do painel: `Modelo`, `Ativacao`, `IMEI`, `Status Estoque` e `Tecnico`.
- Ao clicar no botao `+`, inclui o rastreador na lista.
- Ao clicar no modelo de um item da lista, o painel e preenchido para edicao.
- Durante a edicao, o botao `+` vira botao de salvar.
- Filtros por tecnico, status e busca textual.
- Exclusao de rastreador pela lista, com confirmacao.
- A tela usa CSS proprio escopado na view para manter o layout consistente sem depender de recompilacao do Tailwind.
- A lista de estoque de rastreadores mostra primeiro o ultimo registro incluido ou alterado.
- Nao e permitido incluir ou salvar IMEI duplicado no estoque de rastreadores.
- Campo `Ativacao` no estoque de rastreadores vem preenchido por padrao com o ano atual.
- Ao incluir novo rastreador, o campo `Modelo` permanece preenchido para facilitar cadastro em serie.
- A lista de estoque de rastreadores possui paginacao de 15 registros por pagina, mantendo os filtros atuais.
- Botao `Exportar` do estoque de rastreadores gera CSV compativel com Excel usando exatamente a mesma lista filtrada/buscada na tela, sem limitar por paginacao.

## Fluxo Financeiro - Financeiro

Data: 22/06/2026.

### Visao geral

- Tela no menu `Financeiro`, submenu `Financeiro`.
- A tela mostra uma grade de clientes na esquerda e duas colunas mensais de lancamentos na direita.
- O objetivo e espelhar os lancamentos dos clientes nos dois meses visiveis.
- Ao abrir a tela, mostrar o mes atual e o proximo mes.
- As setas azuis navegam um mes por clique, mantendo sempre duas colunas lado a lado.

### Filtros superiores

- `Status`:
  - filtra `Cliente.StatusClienteId`;
  - mesmas opcoes da tela de clientes: `Ativo`, `Inativo`, `Todos`;
  - padrao: `Ativo`.
- `Clientes`:
  - busca por `Cliente.Nome`;
  - busca por `Cliente.CPF_CNPJ`.
- `Vencimento`:
  - filtra `Cliente.DiaPagamento`;
  - somente por dia exato.
- `Linhas`:
  - controla a quantidade de clientes exibidos;
  - padrao: `15`.
- `Limpar` limpa os filtros superiores.
- `Historico` fica desativado por enquanto.
- `Exportar` exporta a grade atual inteira, com clientes e os dois meses visiveis.

### Grade de clientes

- Coluna `Qtd`:
  - quantidade de rastreadores ativos do cliente.
- Coluna `Vendedor`:
  - vem de `Cliente.VendedorId -> Vendedor.Nome`.
- Coluna `Cliente`:
  - nome do cliente.
- Coluna `Venc.`:
  - edita `Cliente.DiaPagamento`;
  - salva automaticamente apos 2 segundos sem digitar.
- Coluna `Anotacoes`:
  - edita `Cliente.Anotacoes`;
  - salva automaticamente apos 2 segundos sem digitar.
- Botao redondo/setinha:
  - controla `Cliente.ReplicarPagamento`;
  - clique alterna ligado/desligado;
  - visual preto quando ligado;
  - visual cinza quando desligado.

### Colunas mensais de lancamentos

- Cada uma das duas colunas representa um mes/ano.
- Campos exibidos:
  - `NÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Boleto` = `Lancamentos.NumeroBoleto`;
  - `Planejado` = `MAX(Lancamentos.ValorPlanejado)` dentro do cliente/mes;
  - `Efetuado` = soma de `Lancamentos.ValorEfetivado`;
  - `Observacao` = `MAX(Lancamentos.Observacao)` dentro do cliente/mes.
- Para cada mes visivel, agregar lancamentos por `ClienteId`, `MesReferencia` e `AnoReferencia` antes de juntar com clientes.
- Se houver mais de um lancamento para o mesmo cliente/mes:
  - `NumeroBoleto` usa `MAX`;
  - `ValorPlanejado` usa `MAX`;
  - `ValorEfetivado` usa `SUM`;
  - `Observacao` usa `MAX`.
- A query principal usa `LEFT JOIN` com os dois agregados mensais para manter clientes sem lancamento na lista.
- `NumeroBoleto` e campo livre no popup de lancamento.
- As colunas mensais nao sao editaveis diretamente na grade.

### Popup de lancamento

- Icone de lapis abre popup de lancamento daquele cliente/mes.
- Se nao existe lancamento, o popup cria.
- Se existe lancamento, o popup edita.
- Nesta primeira fase, criar apenas popup vazio com botoes de navegacao; conteudo real fica para etapa posterior.

### Filtros por mes

- Cada mes possui filtros proprios.
- Os filtros de um mes limpam os filtros do outro mes para nao conflitar.
- Isso vale tambem para o campo `Pesquisar`.
- O campo `Pesquisar` de cada mes pesquisa em `Lancamentos.Observacao`.
- Pela query atual, o campo `Pesquisar` de cada mes pesquisa em `Observacao`, `NumeroBoleto` e `ValorEfetivado`.
- Os checkboxes influenciam os campos abaixo deles:
  - checkbox de `NÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº Boleto` filtra `Lancamentos.NumeroBoleto`;
  - checkbox de `Efetuado` filtra `Lancamentos.ValorEfetivado`.
- Cada checkbox tem tres estados:
  - caixa totalmente limpa: traz apenas registros em que o campo esta vazio;
  - caixa com um menos: traz apenas registros em que o campo esta preenchido;
  - caixa marcada: traz tudo.
- Padrao dos checkboxes: marcado, ou seja, trazer tudo.

### Implementado na primeira fase

- Criada migration da tabela `lancamentos`.
- Criado model `Lancamento`.
- Adicionada relacao `Cliente -> lancamentos`.
- Criada pagina customizada `Financeiro`, no menu `Financeiro`.
- URL local: `/admin/financeiro`.
- A pagina lista clientes com duas colunas mensais: mes atual e proximo mes.
- As setas navegam um mes por clique, mantendo dois meses visiveis.
- A query preserva clientes sem lancamento.
- Os lancamentos sao agregados por cliente/mes/ano:
  - `NumeroBoleto`: `MAX`;
  - `ValorPlanejado`: `MAX`;
  - `ValorEfetivado`: `SUM`;
  - `Observacao`: `MAX`.
- Filtros superiores implementados:
  - status do cliente;
  - busca de clientes por nome, CPF/CNPJ e anotacoes;
  - vencimento por dia exato;
  - quantidade de linhas por pagina.
- `Limpar` restaura os filtros superiores para o padrao.
- `Historico` fica desativado.
- `Vencimento` e `Anotacoes` sao editaveis direto na grade e salvos automaticamente apos 2 segundos sem digitacao.
- `Vencimento` e `Anotacoes` tambem salvam imediatamente ao sair do campo ou ao pressionar Enter, reduzindo risco de perder alteracao em refresh rapido.
- Botao de `ReplicarPagamento` alterna ligado/desligado na grade.
- Popup de lancamento criado como placeholder, com botoes `Anterior`, `Fechar` e `Proximo`.
- Botao `Exportar` gera CSV compativel com Excel da grade atual com os dois meses visiveis.
- `FinanceiroDemoSeeder` foi usado durante desenvolvimento e removido na limpeza para producao.
- Paginacao implementada no rodape da tela financeira:
  - mostra intervalo atual e total filtrado;
  - permite navegar entre paginas;
  - respeita o campo `Linhas` como tamanho de pagina.
- Totais mensais implementados no rodape de cada painel mensal:
  - total planejado em vermelho;
  - total efetivado em verde;
  - calculados com todos os clientes do filtro atual, nao somente a pagina visivel.
- Filtros mensais implementados:
  - campo `Pesquisar` individual para cada mes;
  - pesquisa mensal em `Observacao`, `NumeroBoleto` e `ValorEfetivado`;
  - ao usar filtros de um mes, os filtros do outro mes sao limpos para evitar conflito;
  - filtro triestado de `N. Boleto`;
  - filtro triestado de `Efetuado`;
  - estado marcado traz todos;
  - estado com traco traz apenas preenchidos;
  - estado vazio traz apenas vazios, incluindo clientes sem lancamento no mes.
- Filtros mensais passam a afetar listagem, paginacao, totais e exportacao.
- Popup de lancamento evoluido:
  - possui abas `Lancamento`, `Parcelamento` e `Boleto`;
  - aba `Boleto` mostra o estado do boleto do lancamento;
  - aba `Lancamento` permite criar ou editar o lancamento do cliente/mes/ano aberto;
  - `Ano Referencia` e `Mes Referencia` ficam travados;
  - campos implementados: `Data Lancamento`, `Numero Boleto`, `Valor Planejado`, `Valor Efetivado` e `Observacao`;
  - quando o lancamento ainda nao tem `Valor Efetivado`, `Data Lancamento` sugere sempre o dia atual, mesmo para referencia passada ou futura;
  - quando o lancamento ja tem `Valor Efetivado`, manter a `Data Lancamento` salva para preservar historico;
  - se existir lancamento, carrega para edicao;
  - se nao existir lancamento, cria ao salvar;
  - ao salvar, o popup fecha e a grade e atualizada.
- Aba `Boleto` do popup financeiro:
  - quando nao existe invoice, mostra valor vindo de `Lancamentos.ValorPlanejado`, vencimento editavel, status `Nao gerado` e acao `Gerar Boleto`;
  - quando existe invoice, mostra valor, vencimento, status colorido, links `Invoice`/`Boleto` e acoes `Refresh`, `Realizar baixa` e `Cancelar Boleto`;
  - a chamada real de geracao/refresh/baixa/cancelamento fica para a proxima etapa, apos detalhar a funcao atual da Lytex.
- Validacoes iniciais do botao `Gerar Boleto` implementadas antes da chamada real da Lytex:
  - cliente deve existir;
  - bloquear se ja existe boleto pago para a mesma referencia (`paid`/`Pago`);
  - bloquear se ja existe boleto em aberto/processamento/atrasado para a mesma referencia;
  - bloquear se a soma de `ValorEfetivado` da referencia for maior que `0`;
  - bloquear se `ValorPlanejado` do lancamento principal nao for maior que `0`;
  - bloquear se a referencia estiver fora da janela de 6 meses anteriores ate 2 meses posteriores ao mes atual;
  - validar email do cliente;
  - validar telefone celular com 11 digitos numericos;
  - validar CPF/CNPJ do cliente;
  - validar data de nascimento quando preenchida.
- Request inicial preparada para a futura chamada de criacao de fatura na Lytex, baseada no trace `POST https://api-pay.lytex.com.br/v2/invoices/`:
  - `client.treatmentPronoun`: `you`;
  - `client.name`: nome do cliente sem espacos extras;
  - `client.type`: `pf` quando CPF/CNPJ possui 11 digitos, `pj` quando possui 14;
  - `client.cpfCnpj`: CPF/CNPJ tratado, somente digitos;
  - `client.email`: email do cliente sem espacos extras;
  - `client.cellphone`: telefone celular tratado, somente digitos;
  - `items[0].name`: `Mensalidade {MesReferencia}/{AnoReferencia}`;
  - `items[0].quantity`: `1`;
  - `items[0].value`: `ValorPlanejado` convertido para centavos em texto, exemplo `50,00` vira `"5000"`;
  - `mulctAndInterest.enable`: `true`;
  - `mulctAndInterest.mulct.type`: `percentage`;
  - `mulctAndInterest.mulct.value`: `5`;
  - `mulctAndInterest.interest.type`: `percentage`;
  - `mulctAndInterest.interest.value`: `0.1`;
  - `paymentMethods.pix.enable`: `true`;
  - `paymentMethods.boleto.enable`: `true`;
  - `paymentMethods.creditCard.enable`: `false`;
  - `dueDate`: vencimento escolhido no popup em formato `YYYY-MM-DD`.
- Consumo real da Lytex:
  - criado servico `App\Services\Lytex\LytexInvoiceService`;
  - antes de gerar a fatura, o servico obtem/reaproveita token em `POST /v2/auth/obtain_token`;
  - endpoint: `POST /v2/invoices/`;
  - configuracoes em `.env`:
    - `LYTEX_BASE_URL`, padrao `https://api-pay.lytex.com.br`;
    - `LYTEX_CLIENT_ID`;
    - `LYTEX_CLIENT_SECRET`;
    - `LYTEX_AUTH_SCHEME`, padrao `Bearer`;
    - `LYTEX_TIMEOUT`, padrao `30`;
  - se URL, ClientId ou ClientSecret nao estiverem configurados, a chamada nao e feita e a tela exibe erro em portugues;
  - tokens retornados pela Lytex ficam cacheados na tabela `tokens_lytex`;
  - `access_token` e `refresh_token` sao salvos criptografados;
  - campos do token: `access_token`, `refresh_token`, `expire_at`, `refresh_expire_at`;
  - enquanto `expire_at` estiver no futuro, reutilizar o token cacheado;
  - ao trocar URL, ClientId ou ClientSecret na tela administrativa, limpar o token cacheado;
  - erros de conexao e erros HTTP da Lytex sao capturados e exibidos em portugues;
  - quando a Lytex devolver detalhes em `error[0].message`, exibir o motivo especifico em portugues; exemplo: vencimento anterior mostra `A data de vencimento deve ser maior que ou igual a dd/mm/aaaa`;
  - `Refresh` do popup financeiro chama `GET /v2/invoices/{Invoice.fatura_id}` e atualiza a tabela `Invoice`;
  - `Cancelar Boleto` chama `PUT /v2/invoices/cancel/{Invoice.fatura_id}`;
  - apos cancelar, chama `GET /v2/invoices/{Invoice.fatura_id}` para buscar o estado atual e atualizar a tabela `Invoice`;
  - invoices canceladas deixam de bloquear o formulario `Novo Boleto`;
  - a aba `Boleto` mostra invoices anteriores/canceladas como itens colapsaveis;
  - ao gerar novo boleto apos cancelamento, criar um novo registro de `Invoice` para a nova `fatura_id`;
  - `Realizar baixa` fica habilitado apenas quando o boleto estiver com status `Pago`;
  - em sucesso, salva ou atualiza `Invoice` por `fatura_id`, mantendo o vinculo com `LancamentoId`;
  - campos salvos:
    - `_clientId` em `Invoice.client_id`;
    - `client.cpfCnpj` em `Invoice.cpf_cnpj`;
    - `_id` em `Invoice.fatura_id`;
    - `totalValue` convertido de centavos para reais em `Invoice.total_value`;
    - `createdAt` e `updatedAt` em campos externos;
    - `_hashId` em `Invoice.hash_id`;
    - `status` traduzido para label local;
    - `dueDate` em `Invoice.vencimento`;
    - usuario autenticado em `Invoice.user_id`.
  - apos boleto gerado com sucesso, atualizar `Lancamentos.NumeroBoleto` para `Lytex`.
- Configuracao administrativa de integracoes:
  - criada tabela `configuracoes_integracao` para guardar configuracoes por integracao e ambiente;
  - criada pagina `Administrativo > Integracoes`;
  - nesta fase a pagina configura apenas `Lytex`;
  - a Lytex possui dois formularios independentes: `Homologacao` e `Producao`;
  - cada ambiente possui URL base, ClientId, ClientSecret, esquema de autenticacao e timeout proprios;
  - existe um unico campo `Ambiente ativo`, que define qual ambiente sera usado para gerar, consultar e cancelar boletos;
  - apenas um registro Lytex pode ficar ativo por vez;
  - ClientSecret e salvo criptografado no banco e nao e exibido novamente na tela;
  - se o campo ClientSecret ficar vazio ao salvar, o ClientSecret anterior daquele ambiente e mantido;
  - `.env` continua como fallback inicial quando ainda nao existe registro salvo no banco;
  - a chave unica da tabela passa a ser `integracao + ambiente`, permitindo Producao e Homologacao simultaneamente;
  - ao trocar o ambiente ativo, limpar o token cacheado da Lytex;
  - ao trocar URL, ClientId ou ClientSecret do ambiente ativo, limpar o token cacheado da Lytex.



## Integracao ZapSign

- ZapSign configurada em `Administrativo > Integracoes`, com ambientes independentes de `Homologacao` e `Producao`.
- Cada ambiente possui:
  - URL base;
  - token criptografado;
  - esquema de autenticacao;
  - timeout;
  - template Principal;
  - template Aditivo;
  - template Comodato.
- Ambiente ativo da ZapSign define qual configuracao sera usada ao enviar contratos.
- Endpoint usado na primeira fase: `POST /api/v1/models/create-doc/`.
- Payload `Principal` e `Aditivo` usam os mesmos campos e mudam apenas o template.
- Templates iniciais de producao vindos dos traces:
  - Principal: `e8db8e22-f163-419e-898f-d7709fea2296`;
  - Aditivo: `029c6d29-7c8d-4e9c-8f8e-9bdc16b7add8`;
  - Comodato: `fe0d3df0-b615-4296-81d7-0dd274036892`.
- Ao enviar com sucesso:
  - criar registro em `contratos`;
  - salvar `response.token` em `contratos.doc_token`;
  - salvar status `Enviado`.
- Em erro da ZapSign, exibir mensagem dentro do popup de contratos.
## Fluxo Rastreadores - Contratos

- Cada veiculo/rastreador pode ter contratos ZapSign dos tipos `Principal`, `Aditivo` e `Comodato`.
- Pode existir mais de um contrato do mesmo tipo para o mesmo veiculo, pois o documento pode mudar de versao.
- Campos antigos conceituais que ficavam em `Veiculo` no OutSystems (`doc_token`, `TipoContratoId`, `StatusContratoId`) passam para a tabela nova `contratos`.
- Tabela `tipo_contratos` criada com `Principal`, `Aditivo` e `Comodato`.
- Tabela `contratos` criada com:
  - `veiculo_id`;
  - `tipo_contrato_id`;
  - `status_contrato_id`;
  - `doc_token`;
  - timestamps.
- Status possiveis usam a base `status_contratos`: `Assinado`, `Enviado`, `Nao Enviado` e `Rejeitado`.
- Dados exibidos do contrato sao buscados das bases existentes sempre que possivel: cliente, endereco, contato, email, veiculo, placa, data de instalacao, valor de instalacao e dia de vencimento.
- Campos digitados na tela de `Comodato` nao sao persistidos nesta fase; serao usados depois apenas para montar o documento ZapSign.
- Primeira fase implementada sem chamada ZapSign: o botao `Contratos` na edicao do rastreador abre a pagina de contratos do veiculo e o botao `Enviar` registra localmente um contrato com status `Nao Enviado`.
- Integracao real com ZapSign fica para fase posterior.
## Fluxo Financeiro - Faturamento

- Criada pagina customizada `Faturamento`, no menu `Financeiro`.
- Criada tabela `faturamentos`:
  - `ano`;
  - `mes`;
  - `is_aberto`;
  - indice unico por `ano` + `mes`.
- A tela exibe sempre os 12 meses do ano selecionado.
- `Total Lancado` e calculado com `SUM(lancamentos.valor_efetivado)` filtrando:
  - `ano_referencia = ano selecionado`;
  - `mes_referencia = mes da linha`;
  - `cliente_id IS NOT NULL`.
- `Total Recebido` e calculado com `SUM(lancamentos.valor_efetivado)` filtrando:
  - mes de `data_lancamento`;
  - ano de `data_lancamento`.
- O rodape soma `Total Lancado` e `Total Recebido` do ano.
- A acao da primeira coluna alterna o mes aberto.
- Ao abrir um mes, os demais meses do mesmo ano sao fechados.
- O link `Ajuste` fica visual, sem acao nesta etapa.
- Rodape e botoes das abas `Lancamento`, `Parcelamento` e `Boleto` foram harmonizados:
  - botao `Fechar` secundario branco em todas as abas;
  - acoes principais em laranja preenchido;
  - tamanhos minimos consistentes entre botoes.
- Painel da aba `Boleto` reorganizado para leitura mais clara:
  - valor e status em caixas de resumo no topo;
  - vencimento, links e acoes agrupados abaixo;
  - layout responsivo para o modal.
- Layout da grade financeira compactado para aproximar do OutSystems e caber melhor em desktop:
  - removido o titulo grande padrao da pagina;
  - filtros superiores ficaram mais baixos e estreitos;
  - grade passou a usar colunas proporcionais;
  - colunas dos meses e celulas foram reduzidas;
  - acao de edicao do lancamento ficou curta para economizar largura.
- Setas de navegacao mensal reposicionadas para o cabecalho `Clientes`, no limite antes dos paineis mensais, seguindo o sistema legado.

## Fluxo Financeiro - Relatorio geral

Data: 23/06/2026.

- Criada migration da tabela `invoices`, equivalente a entidade `Invoice/Faturas` usada no OutSystems.
- Criado model `Invoice`.
- Adicionada relacao `Lancamento -> invoice`.
- Criada pagina customizada `Relatorio geral`, no menu `Financeiro`.
- URL local: `/admin/relatorio-geral`.
- Titulo da tela: `Lancamentos abertos`.
- Consulta implementada com base no SQL atual do OutSystems:
  - fonte principal `lancamentos`;
  - left join com `clientes`;
  - left join com `invoices`;
  - `valor_planejado > 0`;
  - `valor_efetivado = 0`.
- Na reconstrucao Laravel, `valor_efetivado NULL` tambem e tratado como aberto para contemplar campos vazios salvos como `NULL`.
- Filtros implementados:
  - `Data Inicio`;
  - `Data Fim`;
  - `N. Boleto`;
  - `Status Cliente`;
  - `Status Boleto`.
- `Status Cliente` segue a regra:
  - `Todos`;
  - `Ativo`;
  - `Inativo`.
- `Status Boleto` usa valores distintos de `Invoice.Status`.
- Ordenacao por `Cliente.DiaPagamento` crescente.
- Lista inicial limitada a 8 registros, acompanhando o `TOP (8)` do SQL enviado.
- Botao `Exportar` gera CSV compativel com Excel usando a mesma lista filtrada, sem limitar a 8 registros.
- `FinanceiroDemoSeeder` foi usado durante desenvolvimento para validar invoices demo e removido na limpeza para producao.
- Layout ajustado para ocupar largura total do painel e evitar centralizacao/deslocamento horizontal.
- Labels de `Status Boleto` padronizadas por cor:
  - `Aguardando Pagamento`: amarelo claro;
  - `Pago`: verde;
  - `Atrasado`: vermelho;
  - `Cancelado`: vermelho;
  - `Processando`: laranja.
- Seeders financeiros demo removidos da base versionada para evitar dados de exemplo em producao.

## Fluxo Financeiro - Boletos

Data: 23/06/2026.

- Criada pagina customizada `Boletos`, no menu `Financeiro`.
- URL local: `/admin/boletos`.
- Titulo da tela: `Boletos Gerados`.
- Fonte principal: `invoices`, com joins/relacoes para `lancamentos`, `clientes` e `users`.
- Filtros iniciais:
  - `Criado em Inicio`;
  - `Criado em Fim`;
  - `Status`;
  - `Pesquisa`.
- Listagem inicial limitada a 7 registros, seguindo o `TOP (7)` do SQL legado informado.
- Paginacao implementada no rodape, mantendo 7 registros por pagina e preservando filtros.
- Campo `Pesquisa` busca por cliente, CPF/CNPJ, fatura/invoice, numero do boleto e usuario.
- Coluna `Invoice` exibe dois links gerados a partir de `Invoice.hash_id`:
  - `Invoice`: `https://checkout-pay.lytex.com.br/fatura/{hash_id}`;
  - `Boleto`: `https://public-api-pay.lytex.com.br/v1/invoices/print/{hash_id}`.
- A coluna `Usuario` nao e exibida nesta fase porque esta vazia no uso atual.
- A celula de links nao mostra label `Link` para economizar espaco.
- Ambos os links abrem em nova aba.
- Status do boleto usa as mesmas cores globais definidas para labels de boleto.

### Verificacoes realizadas

- `php -l` nos arquivos PHP criados/alterados.
- `php artisan migrate --force`.
- `php artisan db:seed --class=RastreadorSupportSeeder --force`.
- `php artisan route:list --path=admin/rastreadores`.
- `Invoke-WebRequest` retornando `200` para:
  - `/admin/clientes`
  - `/admin/rastreadores`
  - `/admin/rastreadores/create?cliente_id=1`
- `php artisan test`: testes existentes passando; testes novos de fluxo ficam registrados, mas sao pulados neste ambiente enquanto a extensao `pdo_sqlite` nao estiver instalada.

### Bootstrap de dados de exemplo

- `DemoBootstrapSeeder` foi usado durante desenvolvimento para gerar dados locais de teste.
- O seeder demo foi removido da base versionada na limpeza para producao.


## Usuarios e Permissoes

Data: 24/06/2026.

- Criada estrutura de usuarios e permissoes.
- A tabela `users` recebeu o campo `is_admin`.
- Usuarios administradores acessam todas as telas e acoes, independentemente das permissoes marcadas.
- Criada tabela `permissions` com as permissoes originais do OutSystems, mantendo os nomes tecnicos:
  - `Boletos_Baixar`;
  - `Boletos_Escrita`;
  - `Boletos_Leitura`;
  - `Cadastro_Escrita`;
  - `Cadastro_Exclusao`;
  - `Cadastro_Leitura`;
  - `Estoque_Escrita`;
  - `Estoque_Leitura`;
  - `Faturamento_Escrita`;
  - `Faturamento_Leitura`;
  - `Financeiro_Escrita`;
  - `Financeiro_Leitura`.
- Criada tabela pivÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â´ `permission_user` para vincular usuarios e permissoes.
- Criada tela `Administrativo > Usuarios`.
- Na tela de usuario e possivel:
  - cadastrar nome, email e senha;
  - marcar usuario como administrador;
  - marcar permissoes por checkbox.
- O usuario inicial `admin@conectta.local` nasce como administrador e recebe todas as permissoes.
- Regras aplicadas:
  - `Cadastro_Leitura`: acesso a Clientes e Rastreadores;
  - `Cadastro_Escrita`: criar/editar Clientes e Rastreadores;
  - `Cadastro_Exclusao`: excluir Clientes e Rastreadores;
  - `Estoque_Leitura`: acesso a Estoque de Rastreadores e Tecnicos;
  - `Estoque_Escrita`: criar/editar/excluir Estoque de Rastreadores e Tecnicos;
  - `Financeiro_Leitura`: acesso a Financeiro e Relatorio geral;
  - `Financeiro_Escrita`: editar vencimento, anotacoes, replicar pagamento, lancamentos e parcelamentos;
  - `Boletos_Leitura`: acesso a Boletos Gerados;
  - `Boletos_Escrita`: gerar, atualizar e cancelar boletos no popup financeiro;
  - `Boletos_Baixar`: realizar baixa de boleto;
  - `Faturamento_Leitura`: acesso a Faturamento;
  - `Faturamento_Escrita`: abrir/fechar mes de faturamento.
- `Administrativo > Integracoes` e `Administrativo > Usuarios` ficam restritos a administradores.
## ZapSign - Autenticacao

- A integracao da ZapSign usa o ambiente ativo configurado em Administrativo > Integracoes.
- Cada ambiente da ZapSign deve armazenar URL base, token de API, auth scheme, timeout e templates de Principal, Aditivo e Comodato.
- O token e enviado no header Authorization. Quando o campo token nao contem prefixo, o sistema monta usando o auth scheme, por padrao: Bearer TOKEN.
- Se a ZapSign responder API token not found ou Token da API nao encontrado, o sistema exibe mensagem orientando a conferir o token do ambiente ativo.
- Quando a ZapSign recusa por autenticacao, nenhum contrato deve ser gravado no historico, pois o documento nao foi criado.

## Restore de backup por XLSX

- O restore operacional fica em Administrativo > Restore Backup, restrito a administradores.
- A tela recebe os arquivos Cliente.xlsx, Veiculos.xlsx, Rastreador.xlsx, Tecnico.xlsx, Vendedores.xlsx, Lancamento.xlsx, Invoice.xlsx e Faturamento.xlsx.
- Antes da importacao, a rotina limpa tabelas operacionais: contratos, invoices, lancamentos, veiculos, chips, rastreadores, clientes, tecnicos, vendedores e faturamentos.
- Usuarios, permissoes, configuracoes de integracao e tabelas de apoio nao sao apagados.
- Os IDs originais dos dumps sao preservados para manter relacionamentos entre arquivos.
- Como nao existe dump separado de chips, a tabela chips e reconstruida a partir do campo Numero Chip do dump de veiculos.
- Clientes sem CPF/CNPJ ou com CPF/CNPJ duplicado no dump recebem um marcador tecnico no campo para permitir a importacao, e o resumo do restore mostra estes avisos.
- Lancamentos e invoices com referencia quebrada para cliente/lancamento inexistente sao ignorados para preservar integridade referencial.

### Restore de contratos antigos

- O restore reconstrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³i a tabela `contratos` a partir dos campos antigos de `Veiculos.xlsx`: `Status Contrato`, `doc token` e `Contrato Tipo`.
- SÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o criados contratos quando o veÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­culo tem tipo de contrato preenchido, doc token preenchido ou status diferente do default sem contrato.
- Mapeamento legado de `Contrato Tipo`: `1` ou `Principal` = Principal; `2` ou `Aditivo` = Aditivo; `3` ou `Comodato` = Comodato.
- Os IDs legados de `status_contratos` devem ser preservados: `1` Assinado, `2` Rejeitado, `3` Enviado, `4` Nao Enviado.
- VeÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­culos com `Status Contrato = 4`, sem tipo e sem doc token, nÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o geram registro em `contratos`, pois representam o default sem contrato enviado.

### Links de documentos ZapSign no historico

- No historico do popup de contratos, a coluna do token foi substituida por links de documentos.
- Para contratos com `doc_token`, o sistema consulta `GET /api/v1/docs/{token}/` na ZapSign usando o ambiente ativo.
- O link `Sem assinar` usa o campo `original_file` retornado pela ZapSign.
- O link `Assinado` usa o campo `signed_file` retornado pela ZapSign.
- Se ainda nao houver PDF original/assinado mas houver link de assinatura, o sistema pode exibir `Assinar` usando `signers[0].signing_link`.

## Painel de controle

- O painel de controle substitui o widget informativo padrao do Filament por indicadores operacionais do Conectta.
- Cada bloco respeita as permissoes do usuario logado:
  - `Cadastro_Leitura`: clientes, rastreadores, contratos e alertas cadastrais.
  - `Financeiro_Leitura`: valores planejados, recebidos e em aberto do mes.
  - `Boletos_Leitura`: boletos atrasados.
  - `Estoque_Leitura`: estoque disponivel e rastreadores em lixo.
  - Administradores veem atalhos administrativos como Integracoes, Restore Backup e Usuarios.
- O painel contem tres widgets: resumo operacional, atencao necessaria e atalhos rapidos.
- O painel e somente leitura; nao altera dados.

### Ajustes de layout - lista de clientes

- A lista de clientes passa a abrir com filtro padrao de Status = Ativo.
- A coluna `Qtd. Rastreadores` foi encurtada para `Qtd.` para ganhar espaco horizontal.
- A coluna `Data de Adesao` foi ajustada para `Data de AdesÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o`.
- A lista de clientes esta testando filtros expostos acima da tabela usando recurso nativo do Filament (`FiltersLayout::AboveContentCollapsible`), sem reescrever a query manualmente.

- A lista de clientes usa uma barra compacta customizada no topo da tabela para Status, Cadastro Inicio, Cadastro Final e Pesquisa. Esta decisao substitui os filtros nativos expostos do Filament nesta tela, porque os layouts padrao nao encaixavam bem no mesmo painel visual da busca.

- A lista de clientes possui botao Exportar no cabecalho, ao lado de Criar Cliente; o CSV exporta as colunas visiveis da lista respeitando Status, Instalacao Inicio, Instalacao Final e Pesquisa, sem limitar pela paginacao.


- A lista de rastreadores usa o mesmo padrao de filtros customizados da lista de clientes: barra Blade no topo da tabela, propriedades Livewire na pagina e aplicacao da regra via modifyQueryUsing, evitando o popup nativo de filtros do Filament.

- A lista de rastreadores possui botao Exportar no cabecalho, ao lado de Adicionar; o CSV respeita os filtros visiveis e o filtro interno por cliente quando acessada a partir de um cliente, sem limitar pela paginacao.

- 2026-06-29: Corrigido salvamento do Callback Secret do webhook Lytex; o campo `callback_secret` passou a ser fillable e criptografado no model `ConfiguracaoIntegracao`.

- 2026-06-29: Configurada a logo Conectta no menu do Filament, com vers?o clara e invertida para altern?ncia entre tema claro/escuro, usando altura controlada para preservar o menu.

- 2026-06-29: Criado menu independente `Contratos`, com listagem geral, filtros em linha acima da tabela, exportacao CSV, paginacao padrao, breadcrumbs do Filament, criacao de contratos por cliente/rastreador/tipo e acao de envio via ZapSign reaproveitando a logica existente. Removido o botao antigo de contratos da edicao de rastreador para centralizar o fluxo no novo menu.

- 2026-06-29: Contratos passaram a armazenar dados especificos por tipo em coluna JSON `dados`. Na criacao/edicao, Principal e Aditivo exibem Valor mensal; Comodato exibe campos proprios e sugere dados do cliente/rastreador quando possivel. A acao Enviar usa os dados salvos no contrato, sem pedir o formulario novamente.

- 2026-06-29: No formulario de contratos, Principal e Aditivo agora exibem uma previa dos dados calculados do cliente/rastreador alem do campo Valor mensal, mantendo os campos derivados apenas para conferencia.

- 2026-06-30: Criado webhook ZapSign em `/api/webhooks/zapsign`. A ZapSign deve enviar POST JSON e o header `X-Conectta-Webhook-Token` com o Webhook Secret cadastrado em Administrativo > Integracoes > ZapSign para o ambiente ativo. O webhook grava logs em `zapsign_webhook_logs`, localiza contratos por `doc_token` e atualiza status para Enviado, Assinado ou Rejeitado conforme evento/status recebido.

- 2026-06-30: Adicionados status de contrato `Expirado`, `Deletado` e `Cancelado`; webhook ZapSign agora mapeia eventos/status correspondentes para esses status especificos em vez de tratar todos como Rejeitado.

- 2026-06-30: Webhook ZapSign passou a ignorar explicitamente eventos de criacao (`created`, `doc_created`, etc.), pois documentos devem ser criados pelo Conectta. O webhook fica responsavel apenas por mudancas posteriores de status em contratos cujo `doc_token` ja exista localmente.

- 2026-06-30: Como a tela da ZapSign nao permite configurar headers no webhook, o endpoint ZapSign passou a aceitar o Webhook Secret tambem pela query string (`?token=...`, `?secret=...` ou `?webhook_secret=...`), mantendo suporte a header `X-Conectta-Webhook-Token` e `Authorization: Bearer`.

- 2026-06-30: Ajustado webhook ZapSign para priorizar o campo `status` explicito do documento sobre `event_type`. Caso `event_type=doc_signed` venha com `status=recusado`, o contrato agora fica `Rejeitado`, evitando falso `Assinado`.

- 2026-06-30: Ajustado webhook ZapSign para priorizar eventos terminais (`doc_deleted`, expirado/cancelado) sobre `status=signed` quando ambos vierem no mesmo payload. Assim, exclusao na ZapSign atualiza contrato para `Deletado` mesmo que o documento esteja assinado.

- 2026-06-30: Regra final do webhook ZapSign: o status do contrato deve ser decidido somente por `event_type`. Mapeamento oficial: `doc_signed` = Assinado, `doc_refused` = Rejeitado, `doc_expired` = Expirado e `doc_deleted` = Deletado. Eventos sem esse mapeamento sao apenas logados/ignorados e nao alteram contratos. Esta regra substitui as tentativas anteriores de inferir status combinando `status`, `deleted` e outros campos do payload.

- 2026-06-30: Tela de login recebeu imagem de fundo profissional otimizada a partir do arquivo `thumb-1920-924472.jpg`, gerando assets `public/images/auth/login-bg.jpg` e `login-bg-mobile.jpg`. O CSS customizado `public/css/conectta-login.css` aplica fundo full-screen, overlay escuro, card translúcido e logo maior na tela simples de autenticação do Filament.
