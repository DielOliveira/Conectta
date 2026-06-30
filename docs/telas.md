# Telas do sistema atual

Este arquivo registra prints e observacoes das telas do OutSystems para guiar a reconstrucao no Laravel/Filament.

## 01 - Lista de Clientes

Print salvo em: `C:\Conectta\referencias\prints\01-clientes-lista.png`

Origem: print enviado em 22/06/2026.

### Navegacao superior

Itens visiveis:

- Logo Conectta
- Clientes
- Rastreadores
- Financeiro
- Rotinas
- Estoque
- Sistema PMB
- Icone de saida/logout

Observacoes:

- A aba ativa e `Clientes`.
- `Financeiro` possui indicador de menu/dropdown.
- `Estoque` possui indicador de menu/dropdown.

### Conteudo principal

Titulo:

- `Lista de Clientes`

### Busca superior

Elementos:

- Campo `Search`
- Botao `Limpar`
- Botao `Add`

Observacoes:

- O campo search fica no topo direito.
- O botao `Add` provavelmente abre cadastro de cliente.
- O botao `Limpar` provavelmente limpa o search global.

### Filtros

Campos visiveis:

- `Status`
  - valor exibido: `Ativo`
- `Instalacao Inicio`
  - placeholder: `dd/mm/aaaa`
- `Instalacao Final`
  - placeholder: `dd/mm/aaaa`

Botoes:

- `Buscar`
- `Limpar`
- `Exportar`

Observacoes:

- Existem tres pequenos textos/pontos acima dos botoes no print, possivelmente labels quebrados ou componentes vazios do OutSystems.
- Apesar dos labels do print indicarem instalacao, o filtro atual confirmado no OutSystems usa `Cliente.DataAdesao`.

### Filtro atual no OutSystems

Print salvo em: `C:\Conectta\referencias\prints\05-clientes-filtro-outsystems.png`

Condicoes confirmadas:

- `Cliente.StatusClienteId = StatusClienteId or StatusClienteId = NullIdentifier()`
- `Cliente.Dataexclusao = NullDate()`
- busca por `Cliente.Nome` ou `Cliente.CPF_CNPJ`
- se search vazio, nao filtra por texto
- data inicial/final filtra `Cliente.DataAdesao`
- se data inicial/final vazia, nao filtra por data correspondente

Status disponiveis no filtro:

- `Ativo`
- `Inativo`
- `Todos`

### Tabela

Colunas visiveis:

- `Nome`
- `CPF ou CNPJ`
- `Data de Adesao`
- `Status`
- `Qtd. Rastreadores`
- coluna de acoes sem titulo

Ordenacao:

- `Nome` mostra icone de ordenacao.
- `CPF ou CNPJ` mostra icone de ordenacao.
- `Data de Adesao` mostra icone de ordenacao.
- `Status` mostra icone de ordenacao.

Exemplos de linhas visiveis:

| Nome | CPF ou CNPJ | Data de Adesao | Status | Qtd. Rastreadores |
| --- | --- | --- | --- | --- |
| 2R Clube de Beneficios | 41.948.539/0001-54 | 28/05/2021 | Ativo | 82 |
| 7 Tec Transportes | 42.122.835.0001.64 | 22/09/2025 | Ativo | 6 |
| Ademilso Carneiro de Ornelas | 034.012.676-04 | 20/02/2024 | Ativo | 1 |
| Adilson Aparecido Pagani | 596.543.841-91 | 24/06/2024 | Ativo | 1 |
| Adilson Moreira Oliveira | 051.725.745-90 | 06/06/2025 | Ativo | 1 |
| Adilson Rodrigues de Sousa | 589.786.741-00 | 24/10/2018 | Ativo | 1 |

### Acoes por linha

Icones visiveis:

- usuario/pessoa
- carro
- sinal de mais
- lixeira

Hipoteses a confirmar:

- pessoa: abrir detalhe/cadastro do cliente
- carro: listar veiculos/rastreadores do cliente
- mais: adicionar veiculo/rastreador para aquele cliente, usando a mesma tela de edicao/cadastro de rastreadores
- lixeira: excluir cliente de verdade

### Observacoes para implementacao

- Esta tela pode ser implementada inicialmente como Resource/List do Filament para `Cliente`.
- Filtros importantes: status e periodo de cadastro/adesao.
- A coluna `Qtd. Rastreadores` conta apenas veiculos/rastreadores ativos vinculados ao cliente.
- O icone `+` pode ficar inativo/desabilitado ate a tela de cadastro de rastreadores/veiculos ser implementada.
- Antes de implementar, confirmar apenas detalhes do icone de carro se necessario.

## 02 - Editar Cliente - Dados Gerais

Print salvo em: `C:\Conectta\referencias\prints\02-cliente-editar-dados-gerais.png`

Origem: clique no icone de pessoa na tela `01 - Lista de Clientes`.

### Titulo

- `Editar Cliente`

### Abas

Abas visiveis:

- `Dados Gerais`
- `Boletos`

Observacoes:

- A aba ativa e `Dados Gerais`.
- A aba `Boletos` provavelmente mostra lancamentos/faturas/boletos do cliente.

### Campos - Dados gerais

Campos visiveis:

| Label na tela | Campo provavel no banco | Tipo visual | Obrigatorio |
| --- | --- | --- | --- |
| Nome | Cliente.Nome | texto | Sim |
| Data de adesao | Cliente.DataAdesao | data | Sim |
| Cliente no SPC | Cliente.isSPC | checkbox | Nao |
| CPF CNPJ | Cliente.CPF_CNPJ | texto | Sim |
| RG | Cliente.RG | texto | Nao |
| Nascimento | Cliente.Nascimento | texto com mascara de data | Nao |
| Email | Cliente.Email | texto/email | Nao |
| Origem | Cliente.ClienteOrigemId | select | Nao |
| Logradouro | Cliente.RUA | texto | Nao |
| Complemento | Cliente.Complemento | texto | Nao |
| Numero | Cliente.Numero | texto | Nao |
| Setor | Cliente.Setor | texto | Nao |
| Cidade | Cliente.Cidade | texto | Nao |
| Estado | Cliente.EstadoId | select | Nao |
| CEP | Cliente.CEP | texto | Nao |
| Telefone Celular | Cliente.Telefone1 | texto | Nao |
| Telefone Secundario | Cliente.Telefone2 | texto | Nao |
| Empresa | Cliente.Empresa | texto | Nao |
| Vendedor | Cliente.VendedorId | select | Nao |
| Indicacao | Cliente.Indicacao | texto | Nao |
| Status Contrato | Cliente.StatusContratoId | select | Nao |
| Dia de Pagamento | Cliente.DiaPagamento | select | Sim |

### Valores visiveis no exemplo

Cliente exibido:

- Nome: `2R Clube de Beneficios`
- Data de adesao: `28/05/2021`
- Cliente no SPC: desmarcado
- CPF CNPJ: `41.948.539/0001-54`
- RG: `-`
- Nascimento: vazio
- Email: aparece truncado no print, iniciando com `financeiro@2rclubec`
- Origem: `ADS`
- Logradouro: `R Visconde de Maua, S/N`
- Complemento: vazio
- Numero: vazio
- Setor: `Parque Real de Goiania`
- Cidade: `Aparecida de Goiania`
- Estado: `Goias`
- CEP: `74.910-190`
- Telefone Celular: `(48) 9.3380-3529`
- Telefone Secundario: `6240042042`
- Empresa: `Empresa`
- Vendedor: `Ribeiro`
- Indicacao: vazio
- Status Contrato: `Nao Enviado`
- Dia de Pagamento: `10`

### Botoes

Botoes visiveis:

- `Voltar`
- `Salvar`

Hipoteses:

- `Voltar` retorna para `Lista de Clientes`.
- `Salvar` executa action equivalente a `SalvarAtualizarCliente`.

### Layout

Observacoes:

- Formulario em card unico.
- Separadores horizontais dividem blocos:
  - dados principais/documentos/origem;
  - endereco;
  - telefones;
  - empresa/vendedor/contrato/pagamento.
- Campos usam labels acima do input.
- Campos obrigatorios aparecem com asterisco vermelho.

### Observacoes para implementacao

- Esta tela pode ser implementada como formulario do Filament Resource `Cliente`.
- A aba `Boletos` pode virar relation manager ou pagina customizada dentro do cliente.
- O campo `Dia de Pagamento` aparece como select, nao input livre.
- `StatusClienteId` nao aparece nesta tela porque e automatico pela regra de veiculos/rastreadores ativos.
- `isGrupoZelo` nao existe mais no uso atual e nao entra na reconstrucao inicial.
- `Anotacoes` e `ReplicarPagamento` pertencem ao fluxo/tela de financeiro e aparecem depois.

### Duvidas a confirmar

- Quais valores existem no select `Dia de Pagamento`: 1 a 31 ou somente dias usados?
- O botao `Salvar` valida CPF/CNPJ?
- O campo `CPF CNPJ` aceita mascara diferente para pessoa fisica e juridica?
- O campo `Email` precisa validar formato?
- O campo `Cliente no SPC` altera apenas `isSPC` ou dispara algum fluxo?
- Detalhar depois onde `Anotacoes` e `ReplicarPagamento` aparecem no financeiro.

### Confirmacoes recebidas

- O botao `Add` abre formulario igual ao de editar.
- Campos obrigatorios para cadastro: `Nome`, `DataAdesao`, `CPF_CNPJ`, `Telefone1` e `DiaPagamento`.
- `DiaPagamento` vai de 1 a 31.
- O cliente fica automaticamente inativo quando nao possui nenhum veiculo com status ativo.
- Cliente novo nasce inativo no cadastro inicial.
- Hoje essa validacao acontece ao salvar a edicao do veiculo.
- `StatusClienteId` e automatico e nao precisa aparecer no cadastro de cliente.
- `CPF_CNPJ` e unico e deve ser salvo sem mascara.
- `CPF_CNPJ` deve ser validado como CPF ou CNPJ real antes de salvar.
- `Nascimento` deve ter apenas mascara de data no campo, sem calendario/date picker.
- `Email`, quando preenchido, deve ser validado como email valido.
- `CEP` deve usar mascara `00.000-000` no formulario e ser salvo sem mascara.
- `Telefone Celular` deve usar mascara `(00) 0.0000-0000` no formulario e ser salvo sem mascara.
- O campo `Status Cliente` nao deve aparecer no formulario de inclusao/edicao.
- `Qtd. Rastreadores` conta apenas ativos.
- O icone `+` adiciona veiculo/rastreador para aquele cliente e pode ficar inativo ate criarmos esta tela.
- `isGrupoZelo` nao existe mais.
- `Anotacoes` e `ReplicarPagamento` ficam para a tela/fluxo financeiro.

## 03 - Lista de Rastreadores

Print salvo em: `C:\Conectta\referencias\prints\03-rastreadores-lista-menu.png`

Origem do print: acesso pelo menu superior `Rastreadores`.

Outras formas de acesso informadas:

- Pelo icone de carro/carrinho na tela `01 - Lista de Clientes`.
- Quando acessada pelo cliente, a lista fica filtrada por aquele cliente.

### Navegacao superior

Itens visiveis:

- Logo Conectta
- Clientes
- Rastreadores
- Financeiro
- Rotinas
- Estoque
- Sistema PMB
- Icone de saida/logout

Observacoes:

- A aba ativa e `Rastreadores`.
- `Financeiro` possui indicador de menu/dropdown.
- `Estoque` possui indicador de menu/dropdown.

### Conteudo principal

Titulo:

- `Rastreadores`

### Busca superior

Elementos:

- Select com valor `Todos`
- Campo `Search`

Hipoteses:

- O select `Todos` pode filtrar status do rastreador, tipo, cliente ou situacao.
- O campo search provavelmente busca por rastreador/IMEI, cliente, veiculo ou placa.

### Filtros

Campos visiveis:

- `Instalacao Inicio`
  - placeholder: `dd/mm/aaaa`
- `Instalacao Final`
  - placeholder: `dd/mm/aaaa`
- `Remocao Inicio`
  - placeholder: `dd/mm/aaaa`
- `Remocao Final`
  - placeholder: `dd/mm/aaaa`

Botoes:

- `Buscar`
- `Voltar`
- `Limpar`
- `Exportar`

Observacoes:

- Existem pequenos textos/pontos acima dos botoes no print, possivelmente labels quebrados ou componentes vazios do OutSystems.
- `Voltar` provavelmente aparece porque a tela tambem e usada quando vem filtrada por cliente.

### Tabela

Colunas visiveis:

- `Rastreador`
- `Cliente`
- `Veiculo`
- `Tipo`
- `Placa`
- `Status`
- `Instalacao`
- `Remocao`
- coluna de acoes sem titulo

Exemplos de linhas visiveis:

| Rastreador | Cliente | Veiculo | Tipo | Placa | Status | Instalacao | Remocao |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 862292050969070 | 2R Clube de Beneficios | Toyota / Yaris | Carro | PQY-0719 | Ativo | 23/12/2022 |  |
| 352503092763322 | 2R Clube de Beneficios | Ford / Ecosport | Carro | QUS-7F74 | Cancelado | 29/12/2022 | 17/07/2024 |
| 862292052405891 | 2R Clube de Beneficios | Honda / HR-V | Carro | RFC-7B77 | Cancelado | 20/01/2023 | 24/03/2025 |
| 862292052409091 | 2R Clube de Beneficios | Honda / CG 160 Fan | Moto | BBY-6D17 | Cancelado | 26/01/2023 | 12/11/2025 |
| 862292052383874 | 2R Clube de Beneficios | Honda / CG 160 Fan | Moto | BCL-3F78 | Cancelado | 26/01/2023 | 03/11/2025 |

### Acoes por linha

Icones visiveis:

- lapis/editar
- X/remover/cancelar

Hipoteses:

- lapis: editar cadastro do rastreador/veiculo instalado.
- X: remover/cancelar rastreador do veiculo, possivelmente preenchendo `DataRetirada`, `TecnicoRemocaoId` e status `Cancelado`.

### Observacoes de modelagem

- Confirmado pelo dono do sistema: apesar do nome da tela ser `Rastreadores`, no fundo ela representa `Veiculo`, com itens relacionados/inner da tabela `Rastreador`.
- As colunas misturam dados de `Veiculo`, `Cliente`, `TipoVeiculo`, `StatusRastreador` e `Rastreador`.
- A coluna `Rastreador` exibe IMEI/identificador do rastreador vindo de `Rastreador`.
- A data de instalacao provavelmente vem de `Veiculo.DataInstalacao`.
- A data de remocao provavelmente vem de `Veiculo.DataRetirada`.
- A placa provavelmente vem de `Veiculo.Placa`.
- O tipo provavelmente vem de `TipoVeiculo.Label`.
- O status provavelmente vem de `StatusRastreador.Label`.
- A tela e implementada como recurso principal de `Veiculo`, mas com nome visual/menu `Rastreadores`.
- Na reconstrucao, a listagem usa cabecalhos curtos (`Status`, `Instalacao`, `Remocao`) para ganhar espaco e reduzir a chance de barra de rolagem horizontal.

### Observacoes para implementacao

- Pode ser uma tela/listagem baseada em `Veiculo`, com joins/relacionamentos para cliente, tipo, status e rastreador.
- No Filament, talvez seja melhor tratar como Resource de `Veiculo` com label de menu `Rastreadores`, ou criar uma pagina customizada se a logica de instalacao/remocao for especifica.
- Precisa suportar parametro opcional de cliente para abrir a mesma tela filtrada a partir da lista de clientes.
- O select `Todos` filtra `StatusRastreador`.
- O botao `X` exclui o registro de veiculo/rastreador de verdade e deve pedir confirmacao.
- Ao salvar, excluir ou cancelar um veiculo/rastreador, recalcular o status do cliente:
  - se o cliente possui pelo menos um veiculo/rastreador ativo, `Cliente.StatusClienteId = Ativo`;
  - se nao possui nenhum veiculo/rastreador ativo, `Cliente.StatusClienteId = Inativo`.

### Duvidas a confirmar

- O search busca em quais campos?
- Ao clicar no lapis, abre tela de editar veiculo/rastreador?
- Quando acessada pelo cliente, o botao `Voltar` retorna para a lista de clientes ou para o detalhe do cliente?

## 04 - Editar Rastreador / Veiculo

Print salvo em: `C:\Conectta\referencias\prints\04-rastreador-editar-veiculo.png`

Origem: clique no icone de lapis na tela `03 - Lista de Rastreadores`.

Confirmacao de modelagem:

- O nome funcional da tela e `Editar Rastreador`.
- Tecnicamente, esta tela edita o `Veiculo`, com dados relacionados de `Rastreador`, `Chip`, `Cliente`, `TipoVeiculo`, `Tecnico` e `StatusRastreador`.

### Navegacao superior

Itens visiveis:

- Logo Conectta
- Clientes
- Rastreadores
- Financeiro
- Rotinas
- Estoque
- Sistema PMB
- Icone de saida/logout

### Titulo

- `Editar Rastreador`

### Acoes superiores

Botoes visiveis:

- `Contratos`

Hipoteses:

- `Contratos` abre fluxo de contrato/ZapSign relacionado ao veiculo/rastreador.
- Pode usar `Veiculo.doc_token`, `Veiculo.ContratoTipo` e `Veiculo.TipoContratoId`.

### Campos

| Label na tela | Campo provavel no banco | Tipo visual | Obrigatorio |
| --- | --- | --- | --- |
| Cliente | Veiculo.ClienteId | select | Nao visivel como obrigatorio |
| Data de Instalacao | Veiculo.DataInstalacao | data | Nao |
| Veiculo | Veiculo.Veiculo | texto | Sim |
| Placa | Veiculo.Placa | texto | Sim |
| Cor | Veiculo.Cor | texto | Sim |
| Ano | Veiculo.Ano | texto | Sim |
| Tipo Veiculo | Veiculo.TipoVeiculoId | select | Sim |
| IMEI | Veiculo.RastreadorId ou Veiculo.IMEI | select pesquisavel | Nao |
| Numero Chip | Veiculo.NumeroChip ou Chip.ICCID | texto | Nao |
| Login | Veiculo.Login | texto | Nao |
| Senha | Veiculo.Senha | texto | Nao |
| Observacao | Veiculo.Observacao | texto | Nao |
| Valor de Instalacao | Veiculo.ValorInstalacao | decimal/texto | Nao |
| Instalador | Veiculo.Instalador ou Veiculo.TecnicoInstalaId | texto/select desabilitado | Nao |
| Data Retirada | Veiculo.DataRetirada | data | Nao |
| Tecnico Remocao | Veiculo.TecnicoRemocaoId | select | Nao |
| Status Rastreador | Veiculo.StatusRastreadorId | select | Nao |
| Associado / Cliente | Veiculo.Associado | texto | Nao |
| Contato | Veiculo.Contato | texto | Nao |

### Valores visiveis no exemplo

- Cliente: `2R Clube de Beneficios`
- Data de Instalacao: `23/12/2022`
- Veiculo: `Toyota / Yaris`
- Placa: `PQY-0719`
- Cor: `Branca`
- Ano: `19/20`
- Tipo Veiculo: `Carro`
- IMEI: `862292050969070 (Outros)`
- Numero Chip: `9.9874-8737`
- Login: `hygorspencer`
- Senha: `spencer@19`
- Observacao: vazio
- Valor de Instalacao: vazio
- Instalador: `Outros` desabilitado/apagado visualmente
- Data Retirada: vazio
- Tecnico Remocao: `--`
- Status Rastreador: `Ativo`
- Associado / Cliente: `Hygor Spende`
- Contato: vazio

### Botoes inferiores

Botoes visiveis:

- `Voltar`
- `Salvar`

Hipoteses:

- `Voltar` retorna para a listagem de rastreadores, preservando ou nao filtro anterior.
- `Salvar` executa action equivalente a `SalvarAtualizarVeiculo`.

### Comportamentos visuais importantes

- `IMEI` aparece como select com botao de limpar (`x`) e seta de dropdown.
- `Instalador` aparece com fundo cinza, sugerindo campo desabilitado/readonly.
- `Tecnico Remocao` tambem aparece com fundo cinza, mas com seta de dropdown.
- `Cliente`, `Tipo Veiculo`, `Status Rastreador` sao selects.
- Campos obrigatorios visiveis: `Veiculo`, `Placa`, `Cor`, `Ano`, `Tipo Veiculo`.

### Observacoes para implementacao

- Resource principal recomendado: `Veiculo`, com label/menu funcional `Rastreadores`.
- `Veiculo` e texto livre, por exemplo `Toyota / Yaris`; nao ha marca/modelo separados nesta tela.
- `IMEI` vem sempre de `Rastreador`/estoque de rastreadores.
- `Numero Chip` vem sempre de `Chip`/estoque de chips.
- Os campos legados `Veiculo.IMEI` e `Veiculo.NumeroChip` nao serao usados na reconstrucao inicial; a fonte correta sera `Veiculo.RastreadorId` e `Veiculo.ChipId`.
- `Login` e `Senha` sao digitados manualmente.
- `Associado / Cliente` e texto livre.
- `Contato` e texto livre.
- Status operacionais do rastreador nesta tela: `Ativo`, `Cancelado` e `Disponivel`.
- O status `Lixo` existe, mas nao e exibido nem tratado nesta tela; sera tratado no fluxo de estoque.
- Apenas status `Ativo` conta para ativar o cliente.
- Se o status do `Veiculo` for alterado para `Cancelado`, a tela deve exigir `Data Retirada` e `Tecnico Remocao`.
- Quando um veiculo/rastreador e cancelado, o status no `Veiculo` fica `Cancelado`, mas o status do `Rastreador` selecionado volta para `Disponivel`.
- Ao cancelar, alem de deixar o `Rastreador` como `Disponivel`, atualizar `Rastreador.TecnicoId` para o tecnico selecionado na remocao.
- O mesmo IMEI pode existir no historico de varios veiculos cancelados, mas nao pode haver o mesmo IMEI em mais de um veiculo ativo.
- O mesmo chip pode existir no historico de varios veiculos cancelados, mas nao pode haver o mesmo chip em mais de um veiculo ativo.
- Para vincular um IMEI a um veiculo novo/ativo, o rastreador precisa estar com status `Disponivel` na tabela `Rastreador`.
- Ao selecionar o IMEI, o sistema deve buscar `Rastreador.TecnicoId`, preencher o instalador/tecnico de instalacao e manter esse campo bloqueado para edicao manual.
- O botao `Contratos` fica para o final do fluxo.

### Duvidas a confirmar

- O texto entre parenteses em `862292050969070 (Outros)` vem de qual campo? Tecnico? Estoque? Fornecedor?
- Confirmar se campos legados `Veiculo.IMEI` e `Veiculo.NumeroChip` existem apenas para migracao/historico ou se podem ficar fora das migrations novas.

## 05 - Financeiro

Print salvo em: `C:\Conectta\referencias\prints\07-financeiro-lista.png`

Origem: menu superior `Financeiro`, submenu `Financeiro`.

### Conceito da tela

- A tela mostra clientes na coluna da esquerda e dois meses de lancamentos nas colunas da direita.
- Ao abrir, exibe o mes atual e o proximo mes.
- As setas azuis navegam um mes por clique, mantendo sempre dois meses visiveis.
- Mesmo que o cliente nao tenha lancamento no mes, a linha do cliente deve aparecer.
- A grade deve ser compacta e caber na pagina em desktop, evitando barra horizontal quando houver largura suficiente para as tres regioes principais.
- A tela deve priorizar densidade semelhante ao OutSystems atual: filtros baixos, cabecalhos compactos e celulas estreitas.
- As setas de navegacao dos meses ficam alinhadas ao titulo `Clientes`, no limite direito do painel de clientes, antes das colunas mensais.

### Filtros superiores

- `Status`:
  - filtra pelo status do cliente;
  - opcoes equivalentes a tela de clientes: `Ativo`, `Inativo`, `Todos`;
  - padrao: `Ativo`.
- `Clientes`:
  - pesquisa por nome;
  - pesquisa por CPF/CNPJ;
  - a query antiga tambem considera `Anotacoes`.
- `Vencimento`:
  - filtra o dia de pagamento;
  - somente por dia exato.
- `Linhas`:
  - limita quantidade de clientes exibidos;
  - padrao: `15`.
  - funciona como quantidade de linhas por pagina.
- `Limpar` limpa os filtros superiores.
- `Exportar` exporta a grade completa atual, com os dois meses visiveis.
- `Historico` fica desativado por enquanto.

### Grade de clientes

Colunas:

- `Qtd`: quantidade de veiculos/rastreadores ativos do cliente.
- `Vendedor`: nome do vendedor vinculado ao cliente.
- `Cliente`: nome do cliente.
- `Venc.`:
  - edita `Cliente.DiaPagamento`;
  - salva automaticamente apos 2 segundos sem digitacao.
  - tambem salva imediatamente ao sair do campo ou apertar Enter.
- `Anotacoes`:
  - edita `Cliente.Anotacoes`;
  - salva automaticamente apos 2 segundos sem digitacao.
  - tambem salva imediatamente ao sair do campo ou apertar Enter.
- Botao/seta:
  - alterna `Cliente.ReplicarPagamento`;
  - preto quando ativo;
  - cinza quando inativo.

### Colunas mensais

Cada mes exibe:

- `N. Boleto`;
- `Planejado`;
- `Efetuado`;
- `Observacao`.

Cada mes tambem exibe uma linha de total no rodape:

- Total planejado em vermelho.
- Total efetivado em verde.
- Os totais consideram todos os clientes/lancamentos filtrados, nao apenas a pagina visivel.

Regras de agregacao:

- agrupar lancamentos por cliente, mes e ano antes de juntar com clientes;
- `NumeroBoleto`: `MAX`;
- `ValorPlanejado`: `MAX`;
- `ValorEfetivado`: `SUM`;
- `Observacao`: `MAX`.

### Popup de lancamento

- Icone de edicao abre popup do cliente/mes.
- O popup possui tres abas:
  - `Lancamento`;
  - `Parcelamento`;
  - `Boleto`.
- A aba `Parcelamento` permite lancar valores adicionais quando ja existe valor efetivado.
- A aba `Boleto` mostra o estado do boleto vinculado ao lancamento.
- A aba `Lancamento` mostra o nome do cliente.
- `Ano Referencia` e `Mes Referencia` ficam travados conforme a celula aberta na grade.
- Se ja existir lancamento para o cliente/mes/ano, a aba carrega o registro para edicao.
- Se nao existir lancamento para o cliente/mes/ano, a aba cria um novo registro ao salvar.
- Campos da aba `Lancamento`:
  - `Data Lancamento`;
  - `Numero Boleto`;
  - `Ano Referencia`;
  - `Mes Referencia`;
  - `Valor Planejado`;
  - `Valor Efetivado`;
  - `Observacao`.
- Regra de `Data Lancamento`:
  - se ainda nao existe `Valor Efetivado`, sugerir sempre o dia atual, mesmo abrindo referencia passada ou futura;
  - se ja existe `Valor Efetivado`, preservar a data salva.
- Botoes da aba `Lancamento`:
  - `Fechar`;
  - `Salvar`.
- Ao salvar, o popup fecha e a grade financeira e atualizada com os novos valores.

### Aba `Boleto` no popup financeiro

- Mostra o painel `Novo Boleto`.
- Se ainda nao existe boleto gerado para o lancamento:
  - exibe `Valor`, vindo de `Lancamentos.ValorPlanejado` da referencia/cliente;
  - exibe campo editavel de `Vencimento`;
  - status `Nao gerado`;
  - acao `Gerar Boleto`.
- Se ja existe boleto gerado:
  - exibe `Valor`;
  - exibe `Vencimento`;
  - exibe status do boleto com as cores padronizadas;
  - exibe links `Invoice` e `Boleto`, ambos abrindo em nova aba;
  - exibe acoes `Refresh`, `Realizar baixa` e `Cancelar Boleto`.
- A chamada real de geracao/refresh/baixa/cancelamento do boleto sera implementada depois, quando a funcao atual do OutSystems/Lytex for detalhada.
- Antes de gerar boleto, validar:
  - cliente informado existe;
  - nao existe boleto pago para a mesma referencia (`Invoice.Status = paid` ou equivalente local `Pago`);
  - nao existe boleto ja gerado/em aberto para a mesma referencia;
  - soma de `Lancamentos.ValorEfetivado` da referencia deve ser `0`;
  - `Lancamentos.ValorPlanejado` do lancamento principal deve ser maior que `0`;
  - referencia deve estar entre 6 meses antes e 2 meses depois do mes atual, usando `(AnoReferencia * 12) + MesReferencia`;
  - email do cliente deve ser valido;
  - telefone celular do cliente deve conter 11 digitos numericos;
  - CPF/CNPJ do cliente deve ser valido;
  - data de nascimento, quando preenchida, deve ser valida.
- Ao passar nas validacoes, preparar a estrutura de criacao da fatura Lytex conforme o payload atual do OutSystems:
  - `client.treatmentPronoun`: `you`;
  - `client.name`: nome do cliente;
  - `client.type`: `pf` ou `pj`;
  - `client.cpfCnpj`: CPF/CNPJ sem mascara;
  - `client.email`: email do cliente;
  - `client.cellphone`: telefone celular sem mascara;
  - `items[0].name`: `Mensalidade {MesReferencia}/{AnoReferencia}`;
  - `items[0].quantity`: `1`;
  - `items[0].value`: valor planejado em centavos como texto;
  - `mulctAndInterest.enable`: `true`;
  - `mulctAndInterest.mulct.type`: `percentage`;
  - `mulctAndInterest.mulct.value`: `5`;
  - `mulctAndInterest.interest.type`: `percentage`;
  - `mulctAndInterest.interest.value`: `0.1`;
  - `paymentMethods.pix.enable`: `true`;
  - `paymentMethods.boleto.enable`: `true`;
  - `paymentMethods.creditCard.enable`: `false`;
  - `dueDate`: data de vencimento em formato `YYYY-MM-DD`.
- O botao `Gerar Boleto` consome a Lytex de verdade quando `ClientId` e `ClientSecret` estiverem configurados:
  - antes da fatura, obter/reaproveitar token em `POST https://api-pay.lytex.com.br/v2/auth/obtain_token`;
  - depois gerar a fatura em `POST https://api-pay.lytex.com.br/v2/invoices/`;
  - enquanto ClientId ou ClientSecret estiverem vazios, exibir erro claro e nao chamar a API;
  - access token e refresh token retornados pela Lytex ficam cacheados em `tokens_lytex`;
  - quando o token cacheado ainda estiver valido, reutilizar o token;
  - falhas de conexao/autenticacao/validacao retornam mensagem em portugues;
  - sucesso cria/atualiza o registro de `Invoice` vinculado ao lancamento;
  - sucesso atualiza `Lancamentos.NumeroBoleto` para `Lytex`;
  - depois de salvar, o popup passa a exibir o estado de boleto existente, com links `Invoice` e `Boleto`.
- No popup, acao `Refresh`:
  - chama `GET /v2/invoices/{Invoice.fatura_id}`;
  - atualiza os dados locais da `Invoice`.
- No popup, acao `Cancelar Boleto`:
  - pede confirmacao antes de acionar a Lytex;
  - chama `PUT /v2/invoices/cancel/{Invoice.fatura_id}`;
  - em seguida chama `GET /v2/invoices/{Invoice.fatura_id}`;
  - atualiza os dados locais da `Invoice` com o retorno de detalhes.
- Quando um boleto fica cancelado:
  - ele aparece como item colapsavel na aba `Boleto`;
  - o formulario `Novo Boleto` volta a ficar disponivel para a mesma referencia;
  - ao gerar novamente, criar uma nova `Invoice` vinculada ao mesmo lancamento.
- Botao `Realizar baixa`:
  - fica habilitado apenas quando o status do boleto for `Pago`;
  - a funcao interna sera definida em etapa posterior.

### Filtros por mes

- Cada mes possui filtros proprios.
- Ao aplicar filtros em um mes, limpar filtros do outro mes.
- O campo `Pesquisar` por mes pesquisa `Observacao`, `NumeroBoleto` e `ValorEfetivado`.
- Os filtros de `N. Boleto` e `Efetuado` possuem tres estados:
  - vazio: apenas registros sem valor;
  - menos: apenas registros com valor;
  - marcado: todos.
- Padrao: marcado/todos.
- Os filtros mensais afetam a lista de clientes, a paginacao, os totais e a exportacao.
- No estado vazio, clientes sem lancamento no mes tambem devem aparecer, pois o campo agregado e considerado vazio.

### Paginacao

- A tela possui paginacao no rodape.
- Exibe intervalo atual e total de registros, por exemplo `1 a 15 de 1152 registros`.
- O campo `Linhas` define a quantidade de registros por pagina.
- A paginacao deve manter os filtros atuais.

## 06 - Financeiro - Relatorio geral

Origem: menu superior `Financeiro`, submenu `Relatorio geral`.

### Titulo

- `Lancamentos abertos`

### Filtros

- `Data Inicio`:
  - filtra a partir de `Lancamentos.MesReferencia` e `Lancamentos.AnoReferencia`.
- `Data Fim`:
  - filtra ate `Lancamentos.MesReferencia` e `Lancamentos.AnoReferencia`.
- `N. Boleto`:
  - filtra `Lancamentos.NumeroBoleto` por igualdade.
  - vazio traz todos.
- `Status Cliente`:
  - `Todos`;
  - `Ativo`;
  - `Inativo`.
- `Status Boleto`:
  - filtra `Invoice.Status`;
  - vazio/`Todos` traz todos.
- `Buscar` atualiza a lista.
- `Exportar` gera arquivo CSV compativel com Excel.

### Tabela

Colunas:

- `Cliente`;
- `Vencimento`;
- `Mes / Ano`;
- `Valor`;
- `N. Boleto`;
- `Status Boleto`.

### Cores de status boleto

As labels de `Status Boleto` devem usar as mesmas cores em todo o sistema:

- `Aguardando Pagamento`: amarelo claro.
- `Pago`: verde.
- `Atrasado`: vermelho.
- `Cancelado`: vermelho.
- `Processando`: laranja.

## 07 - Financeiro - Boletos

Print salvo em: `C:\Conectta\referencias\prints\07-financeiro-boletos.png`

Menu:

- `Financeiro > Boletos`

Titulo da tela:

- `Boletos Gerados`

### Filtros

- `Criado em Inicio`
- `Criado em Fim`
- `Status`
  - padrao: `Todos`
- `Pesquisa`
- botao `Pesquisar`

### Tabela

Colunas:

- `Cliente`
- `CPF_CNPJ`
- `Referencia`
- `Vencimento`
- `Invoice`
- `Status`
- `Valor`
- `Data Gerado`

### Links da coluna Invoice

- Link `Invoice`: `https://checkout-pay.lytex.com.br/fatura/` + `Invoice.hashId`.
- Link `Boleto`: `https://public-api-pay.lytex.com.br/v1/invoices/print/` + `Invoice.hashId`.
- A celula exibe apenas `Invoice / Boleto`, sem label extra, para ganhar espaco.
- Os dois links abrem em nova aba.

### Query base informada

- Fonte principal: `Invoice`.
- Joins:
  - `Invoice.LancamentosId = Lancamentos.Id`
  - `Lancamentos.ClienteId = Cliente.Id`
  - `Invoice.UserId = User.Id`
- O SQL legado usa `TOP (7)`.

### Regras iniciais da reconstrucao

- Filtrar por periodo de criacao do boleto usando `Invoice.created_at`.
- Filtrar por status do boleto.
- Campo `Pesquisa` busca inicialmente por cliente, CPF/CNPJ, fatura/invoice, numero do boleto e usuario.
- A tela possui paginacao no rodape, usando 7 registros por pagina para acompanhar o `TOP (7)` inicial do legado.
- Exibir as labels de status do boleto com as mesmas cores definidas para todo o sistema:
  - `Aguardando Pagamento`: amarelado.
  - `Pago`: verde.
  - `Atrasado` e `Cancelado`: vermelho.
  - `Processando`: laranja.

### Query base

- Fonte principal: `Lancamentos`.
- Left join com `Cliente` por `Lancamentos.ClienteId = Cliente.Id`.
- Left join com `Invoice` por `Lancamentos.Id = Invoice.LancamentosId`.
- Listar apenas lancamentos com:
  - `ValorPlanejado > 0`;
  - `ValorEfetivado = 0`.
- Na reconstrucao Laravel, `ValorEfetivado NULL` tambem e tratado como aberto, pois campos vazios podem virar `NULL` no MySQL.
- Ordenacao inicial por `Cliente.DiaPagamento` crescente.
- A tela atual do OutSystems usa limite `TOP (8)`; a primeira versao Laravel exibe 8 linhas.

## 08 - Administrativo - Integracoes

- Menu: `Administrativo > Integracoes`.
- Primeira integracao configuravel: `Lytex`.
- A tela possui um seletor `Ambiente ativo`.
- O ambiente ativo define qual configuracao sera usada pelas chamadas reais da Lytex:
  - gerar boleto;
  - consultar/refresh de boleto;
  - cancelar boleto.
- A tela possui dois formularios independentes:
  - `Homologacao`;
  - `Producao`.
- Cada ambiente possui seus proprios campos:
  - `URL base`;
  - `ClientId`;
  - `ClientSecret`;
  - `Autenticacao`;
  - `Timeout`.
- Apenas um ambiente Lytex pode ficar ativo por vez.
- O `ClientSecret` deve ser salvo criptografado e nunca exibido novamente.
- Quando o campo `ClientSecret` ficar vazio ao salvar, manter o valor anterior daquele ambiente.
- Ao trocar o ambiente ativo, limpar o token cacheado da Lytex.
- Ao trocar URL, ClientId ou ClientSecret do ambiente ativo, limpar o token cacheado da Lytex.
- A configuracao salva em banco tem prioridade sobre o `.env`.
- O `.env` serve como fallback inicial antes da primeira configuracao salva.
- Depois, a mesma tela deve ser reaproveitada para Z-Api e ZapSign.
## 09 - Financeiro - Faturamento

- Menu: `Financeiro > Faturamento`.
- Tela lista os 12 meses do ano selecionado.
- Filtro:
  - `Ano`;
  - botao `Atualizar`.
- Colunas:
  - acao de alternar aberto;
  - `Aberto`;
  - `Mes`;
  - `Total Lancado`;
  - `Total Recebido`;
  - `Acoes`.
- `Total Lancado`:
  - soma `Lancamentos.ValorEfetivado`;
  - filtra `AnoReferencia = Ano`;
  - filtra `MesReferencia = Mes`;
  - exige `ClienteId` preenchido.
- `Total Recebido`:
  - soma `Lancamentos.ValorEfetivado`;
  - filtra pelo mes de `Lancamentos.DataLancamento`;
  - filtra pelo ano de `Lancamentos.DataLancamento`.
- Rodape:
  - soma anual de `Total Lancado`;
  - soma anual de `Total Recebido`.
- A coluna inicial com setas pergunta e alterna aquele mes como aberto.
- Apenas um mes fica aberto por ano.
- Mes aberto fica destacado na tabela e exibe check na coluna `Aberto`.
- Link `Ajuste` ainda nao executa acao.


## 10 - Administrativo - Usuarios

- Menu: `Administrativo > Usuarios`.
- Tela restrita a usuarios administradores.
- Campos do usuario:
  - `Nome`;
  - `Email`;
  - `Senha`;
  - `Administrador`;
  - `Permissoes`.
- Usuario administrador acessa todas as telas e acoes.
- Usuario comum acessa conforme permissoes marcadas.
- Permissoes mantem os nomes tecnicos do OutSystems:
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
- Menus sem permissao de leitura devem ficar ocultos.
- Acoes de escrita/exclusao/baixa tambem devem validar permissao no servidor.