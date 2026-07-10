# Regras de negocio atuais

Data da varredura: 2026-07-10.

Este documento consolida as regras de negocio observadas no codigo Laravel atual. Ele nao substitui os arquivos historicos de reconstrucao; a finalidade aqui e manter um inventario operacional para manutencao e validacao.

## Achados da varredura de lixo e uso

- Nao foram encontrados arquivos `.bak`, `.old`, `.tmp`, `.orig`, `.rej`, dumps `.sql`, `.sql.gz`, `.zip`, `.DS_Store` ou arquivos grandes suspeitos dentro de `app/`, excluindo `vendor`, `node_modules`, `storage`, cache e build.
- `tmp/laravel-8000.pid` existe localmente e nao esta versionado. E residuo operacional do servidor local, nao lixo de Git.
- Views Blade encontradas possuem pelo menos uma referencia direta por `protected string $view`, `view()` ou configuracao de recurso/widget.
- Classes com baixa contagem de referencia aparente foram conferidas e sao usadas por DI, rotas, webhooks, services ou views.
- `docs/implementacao.md` contem trecho historico com texto corrompido/encoding quebrado. Nao foi alterado porque pode ser registro de historico, mas e candidato a limpeza editorial.
- Os assets publicados do Filament em `public/js/filament`, `public/css/filament` e `public/fonts/filament` parecem ser assets de runtime publicados e versionados, nao lixo.

## Acesso, menus e permissoes

- `User::hasPermission()` libera tudo para `is_admin`.
- `canAccessPanel()` permite acesso ao painel para usuarios autenticados; o controle real fica em `canAccess`, actions e visibilidade.
- Catalogo central de permissoes: `App\Models\Permission::catalogo()`.
- Grupos de navegacao do painel: `Cadastro`, `Financeiro`, `Estoque`, `Rotinas`, `Administrativo`.
- `Financeiro` exige `Financeiro_Leitura`; alteracoes da tela financeira exigem `Financeiro_Escrita`.
- `Boletos` usa `Boletos_Leitura`, `Boletos_Escrita` e `Boletos_Baixar`.
- `Estoque` usa `Estoque_Leitura` e `Estoque_Escrita`.
- `Rotinas`, `Integracoes` e `Restore Backup` exigem `Tecnico`.
- `Usuarios` e `Auditoria` exigem `Coordenador`.
- Coordenador pode manter usuarios nao administradores, vendedores e tecnicos; nao pode criar/promover admin nem editar/excluir admin.

## Clientes

- `cpf_cnpj`, `cep`, `telefone1` e `telefone2` sao normalizados para digitos ao salvar.
- `telefone1_pais` padrao e `BR`; `telefone2_pais` fica nulo quando nao ha telefone secundario.
- Cliente ativo/inativo e calculado por veiculos ativos: se existir ao menos um veiculo com status `Ativo`, cliente fica `Ativo`; caso contrario, `Inativo`.
- A busca compartilhada `conectta.busca_cadastros` sincroniza `Financeiro`, `Clientes` e `Cadastro > Rastreadores`.
- O filtro compartilhado `conectta.status_cliente` sincroniza apenas `Financeiro` e `Clientes`.
- Export de clientes usa filtros atuais e agora obedece a ordenacao atual da tabela Filament.

## Cadastro > Rastreadores

- O recurso visual `Cadastro > Rastreadores` usa `Veiculo` como model principal.
- O campo `IMEI` seleciona `Rastreador`; o campo `Numero Chip` reflete/vincula `Chip` ao `Rastreador`, nao ao `Veiculo`.
- A lista de IMEIs no formulario mostra rastreadores `Disponivel` e tambem o rastreador ja vinculado ao registro em edicao.
- Ao selecionar IMEI, o instalador e preenchido pelo tecnico do rastreador.
- Se o rastreador selecionado nao tiver chip, o formulario mostra aviso.
- Se for escolhido um chip ja vinculado a outro IMEI, a tela pede confirmacao para transferir o chip.
- Ao vincular chip no cadastro de rastreador/veiculo, qualquer outro rastreador com o mesmo chip perde o vinculo.
- Ao vincular chip, o chip passa para status `Ativo`.
- Veiculo com status `Cancelado` exige `Data Retirada` e `Tecnico Remocao`.
- Veiculo ativo nao pode usar um `rastreador_id` ja vinculado a outro veiculo ativo.
- Ao salvar veiculo ativo, o rastreador vinculado passa para status `Ativo`.
- Ao cancelar ou excluir veiculo, o rastreador vinculado volta para `Disponivel`; quando houver tecnico de remocao, esse tecnico e gravado no rastreador.
- Apos salvar/excluir veiculo, o status do cliente atual e do cliente anterior, se mudou, e recalculado.
- Export de `Cadastro > Rastreadores` usa filtros atuais e agora obedece a ordenacao atual da tabela Filament.

## Estoque

- `Estoque > Rastreadores` lista a tabela real `rastreadores`; `Cadastro > Rastreadores` lista `veiculos`.
- `Estoque > Rastreadores` permite criar/editar/excluir rastreadores com permissao `Estoque_Escrita`.
- `Estoque > Chips` permite criar/editar/excluir chips com permissao `Estoque_Escrita`.
- Em chips, `numero_chip` e `iccid` sao campos separados.
- ICCID deve ter exatamente 20 digitos no formulario e ser unico quando preenchido.
- Ao vincular um chip a um IMEI em `Estoque > Chips`, o sistema remove vinculos antigos desse chip em outros rastreadores.
- Um rastreador nao pode receber chip se ja houver outro chip vinculado nele, exceto quando e o mesmo registro em edicao.
- Exports de `Estoque > Rastreadores` e `Estoque > Chips` usam a mesma query da listagem; como essas telas nao possuem ordenacao clicavel, tela e CSV saem na mesma ordem fixa.
- `Estoque > Tecnicos` e tabela Filament; export agora obedece a ordenacao atual.

## Financeiro

- Tela principal exibe dois meses: mes base e mes seguinte.
- Ordenacao permitida dos clientes: `qtd`, `vendedor`, `cliente` e `vencimento`.
- Export financeiro usa `linhasFinanceiro()` e obedece a ordenacao atual.
- `dia_pagamento` editado no financeiro deve estar entre 1 e 31.
- Anotacoes, vencimento e replicacao de pagamento gravam logs de auditoria.
- Somente um grupo de filtros mensais fica ativo por vez: ao mexer nos filtros do mes 1, limpa mes 2; ao mexer no mes 2, limpa mes 1.
- Replicacao de planejado copia do mes anterior apenas para clientes com `replicar_pagamento = true`.
- Replicacao ignora cliente sem valor planejado anterior ou que ja possui valor planejado no mes destino.
- Lancamento financeiro salva `data_lancamento`, `numero_boleto`, `valor_planejado`, `valor_efetivado`, `observacao`, mes/ano de referencia e `time_stamp`.
- Logs novos de lancamento/parcelamento guardam `total_antes` e `total_depois` no contexto.
- Parcelamento so pode ser lancado quando o lancamento principal existe e tem `valor_efetivado` diferente de zero.
- Parcelamento cria novo registro em `lancamentos` na mesma referencia do cliente.
- Nao e permitido excluir o lancamento principal pelo fluxo de excluir parcelamento.
- Historico financeiro deve exibir logs financeiros relevantes de `valor_efetivado` ou `data_lancamento`, incluindo criacao/exclusao de parcelamento.

## Boletos e Lytex

- Geracao de boleto exige permissao `Boletos_Escrita`.
- Baixa de boleto exige `Boletos_Baixar`, mas a acao ainda esta marcada como etapa futura.
- Para gerar boleto, precisa existir cliente, lancamento, mes e ano.
- Nao gera boleto se ja existir boleto pago ou boleto em aberto na mesma referencia.
- Nao gera boleto se a referencia ja tiver qualquer `valor_efetivado` maior que zero.
- `valor_planejado` precisa ser maior que zero.
- A referencia disponivel para boleto fica entre 6 meses antes e 2 meses depois do mes atual.
- Cliente precisa ter email valido, telefone celular com 11 digitos, CPF/CNPJ valido e nascimento valido quando preenchido.
- Payload Lytex usa PIX e boleto habilitados, cartao desabilitado, multa de 5% e juros de 0,1%.
- Ao salvar resposta da Lytex, `totalValue` e convertido de centavos para decimal.
- Ao gerar boleto pela Lytex, o lancamento recebe `numero_boleto = Lytex`.
- Status de boleto sao normalizados para exibicao: Pago, Cancelado, Atrasado, Processando, Aguardando Pagamento e Nao gerado.
- URLs de invoice/boleto usam links persistidos quando existem; caso contrario, usam `hash_id` com base Lytex padrao.
- `Boletos` e `Relatorio geral` nao possuem ordenacao clicavel; seus exports usam a mesma query e ordem da tela.

## Rotinas de cobranca

- Tipos de cobranca: `boleto_7_dias`, `lembrete_vencimento`, `atraso_2`, `atraso_5`, `atraso_7`, `atraso_10`, `atraso_12`, `atraso_15`.
- Sem `--executar`, comandos de cobranca rodam em simulacao.
- Cada tipo processado cria uma execucao propria.
- `boleto_7_dias` mira vencimento 7 dias depois da data de processamento.
- `lembrete_vencimento` mira vencimento na data de processamento.
- Atrasos miram vencimentos de 2, 5, 7, 10, 12 ou 15 dias antes da data de processamento.
- Lancamento candidato precisa ter `valor_planejado > 0` e `valor_efetivado` nulo ou menor/igual a zero.
- Depois da selecao inicial, qualquer soma de `valor_efetivado > 0` na mesma referencia do cliente impede cobranca.
- Vencimento e calculado pelo `dia_pagamento` do cliente; dia maior que o ultimo dia do mes cai no ultimo dia.
- O mesmo lancamento/tipo/data nao e reenviado se ja houver envio `enviado` ou `pendente_whatsapp`.
- Cliente sem telefone WhatsApp valido vira erro.
- `boleto_7_dias` pode gerar boleto na Lytex quando nao ha invoice e `numero_boleto` esta vazio.
- Em dry-run, `boleto_7_dias` nao gera boleto de verdade.
- Atraso exige `numero_boleto = Lytex` e invoice ativa com `hash_id`.
- Atraso inclui no payload boletos vencidos de meses anteriores do mesmo cliente, desde que tenham `numero_boleto = Lytex`, sem pagamento, invoice ativa, `hash_id` e vencimento passado.
- Agendamentos ativos calculam proxima execucao por horario e dias da semana; por padrao, usam todos os dias da semana.
- Agendamento pode processar cobranca e, se configurado, enviar pendentes de WhatsApp da execucao.

## WhatsApp de cobranca

- Somente envios com status `pendente_whatsapp` sao processados.
- Dry-run de WhatsApp grava payload e response simulada, sem marcar como enviado.
- Fluxo de lembrete de vencimento: mensagem principal apenas.
- Fluxo de boleto 7 dias: mensagem principal, PDF do boleto, linha digitavel, instrucao PIX, botao/codigo PIX e finalizacao.
- Fluxo de atraso: mensagem principal e PDFs dos boletos; nao envia linha digitavel, PIX ou finalizacao.
- Para boleto 7 dias, se invoice nao tiver linha digitavel ou PIX, tenta buscar detalhes na Lytex.
- PDF do boleto usa link do boleto acrescido de `.PDF` quando o link nao termina com `.pdf`.
- Nome de PDF segue `BoletoConectta_Mes_Ano.pdf`.
- Textos auxiliares (`pix_instrucao`, `finalizacao`) vem de `cobranca_mensagem_modelos` ativos por canal WhatsApp, com fallback fixo.

## Contratos e ZapSign

- Contratos pertencem a `Cadastro`.
- Criar/editar contrato exige `Cadastro_Escrita`; excluir exige `Cadastro_Exclusao`.
- Botao de envio para ZapSign aparece apenas para contratos com status `Nao Enviado` e permissao de escrita.
- Documento assinado ou enviado com token pode ser aberto pela rota autenticada `/admin/contratos/{contrato}/documento`.
- Webhook ZapSign registra payload em `zapsign_webhook_logs`.
- Tokens e secrets sao redigidos nos logs de auditoria.
- Export de contratos usa filtros atuais e agora obedece a ordenacao atual da tabela Filament.

## Integracoes

- Tela `Integracoes` exige permissao `Tecnico`, mas salvar configuracoes exige usuario admin.
- Lytex, ZapSign e Z-API possuem ambientes `producao` e `homologacao`, com um ambiente ativo por integracao.
- Ao trocar ambiente ativo Lytex ou alterar a configuracao do ambiente ativo, os tokens Lytex cacheados sao apagados.
- Secrets/tokens cadastrados nao sao recarregados em texto no formulario; a tela mostra apenas flags de cadastrado.
- Timeouts validados ficam entre 5 e 120 segundos nas integracoes que usam essa regra.
- Webhook Lytex registra payload em `lytex_webhook_logs`.

## Restore e importacao legada

- Restore exige arquivos de clientes, veiculos, rastreadores, tecnicos, vendedores, lancamentos, invoices e faturamentos.
- Quando `limparBase` esta ativo, trunca tabelas controladas antes de importar.
- Seeders garantem paises e modelos de mensagem de cobranca ao final do restore.
- Restore ajusta `AUTO_INCREMENT` apos importacao.
- Cliente importado sem CPF/CNPJ recebe valor tecnico `IMPORTADO-{id}`.
- CPF/CNPJ duplicado no backup recebe sufixo tecnico `-DUP-{id}`.
- `dia_pagamento` importado e limitado entre 1 e 31.
- Marcadores vazios de importacao: vazio, `-` e `'-`.
- Excecao: em `lancamentos.numero_boleto` e `lancamentos.observacao`, `-` e `'-` sao preservados como `-`.

## Auditoria

- `AuditLogger` grava usuario, acao, entidade, descricao, antes, depois, IP, user agent e contexto.
- Campos sensiveis sao redigidos quando a chave e ou contem `token` ou `secret`, alem de `password`, `remember_token`, `doc_token`, `client_secret`, `callback_secret` e `signature`.
- Exclusoes e alteracoes criticas de financeiro, rastreadores, boletos, clientes, replicacao e contratos devem passar por auditoria quando implementadas no fluxo.

