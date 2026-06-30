# Modelo atual do sistema

Fonte inicial: diagrama do OutSystems enviado em `All.png`.

Fonte principal adicionada: `OutDoc - eSpace Conectta_CS.pdf`, gerado em 22/06/2026.

Objetivo desta documentacao: registrar o modelo existente antes de qualquer reimplementacao em PHP. Nesta fase, a prioridade e reproduzir o comportamento atual, sem evoluir regras, nomes ou estrutura.

## Visao geral do OutDoc

- eSpace: `Conectta_CS`
- Multitenant: `No`
- User Provider: `Users`
- UI Flows: nenhum no OutDoc
- Processes: nenhum no OutDoc
- SOAP/REST/SAP integrations no OutDoc: nenhuma integrada diretamente neste eSpace
- Session Variables: nenhuma
- Site Properties: apenas `TenantId` e `TenantName` do sistema
- Timer: `CorrigeRepetidos`

Observacao: as integracoes com Z-Api, Lytex e ZapSign podem estar implementadas em outro eSpace/modulo, via actions nao documentadas neste PDF, ou manualmente fora da secao de integrations do OutDoc.

## Entidades persistentes

### Cliente

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| NUMR | Long Integer |  |
| VendedorId | Vendedor Identifier | FK |
| StatusContratoId | StatusContrato Identifier | FK |
| Nome | Text 100 |  |
| CPF_CNPJ | Text 50 |  |
| RG | Text 50 |  |
| Nascimento | Date |  |
| Email | Email 250 |  |
| CEP | Text 50 |  |
| RUA | Text 150 |  |
| Numero | Text 50 |  |
| Complemento | Text 50 |  |
| Setor | Text 50 |  |
| Cidade | Text 50 |  |
| EstadoId | Estado Identifier | FK |
| Telefone1 | Text 50 |  |
| Telefone2 | Text 50 |  |
| Empresa | Text 50 |  |
| Indicacao | Text 50 |  |
| DataAdesao | Date |  |
| Dataexclusao | Date Time |  |
| DiaPagamento | Integer |  |
| StatusClienteId | StatusCliente Identifier | FK |
| isSPC | Boolean |  |
| isGrupoZelo | Boolean | Legado; nao existe mais no uso atual e nao entra na reconstrucao inicial |
| CreatedAt | Date Time |  |
| UpdatedAt | Date Time |  |
| CreatedBy | User Identifier | FK externa para Users |
| UpdatedBy | User Identifier | FK externa para Users |
| ClienteOrigemId | ClienteOrigem Identifier | FK |
| Anotacoes | Text 500 |  |
| ReplicarPagamento | Boolean |  |

Indices informados pelo OutDoc:

- AutoIndex_CreatedBy
- AutoIndex_StatusClienteId
- AutoIndex_VendedorId
- AutoIndex_ClienteOrigemId
- AutoIndex_EstadoId
- AutoIndex_UpdatedBy
- AutoIndex_StatusContratoId

Actions relevantes:

- `SalvarAtualizarCliente(Source: Cliente) -> Id`
- `Delete(ClienteId)`
- `AlterarAnotacoes(ClienteId, Anotacao)`
- `AlterarDiaPagamento(ClienteId, DiaPagamento)`
- `AlterarReplicarPagamento(ClienteId, IsReplicarPagamento)`
- `UpdateStatusCliente(ClienteId)`

Regras confirmadas:

- Cliente fica automaticamente inativo quando nao possui nenhum veiculo com `StatusRastreador` ativo.
- Cliente nasce como inativo no cadastro inicial.
- No sistema atual, essa validacao ocorre ao salvar a edicao do veiculo.
- `StatusClienteId` e automatico e segue a regra dos veiculos/rastreadores ativos.
- Na lista de clientes, a lixeira exclui o cliente de verdade.
- O botao `Add` usa formulario igual ao de editar cliente.
- Campos obrigatorios no formulario de cliente: `Nome`, `DataAdesao`, `CPF_CNPJ`, `Telefone1` e `DiaPagamento`.
- `DiaPagamento` aceita valores de 1 a 31.
- `CPF_CNPJ` e unico e nao pode repetir.
- `CPF_CNPJ` deve ser salvo sem mascara.
- `CPF_CNPJ` deve ser validado como CPF ou CNPJ real antes de salvar.
- No formulario de cliente, `Nascimento` deve ser campo com mascara de data, sem calendario/date picker.
- `StatusClienteId` e automatico e nao deve aparecer no formulario de inclusao/edicao de cliente.
- A quantidade de rastreadores na lista conta apenas rastreadores/veiculos ativos.
- O icone `+` na lista de clientes adiciona um veiculo para aquele cliente, abrindo a mesma tela de edicao/cadastro de rastreadores.
- `isGrupoZelo` nao existe mais no uso atual e deve ficar fora das telas/reconstrucao inicial.
- `Anotacoes` e `ReplicarPagamento` aparecem depois no fluxo/tela de financeiro, nao no cadastro principal de cliente.
- Filtro da lista de clientes:
  - filtra por `StatusClienteId`, exceto quando status selecionado e todos/nulo;
  - considera apenas clientes com `Dataexclusao = NullDate()`;
  - busca por `Nome` ou `CPF_CNPJ`;
  - filtra periodo por `DataAdesao`.

### Veiculo

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| ClienteId | Cliente Identifier | FK |
| Veiculo | Text 50 |  |
| Placa | Text 50 |  |
| Cor | Text 50 |  |
| Ano | Text 50 |  |
| Observacao | Text 500 |  |
| TipoVeiculoId | TipoVeiculo Identifier | FK |
| DataExclusao | Date Time |  |
| CreatedAt | Date Time |  |
| UpdatedAt | Date Time |  |
| CreatedBy | User Identifier | FK externa para Users |
| UpdatedBy | User Identifier | FK externa para Users |
| IMEI | Text 150 |  |
| NumeroChip | Text 150 |  |
| DataRetirada | Date |  |
| DataInstalacao | Date |  |
| Login | Text 50 |  |
| Senha | Text 50 |  |
| TecnicoRemocao | Text 50 |  |
| Instalador | Text 50 |  |
| ValorInstalacao | Decimal 37,8 |  |
| StatusRastreadorId | StatusRastreador Identifier | FK |
| TextoImportacao | Text 50 |  |
| Associado | Text 500 |  |
| Contato | Text 50 |  |
| StatusContratoId | StatusContrato Identifier | FK |
| doc_token | Text 200 | Provavel ZapSign |
| ContratoTipo | Text 50 |  |
| ChipId | Chip Identifier | FK |
| RastreadorId | Rastreador Identifier | FK |
| TecnicoRemocaoId | Tecnico Identifier | FK |
| TipoContratoId | TipoContrato Identifier | FK |
| TecnicoInstalaId | Tecnico Identifier | FK |

Indices informados pelo OutDoc:

- AutoIndex_StatusRastreadorId
- AutoIndex_CreatedBy
- AutoIndex_ClienteId
- AutoIndex_TecnicoInstalaId
- AutoIndex_TipoVeiculoId
- AutoIndex_StatusContratoId
- AutoIndex_TecnicoRemocaoId
- AutoIndex_RastreadorId
- AutoIndex_ChipId
- AutoIndex_UpdatedBy
- AutoIndex_TipoContratoId

Actions relevantes:

- `CreateOrUpdate_Veiculo(Source: Veiculo) -> Id`
- `Delete_Veiculo(VeiculoId)`
- `GetTotalVeiculosByClientes(ClienteId) -> Total`
- `SalvarAtualizarVeiculo(IN_Veiculo, RastreadorId_Removido)`

Regras confirmadas:

- A tela funcional se chama `Rastreadores`, mas o registro principal salvo/editado e `Veiculo`.
- `Veiculo.Veiculo` e texto livre, por exemplo `Toyota / Yaris`.
- `IMEI` vem do cadastro/estoque de `Rastreador`.
- `NumeroChip` vem do cadastro/estoque de `Chip`.
- Na reconstrucao inicial, nao usar os campos legados `Veiculo.IMEI` e `Veiculo.NumeroChip`; a fonte correta sera o vinculo por `Veiculo.RastreadorId` e `Veiculo.ChipId`.
- `Login` e `Senha` sao digitados manualmente.
- `Associado` e `Contato` sao textos livres.
- Status operacionais na tela de `Rastreadores`: `Ativo`, `Cancelado` e `Disponivel`.
- `Lixo` existe no dominio/static entity de `StatusRastreador`, mas nao e exibido nem tratado na tela de `Rastreadores`; sera tratado no fluxo de estoque.
- Apenas `Veiculo` com `StatusRastreador = Ativo` conta como veiculo ativo do cliente.
- Ao salvar, excluir ou cancelar um veiculo, recalcular `Cliente.StatusClienteId`.
- Se o cliente tiver pelo menos um veiculo ativo, status do cliente deve ser `Ativo`.
- Se o cliente nao tiver nenhum veiculo ativo, status do cliente deve ser `Inativo`.
- Se `Veiculo.StatusRastreadorId = Cancelado`, a tela deve exigir `DataRetirada` e `TecnicoRemocaoId`.
- Ao cancelar um veiculo, o `Veiculo` fica com status `Cancelado` e o `Rastreador` vinculado volta para status `Disponivel`.
- Ao cancelar, atualizar tambem `Rastreador.TecnicoId` para o tecnico selecionado na remocao.
- O mesmo `Rastreador/IMEI` pode aparecer em varios veiculos historicos cancelados, mas nao pode estar em mais de um veiculo ativo.
- O mesmo `Chip/ICCID` pode aparecer em varios veiculos historicos cancelados, mas nao pode estar em mais de um veiculo ativo.
- Para vincular um `Rastreador/IMEI` a outro veiculo, o rastreador precisa estar com status `Disponivel`.
- Ao selecionar um `Rastreador/IMEI`, o sistema usa `Rastreador.TecnicoId` para preencher o tecnico instalador e deixar esse campo bloqueado.
- Botao `Contratos` fica para etapa posterior.

### Lancamentos

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| NUMR | Text 50 |  |
| ClienteId | Cliente Identifier | FK |
| DataLancamento | Date |  |
| ValorPlanejado | Decimal 37,8 |  |
| ValorEfetivado | Decimal 37,8 |  |
| NumeroBoleto | Text 500 |  |
| Observacao | Text 500 |  |
| isBaixado | Boolean |  |
| MesReferencia | Integer |  |
| AnoReferencia | Integer |  |
| TimeStamp | Date Time |  |
| Log | Text 2000 |  |

Indice informado pelo OutDoc:

- AutoIndex_ClienteId

Actions relevantes:

- `CreateLancamento_Parcelamento(ClienteId, Mes, Ano, GerarLog, DataLancamento, ValorEfetivado)`
- `CreateLancamento_Principal(Source: Lancamentos, GerarLog)`
- `LancamentoDelete(Id)`
- `ReplicarValores(MesAtual)`
- Actions padrao: create, update, delete, createOrUpdate

Regras confirmadas para a tela financeira:

- A grade financeira mostra clientes mesmo quando nao existe lancamento para o mes.
- A tela trabalha sempre com dois meses visiveis: mes atual e proximo mes ao abrir.
- A navegacao anda um mes por clique, mantendo dois meses lado a lado.
- Para exibir valores mensais, agregar `Lancamentos` por `ClienteId`, `MesReferencia` e `AnoReferencia`.
- Quando houver mais de um lancamento para o mesmo cliente/mes:
  - `NumeroBoleto` usa `MAX`;
  - `ValorPlanejado` usa `MAX`;
  - `ValorEfetivado` usa `SUM`;
  - `Observacao` usa `MAX`.
- O filtro de clientes busca por `Nome`, `CPF_CNPJ` e, conforme SQL atual, tambem `Anotacoes`.
- O filtro `Vencimento` compara exatamente com `Cliente.DiaPagamento`.
- `Cliente.DiaPagamento` e `Cliente.Anotacoes` podem ser editados direto na grade e salvos automaticamente apos 2 segundos sem digitacao.
- `Cliente.ReplicarPagamento` e alternado por botao na grade.
- O popup de lancamento sera implementado em etapa propria; a primeira fase cria apenas o ponto de abertura/navegacao.

### Lancamentos_Log

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| NomeCliente | Text 100 |  |
| MesReferencia | Integer |  |
| AnoReferencia | Integer |  |
| ValorEfetivadoAnterior | Decimal 37,8 |  |
| ValorEfetivadoPosterior | Decimal 37,8 |  |
| DataLancamentoAnterior | Date |  |
| DataLancamentoPosterior | Date |  |
| TimeStamp | Date Time |  |
| UserId | User Identifier | FK externa para Users |
| TotalAnterior | Decimal 37,8 |  |
| TotalPosterior | Decimal 37,8 |  |

Indice informado pelo OutDoc:

- AutoIndex_UserId

### Invoice

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| ClientId | Text 50 | Atencao: nao e FK no OutDoc |
| CPFCNPJ | Text 50 |  |
| Fatura_Id | Text 100 | Provavel Lytex |
| totalValue | Decimal 37,8 |  |
| createdAt | Text 50 |  |
| updatedAt | Text 50 |  |
| hashId | Text 500 |  |
| LancamentosId | Lancamentos Identifier | FK |
| Status | Text 50 |  |
| Vencimento | Date Time |  |
| UserId | User Identifier | FK externa para Users |

Indices informados pelo OutDoc:

- AutoIndex_UserId
- AutoIndex_LancamentosId

Uso confirmado:

- A tela `Financeiro > Relatorio geral` usa `Invoice` via left join com `Lancamentos`.
- Join: `Lancamentos.Id = Invoice.LancamentosId`.
- `Invoice.Status` alimenta o filtro `Status Boleto` e a coluna/badge de status.

### Faturamento

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| Ano | Integer |  |
| Mes | Integer |  |
| isAberto | Boolean |  |

Actions relevantes:

- `ChangeMes(Mes, Ano)`
- Actions padrao: create, update, delete, createOrUpdate

### EnviosRotinas

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| RotinasId | Rotinas Identifier | FK |
| ClienteId | Cliente Identifier | FK |
| AgendadoPara | Date Time |  |
| Status | Text 50 |  |
| MensagemCompleta | Text 5000 |  |
| ZAP_Id | Text 50 | Provavel Z-Api |
| LinkBoleto | Text 500 |  |
| LinhaDigitavel | Text 500 |  |
| PixCopiaCola | Text 500 |  |
| IsEnviado | Boolean |  |
| DataCobranca | Date Time |  |

Indices informados pelo OutDoc:

- AutoIndex_ClienteId
- AutoIndex_RotinasId

### Rotinas

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| NomeRotina | Text 50 |  |
| Descricao | Text 500 |  |
| Mensagem | Text 500 |  |
| EnviadoEm | Date Time |  |

### Vendedor

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| NUMR | Long Integer |  |
| Nome | Text 50 |  |

### Autenticadores

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| Usuario | Text 50 |  |
| Senha | Text 50 |  |
| isActive | Boolean |  |

### TokenLytex

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| accessToken | Text 2000 |  |
| refreshToken | Text 2000 |  |
| expireAt | Date Time |  |
| refreshExpireAt | Date Time |  |

### Chip

No diagrama inicial apareceu como `EstoqueChip`; no OutDoc o nome real da entidade e `Chip`.

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| Fornecedor | Text 50 |  |
| Operadora | Text 50 |  |
| ICCID | Text 50 |  |
| TecnicoId | Tecnico Identifier | FK |

Indice informado pelo OutDoc:

- AutoIndex_TecnicoId

### Rastreador

No diagrama inicial apareceu como `EstoqueRastreador`; no OutDoc o nome real da entidade e `Rastreador`.

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| Modelo | Text 50 |  |
| Ativacao | Integer |  |
| IMEI | Text 50 |  |
| TecnicoId | Tecnico Identifier | FK |
| IsEstoque | Boolean |  |
| UserId | User Identifier | FK externa para Users |
| CriadoEm | Date Time |  |
| StatusRastreadorId | StatusRastreador Identifier | FK |

Indices informados pelo OutDoc:

- AutoIndex_StatusRastreadorId
- AutoIndex_UserId
- AutoIndex_TecnicoId

Actions relevantes:

- `RastreadorCreateOrUpdate(Source: Rastreador) -> Id, IsSuccess, Mensagem`
- `RastreadorDelete(Id) -> isSuccess, Mensagem`
- Actions padrao: create, update

### Tecnico

No diagrama inicial apareceu como `EstoqueTecnico`; no OutDoc o nome real da entidade e `Tecnico`.

Campos:

| Campo | Tipo OutSystems | Observacao |
| --- | --- | --- |
| Id | Long Integer | PK |
| Nome | Text 50 |  |
| CPF | Text 50 |  |
| Telefone | Text 50 |  |
| IsAtivo | Boolean |  |



## Estruturas nao persistentes

### Excel_Importacao

Usada provavelmente em importacao de veiculos/rastreadores.

Campos:

- Data: Text 50
- CLIENTE: Text 50
- VEICULO: Text 50
- PLACA: Text 50
- Cor: Text 50
- Ano: Text 50
- Tipo: Text 50
- IMEI: Text 50
- CHIP: Text 50
- Login: Text 50
- Senha: Text 50
- Observacao: Text 50
- Instalador: Text 50
- Valordainstalacao: Text 50

## Static entities

### ClienteOrigem

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean

Registros:

| Label | Order | Is_Active |
| --- | --- | --- |
| Meta | 1 | True |
| Indicacao | 2 | True |
| ADS | 3 | True |
| Retencao | 4 | True |
| Conectta | 5 | True |
| Venda Direta | 6 | True |
| Outros | 7 | True |

### Estado

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean

Registros conforme OutDoc:

- Goias, order 1
- Distrito Federal, order 2
- Mato Grosso, order 3
- Mato Grosso do Sul, order 4
- Alagoas, order 5
- Bahia, order 6
- Ceara, order 7
- Maranhao, order 8
- Paraiba, order 9
- Pernambuco, order 10
- Piaui, order 11
- Rio Grande do Norte, order 12
- Sergipe, order 13
- Acre, order 14
- Amapa, order 15
- Amazonas, order 16
- Para, order 17
- Rondonia, order 18
- Roraima, order 19
- Tocantis, order 20
- Espirito Santo, order 21
- Minas Gerais, order 22
- RiodeJaneiro, order 23
- SaoPaulo, order 24
- Parana, order 25
- Rio Grande do Sul, order 26
- Santa Catarina, order 27

Observacao: manter grafia original do OutDoc na migracao inicial, mesmo quando houver aparente erro ou ausencia de espaco/acentos.


### Mes

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean
- Numero: Integer

Registros:

| Label | Order | Is_Active | Numero |
| --- | --- | --- | --- |
| Janeiro | 1 | True | 1 |
| Fevereiro | 2 | True | 2 |
| Marco | 3 | True | 3 |
| Abril | 4 | True | 4 |
| Maio | 5 | True | 5 |
| Junho | 6 | True | 6 |
| Julho | 7 | True | 7 |
| Agosto | 8 | True | 8 |
| Setembro | 9 | True | 9 |
| Outubro | 10 | True | 10 |
| Novembro | 11 | True | 11 |
| Dezembro | 12 | True | 12 |

### StatusCliente

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean

Registros:

| Label | Order | Is_Active |
| --- | --- | --- |
| Ativo | 1 | True |
| Inativo | 2 | True |

### StatusContrato

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean

Registros:

| Label | Order | Is_Active |
| --- | --- | --- |
| Assinado | 1 | True |
| Enviado | 3 | True |
| Nao Enviado | 4 | True |
| Rejeitado | 5 | True |

Observacao: nao apareceu registro com order 2 no OutDoc.

### StatusRastreador

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean

Registros:

| Label | Order | Is_Active |
| --- | --- | --- |
| Ativo | 1 | True |
| Cancelado | 2 | True |
| Lixo | 3 | True |
| Disponivel | 4 | True |

### TipoContrato

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean
- Codigo: Text 50

Registros:

| Label | Order | Is_Active | Codigo |
| --- | --- | --- | --- |
| Principal | 1 | True | e8db8e22-f163-419e-898f-d7709fea2296 |
| Aditivo | 2 | True | 029c6d29-7c8d-4e9c-8f8e-9bdc16b7add8 |
| Comodato | 3 | True | fe0d3df0-b615-4296-81d7-0dd274036892 |

### TipoVeiculo

Campos:

- Id: Integer PK
- Label: Text 50
- Order: Integer
- Is_Active: Boolean

Registros:

| Label | Order | Is_Active |
| --- | --- | --- |
| Carro | 1 | True |
| Moto | 2 | True |
| Caminhonete | 3 | True |
| Camioneta | 4 | True |
| Caminhao | 5 | True |
| Onibus | 6 | True |
| Maquinas Agricolas | 7 | True |
| Nauticos | 8 | True |
| Maquina Industrial | 9 | True |
| Computador | 10 | True |
| Nao definido | 11 | True |

## Papeis/permissoes

Roles persistentes encontrados no OutDoc:

- Boletos_Baixar
- Boletos_Escrita
- Boletos_Leitura
- Cadastro_Escrita
- Cadastro_Exclusao
- Cadastro_Leitura
- Estoque_Escrita
- Estoque_Leitura
- Faturamento_Escrita
- Faturamento_Leitura
- Financeiro_Escrita
- Financeiro_Leitura

## Integracoes externas conhecidas

Informadas pelo dono do sistema:

- Z-Api: envio de mensagens/rotinas, provavelmente conectado a `EnviosRotinas`.
- Lytex: boletos/faturas, provavelmente conectado a `Lancamentos`, `Invoice` e `TokenLytex`.
- ZapSign: contratos/assinaturas, provavelmente conectado a `Veiculo.doc_token`, `Veiculo.ContratoTipo` e `TipoContrato.Codigo`.

Observacao do OutDoc: a secao `Integrations` nao lista REST/SOAP consumidos ou expostos neste eSpace. Precisamos confirmar onde essas chamadas estao implementadas.

## Pontos a confirmar

- Campos obrigatorios e valores padrao, pois o texto extraido do OutDoc nao deixou a coluna `Mandatory` legivel.
- Regras de unicidade. O OutDoc listou apenas auto indices de FK, sem indices unicos aparentes.
- Se `Veiculo.Placa`, `Veiculo.IMEI`, `Veiculo.NumeroChip`, `Chip.ICCID` e `Rastreador.IMEI` devem ser unicos.
- O papel exato de `Veiculo.TecnicoRemocao` e `Veiculo.Instalador`, ja que tambem existem `TecnicoRemocaoId` e `TecnicoInstalaId`.
- O papel exato de `Veiculo.ChipId` versus `Veiculo.NumeroChip`.
- O papel exato de `Veiculo.RastreadorId` versus `Veiculo.IMEI`.
- Se `DataExclusao` indica exclusao logica para Cliente e Veiculo.
- Se `Invoice.ClientId` guarda identificador externo da Lytex ou cliente interno em texto.
- Como `NUMR` e gerado em `Cliente`, `Vendedor` e `Lancamentos`.
- Como funciona `ReplicarPagamento` e a action `ReplicarValores`.
- O que o timer `CorrigeRepetidos` faz.
- Se `Autenticadores` ainda e usado ou se foi substituido por usuarios/roles nativos do OutSystems.
