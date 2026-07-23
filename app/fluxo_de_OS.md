# Fluxo de Ordem de Servico

## Estado do documento

- Levantamento funcional concluido em 22/07/2026.
- Este documento consolida os requisitos e as decisoes para orientar a implementacao.

## Objetivo

Criar no sistema um fluxo para controlar Ordens de Servico (OS), desde a abertura e o agendamento com um tecnico ate a conferencia e a finalizacao do atendimento.

## Tipos de OS

- Instalacao
- Retirada
- Manutencao

## Participantes

- **Operador:** abre, agenda, acompanha e confere a OS na central.
- **Tecnico:** aceita ou rejeita a OS e executa o atendimento por uma tela publica protegida por hash.

## Fluxo geral confirmado

1. A OS e criada com status `Aberta`.
2. A OS pode ser atribuida a um tecnico.
3. O tecnico cadastra sua agenda, selecionando dias e horarios disponiveis.
4. Quando a OS e vinculada a um horario da agenda do tecnico, passa para o status `Enviada`.
5. O tecnico pode aceitar ou rejeitar a OS.
6. Se rejeitar, deve preencher uma observacao com o motivo, e a OS volta ao operador com status `Aberta`.
7. Ao entrar em `Enviada`, o tecnico recebe via WhatsApp um link unico com hash na URL para consultar a OS.
8. Pelo mesmo link, o tecnico aceita ou rejeita a OS. Se aceitar, a OS passa para `Aceita` e o link continua sendo usado durante o atendimento.
9. Ao chegar ao cliente, o tecnico aciona o botao de inicio e a OS passa para `Em atendimento`.
10. Depois de preencher os dados obrigatorios do atendimento, o tecnico aciona `Solicitar conferencia`.
11. O sistema registra a data e a hora reais do termino tecnico, e a OS passa para `Em conferencia`.
12. O operador confere o servico na central.
13. Se a conferencia for reprovada, o operador marca a OS como `Pendente` e informa o motivo em uma observacao.
14. Se a conferencia for aprovada, o operador marca a OS como conferida e ela e finalizada.

Em retiradas ou manutencoes com divergencia de IMEI ou chip, a OS sai de `Em atendimento` para `Aguardando correcao cadastral`. Depois que o operador corrigir o cadastro, ela volta para `Em atendimento`, permitindo que o tecnico confirme os dados e prossiga.

## Status identificados

- `Aberta`
- `Enviada`
- `Aceita`
- `Em atendimento`
- `Aguardando correcao cadastral`
- `Em conferencia`
- `Pendente`
- `Finalizada`
- `Cancelada`

## Cancelamento

- Uma OS pode ser cancelada somente por um operador da central.
- O tecnico nao pode cancelar a OS pelo link com hash.
- `Cancelada` e um estado final e nao permite novas acoes pelo tecnico.
- Uma OS cancelada nao realiza vinculacao nem movimentacao de equipamentos.
- O operador deve informar obrigatoriamente o motivo do cancelamento.
- O motivo e a identificacao do operador ficam registrados no historico da OS.
- O cancelamento invalida imediatamente o hash do tecnico, sem manter acesso para consulta.
- Ao cancelar, o sistema envia automaticamente ao tecnico uma mensagem de WhatsApp informando o cancelamento e o motivo registrado pela central.

## Agenda do tecnico

- Nesta primeira implementacao, a central cadastra os dias e intervalos de horario em que o tecnico esta disponivel, informando hora inicial e hora final.
- Cada disponibilidade pertence a uma data especifica; nao havera cadastro recorrente por dia da semana.
- A central pode cadastrar mais de um intervalo para o mesmo tecnico no mesmo dia, por exemplo 08:00 a 12:00 e 14:00 a 18:00.
- Intervalos do mesmo tecnico no mesmo dia nao podem se sobrepor.
- O sistema divide cada intervalo em blocos consecutivos de 40 minutos.
- Cada OS ocupa um bloco de 40 minutos na agenda.
- Quando o intervalo terminar com uma sobra inferior a 40 minutos, essa sobra e ignorada.
- Exemplo: 08:00 a 09:00 gera apenas o bloco 08:00 a 08:40.
- Exemplo: uma disponibilidade de 08:00 a 12:00 gera seis blocos, iniciados as 08:00, 08:40, 09:20, 10:00, 10:40 e 11:20.
- O operador vincula a OS a um horario disponivel da agenda.
- A OS pode ser criada como `Aberta` sem tecnico atribuido.
- Nesse caso, a data e o horario registrados representam o atendimento desejado.
- A OS somente passa para `Enviada` quando a central atribui um tecnico e a vincula a um bloco disponivel da agenda dele.
- Ao atribuir o tecnico, o operador deve escolher obrigatoriamente um bloco livre de 40 minutos da agenda.
- A data e o horario do bloco escolhido substituem a data e o horario inicialmente desejados na OS.
- A atribuicao exige que o tecnico possua um telefone valido no cadastro para receber o link por WhatsApp.
- Sem telefone valido, a atribuicao e recusada, nenhum bloco e ocupado e a OS permanece `Aberta`.
- A tela orienta a central a corrigir o telefone no cadastro do tecnico.
- Quando a OS for rejeitada, cancelada ou transferida para outro tecnico, o bloco anteriormente ocupado volta a ficar disponivel.
- Em um reagendamento, o bloco anterior e liberado e o novo bloco passa a ficar ocupado pela OS.
- A central nao pode excluir ou reduzir uma disponibilidade se a alteracao remover um bloco ocupado por uma OS.
- Para alterar esse intervalo, primeiro deve reagendar ou cancelar a OS que ocupa o bloco.
- Nao e permitido criar disponibilidade em data ou horario ja passado.
- Nao e permitido agendar ou reagendar uma OS para um bloco ja passado.
- Essa validacao nao impede que uma OS `Aceita` e atrasada seja iniciada normalmente.

Escopo futuro:

- Posteriormente, cada tecnico podera ter um usuario proprio vinculado ao seu cadastro para manter a propria agenda.
- O autoatendimento da agenda pelo tecnico nao faz parte desta primeira implementacao.
- Se a central alterar a data, o horario ou o tecnico de uma OS que ja esteja `Enviada` ou `Aceita`, o aceite anterior deixa de valer.
- Depois dessa alteracao, a OS retorna para `Enviada` e o tecnico precisa realizar um novo aceite.

## Abertura da OS

O operador deve preencher:

- Tipo da OS.
- Cliente.
- Veiculo.
- Endereco do atendimento.
- Data e horario do atendimento.
- Motivo ou descricao do servico.
- Observacoes.
- Localizacao do atendimento recebida pelo WhatsApp, opcional.
- Opcao `Notificar cliente pelo WhatsApp`, desativada por padrao.

Regras de vinculacao:

- Cada OS pertence a um unico cliente e a um unico veiculo desse cliente.
- Nao e permitido selecionar um veiculo pertencente a outro cliente.
- Um atendimento para varios veiculos exige uma OS separada para cada veiculo.
- Um veiculo pode ter somente uma OS ativa por vez, independentemente do tipo.
- Enquanto existir uma OS que nao esteja `Finalizada` nem `Cancelada`, o sistema bloqueia a abertura de outra ordem para o mesmo veiculo.

Identificacao:

- Alem do ID interno, cada ordem recebe um numero sequencial visivel.
- A numeracao usa uma sequencia global continua e nao reinicia a cada ano.
- O numero e usado nas telas da central, na pagina do tecnico, no historico e nas mensagens de WhatsApp.
- Exemplo de apresentacao: `OS 000123`.

Ao selecionar o cliente:

- O endereco da OS e preenchido automaticamente a partir do cadastro do cliente.
- O operador pode alterar o endereco somente para aquela OS.
- A alteracao do endereco na OS nao modifica o cadastro principal do cliente.

Regras da localizacao:

- O sistema aceita o link de localizacao compartilhado pelo WhatsApp ou por um servico de mapas.
- O link original deve ser armazenado.
- O sistema deve extrair e armazenar latitude e longitude quando elas estiverem disponiveis no link.
- A ausencia de localizacao nao impede a abertura nem o andamento da OS.

## Acesso do tecnico

- Quando a OS e enviada ao tecnico, ele recebe pelo WhatsApp um link unico com hash na URL.
- O tecnico usa esse link para consultar os dados da OS e decidir se aceita ou rejeita.
- Se aceitar, ele continua usando o mesmo link para iniciar, preencher e concluir sua parte do atendimento.
- O acesso por hash substitui o login do tecnico nessa pagina; o hash funciona como a credencial de acesso a OS.
- O link nao expira por prazo.
- Depois que a OS e finalizada, o link pode continuar abrindo a consulta, mas nenhuma acao ou alteracao e permitida.
- Se o tecnico atribuido for substituido, o hash anterior e invalidado imediatamente.
- Um novo hash e gerado e enviado ao novo tecnico.
- O link invalidado deixa de permitir tanto consulta quanto alteracao da OS.
- A pagina acessada pelo tecnico deve ser simples, responsiva e priorizar o uso em celular.
- Ao chegar ao cliente, o tecnico deve ter uma acao clara para iniciar o atendimento.
- Depois de aceitar, o tecnico pode iniciar o atendimento em qualquer horario, sem bloqueio pela hora agendada.
- Ao acionar `Iniciar atendimento`, a tela pede confirmacao antes de alterar o status para `Em atendimento`.
- O sistema registra a data e a hora reais do inicio.
- No inicio, a pagina solicita ao celular a localizacao atual do tecnico e registra latitude e longitude junto ao horario real.
- A geolocalizacao do tecnico e opcional.
- Se as coordenadas forem obtidas, elas sao armazenadas; se a permissao for negada ou a obtencao falhar, o atendimento inicia normalmente.
- Negativa ou falha de geolocalizacao nao gera log especifico.
- A tela mobile deve oferecer a acao `Abrir rota`.
- `Abrir rota` usa preferencialmente a latitude e longitude armazenadas; quando nao houver coordenadas, usa o endereco da OS.
- A acao abre o aplicativo ou servico de mapas disponivel no celular.
- A tela do tecnico exibe o telefone do cliente com acoes para ligar e abrir uma conversa no WhatsApp.
- O hash deve ser imprevisivel e nao deve expor o ID sequencial da OS.
- O formulario do atendimento nao precisa salvar rascunho automaticamente.
- Os dados e fotos sao enviados de uma vez na acao `Solicitar conferencia`.
- Se o tecnico sair antes de submeter, o preenchimento ainda nao enviado nao e preservado.

## Fotos do atendimento

- Os anexos enviados pelo tecnico aceitam somente imagens da camera ou da galeria do dispositivo.
- Formatos de imagem web compativeis, como JPG, PNG e WEBP, podem ser aceitos.
- PDF e outros tipos de documento nao sao permitidos.
- A interface mobile deve permitir fotografar no momento ou escolher imagens existentes na galeria.
- Cada atendimento aceita no maximo quatro fotos.
- O sistema deve compactar automaticamente as fotos para aproximadamente 2 MB quando isso puder ser implementado de forma simples e confiavel com a stack adotada.
- Se a compactacao automatica nao for adequada, cada arquivo pode ter no maximo 5 MB.
- Arquivos acima do limite devem ser recusados com uma mensagem clara ao tecnico.

## Confirmacao do cliente

- A primeira versao nao exige assinatura, nome, documento ou confirmacao do cliente na tela do tecnico.
- A solicitacao de conferencia depende somente dos dados e fotos obrigatorios de cada tipo de OS.

## Acesso do operador da central

- O operador da central acessa o sistema normalmente, com autenticacao.
- As telas de criacao, edicao e acompanhamento usam o ID normal da OS nas rotas internas do sistema.
- O novo modulo usa as permissoes `OS_Leitura` e `OS_Escrita`.
- `OS_Leitura` permite acessar e consultar as ordens de servico.
- `OS_Escrita` permite criar e alterar ordens de servico, atribuir agenda, executar a conferencia e cancelar com motivo obrigatorio.
- Ordens de servico nao podem ser excluidas definitivamente; devem ser preservadas e, quando necessario, canceladas.
- Usuarios administradores continuam com acesso total pelo comportamento atual de `User::hasPermission()`.

Regras de edicao:

- A partir de `Em atendimento`, a central nao pode alterar tipo, cliente, veiculo nem tecnico.
- Nesse ponto, permanecem disponiveis apenas observacoes, a correcao cadastral prevista para retirada, a conferencia e o cancelamento com motivo.
- Reagendamento e troca de tecnico devem ocorrer antes do inicio do atendimento.
- Depois de `Finalizada`, a OS fica totalmente bloqueada para edicao tambem na central.
- Uma OS finalizada permite apenas consultar dados, equipamentos, fotos, checklist e historico.

## Navegacao da central

- O painel administrativo tera um novo grupo principal de menu chamado `Ordens de Servico`.
- As telas do fluxo de OS ficarao agrupadas nesse novo menu, separado de `Cadastro`, `Financeiro`, `Estoque`, `Rotinas` e `Administrativo`.
- A listagem de OS possui filtros por status, tipo, tecnico e periodo.
- A busca localiza por numero da OS, nome do cliente e placa do veiculo.
- A ordenacao padrao mostra as OS criadas mais recentemente primeiro.
- O operador pode alternar para ordenar pelo horario de atendimento mais proximo.
- O modulo possui uma visualizacao de agenda ou calendario por tecnico.
- A agenda oferece visoes por dia e por semana.
- A agenda mostra os blocos livres de 40 minutos e as OS agendadas.
- A central pode abrir a OS a partir de um bloco ocupado e usar os blocos livres durante a atribuicao.
- Nesta primeira versao, nao havera geracao de PDF nem layout especifico para impressao da OS.

## Historico e auditoria

- Cada OS exibe uma linha do tempo cronologica das mudancas de status.
- Cada evento registra data e hora.
- Cada evento identifica o operador da central ou o tecnico responsavel.
- Observacoes e motivos associados ao evento ficam visiveis no historico.
- O historico inclui, no minimo: envio, aceite, rejeicao, inicio do atendimento, divergencia cadastral, correcao cadastral, solicitacoes de conferencia, pendencias, novas conferencias, reagendamentos, troca de tecnico, finalizacao e cancelamento.
- Os eventos historicos nao podem ser editados nem excluidos.
- A OS usa um unico tipo de observacao geral, visivel para a central e para o tecnico.
- Nao existe campo separado de observacao interna nesta primeira versao.
- O cliente nao recebe nem visualiza essas observacoes.

## Aceite ou rejeicao

- Enquanto aguarda a resposta do tecnico, a OS permanece como `Enviada`.
- Nao existe prazo automatico para o aceite ou a rejeicao.
- A OS pode permanecer `Enviada` indefinidamente ate o tecnico responder ou a central intervir.
- Antes de responder, o tecnico visualiza numero e tipo da OS, cliente, veiculo e placa, endereco, data e horario, motivo ou descricao e observacoes.
- O endereco e a acao `Abrir rota` ficam disponiveis antes do aceite.
- O tecnico pode aceitar ou rejeitar a OS.
- Ao rejeitar, o tecnico deve preencher uma observacao com o motivo.
- A observacao da rejeicao fica registrada no historico da OS.
- Depois da rejeicao, a OS volta para `Aberta` e fica novamente disponivel ao operador.
- A rejeicao invalida imediatamente o hash usado pelo tecnico.
- Se a OS for enviada novamente, mesmo para o mesmo tecnico, o sistema gera um novo hash.
- Se a central atribuir outro tecnico, o hash anterior tambem e invalidado e um novo e gerado.
- O aceite ou a rejeicao ocorre na pagina aberta pelo link unico enviado por WhatsApp.

## Notificacoes por WhatsApp

- Nesta primeira versao, os textos das mensagens de OS ficam fixos no codigo.
- Os textos devem ficar centralizados no servico de notificacao para permitir configuracao futura sem espalhar conteudo pelo sistema.
- Quando o operador concluir uma correcao cadastral solicitada em uma retirada, o sistema avisa o tecnico e envia novamente o mesmo link.
- Ao vincular a OS a agenda, o sistema envia ao tecnico o link unico para aceitar ou rejeitar.
- Depois do envio, a central tem sempre disponivel a acao `Reenviar link` enquanto a OS ainda permitir acesso do tecnico.
- O status permanece `Enviada` mesmo se a chamada de WhatsApp falhar ou nao houver confirmacao de entrega.
- O fluxo nao depende da validacao de entrega ou leitura da mensagem.
- `Reenviar link` reutiliza o hash atual; nao cria uma nova credencial.
- Ao trocar o tecnico, o sistema invalida o link anterior e envia o novo link ao novo responsavel.
- O sistema envia um lembrete automatico duas horas antes do horario agendado somente quando a OS estiver `Aceita`.
- Uma OS ainda `Enviada`, aguardando resposta do tecnico, nao recebe esse lembrete.
- Quando o operador marcar uma OS como `Pendente`, o sistema envia automaticamente ao tecnico uma mensagem com o motivo e o mesmo link da OS.
- Quando a conferencia for aprovada e a OS finalizada, o sistema envia automaticamente uma mensagem de confirmacao ao tecnico.
- A mensagem de finalizacao pode incluir o mesmo link, que passa a permitir somente consulta.
- As notificacoes ao cliente sao opcionais por OS e ficam desativadas por padrao na abertura.
- O cliente somente recebe mensagens quando o operador ativar deliberadamente `Notificar cliente pelo WhatsApp`.
- O numero destinatario e preenchido automaticamente a partir do telefone do cadastro do cliente.
- O telefone nao pode ser alterado somente na OS; as mensagens usam obrigatoriamente o numero atual do cadastro.
- Se o numero estiver incorreto, ele deve ser corrigido no cadastro do cliente.
- Sem telefone valido no cadastro, o operador nao pode ativar `Notificar cliente pelo WhatsApp`.
- Nesse caso, a tela orienta o operador a corrigir o cadastro do cliente.
- A falta de telefone nao impede a criacao da OS quando a opcao de notificacao estiver desligada.
- Com a opcao ativa, o cliente recebe uma mensagem quando o tecnico aceitar a OS.
- A mensagem de aceite ao cliente informa o numero da OS, o tipo de servico, a data, o horario e o nome do tecnico.
- Com a opcao ativa, o cliente recebe outra mensagem quando a OS for finalizada.
- A mensagem de finalizacao ao cliente informa o numero da OS, o tipo de servico e que o atendimento foi concluido.
- Se uma OS aceita for cancelada e a opcao estiver ativa, o cliente tambem recebe uma mensagem de cancelamento.
- A mensagem ao cliente apenas informa o cancelamento e nao inclui o motivo interno.
- As mensagens ao cliente sao somente textos informativos.
- O cliente nao recebe link e nao acessa a OS, fotos, equipamentos, checklist ou historico.
- Se o horario agendado passar sem o inicio do atendimento, nenhuma mudanca automatica de status acontece.
- A OS permanece `Aceita` e pode ser iniciada normalmente pelo tecnico depois do horario.
- A primeira versao nao gera notificacoes internas para a central quando o tecnico aceita, rejeita, informa divergencia ou solicita conferencia.
- A central acompanha essas mudancas pelos status na lista, agenda e historico da OS.

## Atendimento de instalacao

Validacao na abertura:

- Uma OS de instalacao somente pode ser criada para um veiculo sem rastreador vinculado.
- Se o veiculo ja possuir rastreador, o sistema bloqueia a criacao para evitar instalacao duplicada.
- A central deve usar manutencao ou retirada, conforme o servico necessario.

O tecnico deve informar:

- O rastreador instalado.
- O chip instalado, somente quando o rastreador escolhido ainda nao possuir chip vinculado.
- Uma ou mais fotos do servico.

Regras:

- Somente chips e rastreadores existentes no estoque do tecnico podem ser selecionados.
- Ao selecionar um rastreador que ja possui chip vinculado, o sistema preenche automaticamente o chip e nao exige uma segunda selecao.
- Se o rastreador nao possuir chip vinculado, o tecnico deve selecionar um chip disponivel no estoque dele.
- Mesmo quando houver um chip preenchido automaticamente, o tecnico pode substitui-lo por outro chip do proprio estoque.
- Na finalizacao, o novo chip passa a ficar vinculado ao rastreador, e o chip substituido permanece no estoque do tecnico.
- A instalacao nao exige descricao do servico executado.
- O tecnico nao pode solicitar a conferencia enquanto os campos obrigatorios nao estiverem preenchidos.
- A acao final do tecnico e `Solicitar conferencia`.

## Atendimento de retirada

O tecnico visualiza na OS:

- O IMEI do rastreador atualmente vinculado ao veiculo.
- O chip atualmente vinculado ao rastreador do veiculo.

Validacao na abertura:

- Uma OS de retirada exige que o veiculo tenha rastreador e chip corretamente vinculados no cadastro.
- Se faltar qualquer um desses vinculos, o sistema bloqueia a criacao da OS.
- A tela orienta a central a corrigir o cadastro antes de abrir a retirada.

O tecnico deve confirmar se o equipamento retirado corresponde aos dados exibidos na OS. Essa confirmacao e obrigatoria antes de solicitar a conferencia.

Mesmo quando nao houver divergencia, o tecnico deve anexar pelo menos uma foto do equipamento retirado antes de solicitar a conferencia.

Se o IMEI ou o chip encontrado nao corresponder aos dados da OS:

- O tecnico nao pode concluir o atendimento nem solicitar a conferencia.
- O tecnico pode anexar fotos que comprovem a divergencia, mas elas sao opcionais nessa etapa.
- O tecnico deve preencher obrigatoriamente uma observacao descrevendo a divergencia.
- O operador deve corrigir o cadastro do equipamento na central.
- A correcao e realizada nas telas e estruturas de cadastro de veiculo, rastreador e chip que ja existem no sistema, e nao dentro do formulario da OS.
- Depois da correcao externa, o operador volta a OS e aciona `Cadastro corrigido`.
- A acao valida novamente os vinculos atuais do veiculo, rastreador e chip.
- Se os dados ainda estiverem inconsistentes, a OS permanece `Aguardando correcao cadastral` e a tela informa o que falta corrigir.
- Durante esse periodo, a OS fica com status `Aguardando correcao cadastral`.
- Depois da correcao, a OS volta para `Em atendimento`.
- Ao concluir a correcao, o sistema envia automaticamente ao tecnico uma mensagem de WhatsApp com o mesmo link da OS.
- Somente entao o tecnico pode confirmar os dados e continuar o fechamento da OS.

Depois que a OS for conferida e finalizada:

- O rastreador e o chip deixam de estar instalados no veiculo.
- O equipamento retirado passa a constar no estoque do tecnico que executou a OS.

## Atendimento de manutencao

Validacao na abertura:

- Uma OS de manutencao exige que o veiculo tenha rastreador e chip corretamente vinculados no cadastro.
- Se faltar qualquer um desses vinculos, o sistema bloqueia a criacao e orienta a central a corrigir o cadastro primeiro.

Ao abrir o atendimento, o tecnico visualiza:

- O IMEI do rastreador atualmente vinculado ao veiculo.
- O chip atualmente vinculado ao rastreador.
- Antes de registrar o resultado, o tecnico deve confirmar explicitamente que o IMEI e o chip encontrados correspondem aos dados exibidos.
- Sem essa confirmacao, ele deve seguir o fluxo de divergencia cadastral e nao pode concluir normalmente.

Se o IMEI ou chip encontrado nao corresponder ao cadastro:

- O tecnico nao pode concluir o atendimento nem solicitar conferencia.
- O tecnico deve preencher uma observacao sobre a divergencia e pode anexar fotos opcionais como apoio.
- A OS passa para `Aguardando correcao cadastral`.
- A central corrige os vinculos e devolve a OS para `Em atendimento`.
- A correcao usa a estrutura de cadastro existente, sem criar edicao de equipamentos dentro da OS.
- O tecnico recebe um aviso por WhatsApp e retoma pelo mesmo link.

O tecnico deve registrar um dos seguintes resultados:

- Reparo realizado sem troca de equipamento.
- Troca somente do rastreador.
- Troca somente do chip.
- Troca do rastreador e do chip.
- Equipamento testado e identificado sem defeito.

Para qualquer resultado de manutencao, o tecnico tambem deve:

- Preencher uma descricao do servico executado ou do diagnostico realizado.
- Anexar uma ou mais fotos.
- Preencher todos os dados obrigatorios antes de solicitar a conferencia.

Nos resultados `Reparo realizado sem troca de equipamento` e `Equipamento testado e identificado sem defeito`:

- Os vinculos atuais de rastreador e chip permanecem inalterados.
- Nenhum item entra ou sai do estoque do tecnico.

Quando houver troca:

- O tecnico somente pode selecionar rastreadores e chips disponiveis no estoque dele.
- Ao escolher um novo rastreador que ja tenha chip vinculado, o sistema preenche esse chip automaticamente.
- O tecnico pode manter o chip preenchido ou substitui-lo por outro chip do proprio estoque.
- No resultado `Troca somente do rastreador`, o conjunto final respeita a relacao do novo rastreador escolhido.
- Se o novo rastreador ja possuir chip, esse chip passa a compor o conjunto instalado; o chip antigo retorna ao estoque do tecnico junto com o rastreador removido.
- A classificacao do resultado descreve a intervencao principal, mas os vinculos finais sempre devem permanecer coerentes com a relacao rastreador-chip selecionada.
- No resultado `Troca somente do chip`, o rastreador atual permanece instalado e apenas seu vinculo `chip_id` e atualizado para o novo chip selecionado.
- O chip anterior retorna ao estoque do tecnico quando a OS e finalizada.
- Na finalizacao da OS, o vinculo do veiculo deve ser atualizado para refletir o novo rastreador e/ou chip informado no atendimento.
- O rastreador e/ou chip antigo removido do veiculo passa automaticamente para o estoque do tecnico que executou a manutencao.
- O novo item deixa o estoque do tecnico ao ser vinculado ao veiculo.

## Conferencia do operador

Para instalacao e manutencao, o operador deve conferir os seguintes itens:

- Funcionamento do equipamento.
- Pos-chave, com veiculo ligado e desligado.
- Bloqueio do veiculo, quando aplicavel.

Regras do checklist:

- `Funcionamento do equipamento` e `Pos-chave` devem ser confirmados para aprovar instalacoes e manutencoes.
- Durante a conferencia, o operador decide manualmente se o teste de bloqueio se aplica.
- Quando nao houver bloqueio, o operador marca esse item como `Nao se aplica`.
- Quando houver bloqueio, o operador deve confirma-lo como conferido.
- A central nao pode finalizar uma instalacao ou manutencao enquanto o checklist obrigatorio estiver incompleto.
- `Funcionamento do equipamento` e `Pos-chave` devem estar conferidos, e `Bloqueio do veiculo` deve estar conferido ou marcado como `Nao se aplica`.

Para retirada:

- Nao e necessario exibir o checklist acima.
- O operador apenas marca o servico como conferido.

Resultados da conferencia:

- **Aprovada:** o operador marca como conferida e a OS e finalizada.
- **Reprovada:** o operador marca como `Pendente` e deve preencher o motivo em uma observacao.

Marcos de tempo:

- `Termino tecnico`: registrado quando o tecnico aciona `Solicitar conferencia`.
- `Finalizacao administrativa`: registrada separadamente quando a central aprova a conferencia e finaliza a OS.

Quando a OS fica `Pendente`:

- O tecnico usa o mesmo link com hash que ja recebeu.
- O motivo informado pelo operador fica visivel ao tecnico.
- Os dados preenchidos e anexos anteriores continuam carregados para edicao.
- O tecnico pode remover e substituir fotos, respeitando o limite total de quatro imagens atuais.
- Fotos removidas nao precisam ser preservadas no historico; permanecem os eventos, motivos e novas solicitacoes de conferencia.
- O tecnico corrige ou complementa o atendimento e solicita uma nova conferencia.
- A OS volta para `Em conferencia` sem gerar um novo hash.
- As tentativas e observacoes ficam preservadas no historico.
- O checklist preenchido na conferencia anterior nao e zerado.
- Na nova conferencia, o operador pode revisar e alterar os itens ja marcados.

Enquanto a OS estiver `Em conferencia`:

- O tecnico pode consultar os dados e fotos enviados, mas nao pode edita-los.
- A edicao volta a ser liberada somente se a central alterar o status para `Pendente`.

## Vinculacao de equipamento ao finalizar

Na finalizacao de uma instalacao, o IMEI do rastreador e o chip informados no atendimento sao vinculados automaticamente ao veiculo do cliente dono da OS.

Na finalizacao de uma retirada, o rastreador e o chip sao desvinculados do veiculo e passam para o estoque do tecnico que executou a OS.

Observacao tecnica ja conhecida no sistema: o chip e vinculado ao rastreador por `rastreadores.chip_id`; novas regras nao devem usar o campo legado `veiculos.chip_id`.

Na finalizacao de uma manutencao com troca, o vinculo do veiculo e atualizado com o novo rastreador e/ou chip selecionado no estoque do tecnico.

Os itens substituidos sao transferidos para o estoque do tecnico que realizou o atendimento, enquanto os novos itens deixam esse estoque e passam a compor o conjunto instalado no veiculo.

## Selecao e movimentacao de estoque

- Nao e necessario reservar o chip ou rastreador quando o tecnico o seleciona no atendimento.
- Cada item disponivel ja pertence ao estoque de um unico tecnico.
- O estoque do tecnico e determinado pelo vinculo existente entre o item e o tecnico.
- Esse vinculo e sua manutencao ja estao implementados no sistema e devem ser reutilizados.
- O novo fluxo de OS nao precisa criar um historico de transferencias entre o estoque central e o tecnico.
- O tecnico executa apenas um atendimento por vez.
- A movimentacao definitiva do estoque e dos vinculos ocorre somente na finalizacao da OS.

## Decisoes registradas durante o levantamento

- Na retirada, o tecnico visualiza e confirma o IMEI e o chip atualmente associados ao veiculo.
- Ao finalizar a retirada, o rastreador e o chip sao desvinculados do veiculo e transferidos para o estoque do tecnico que realizou o atendimento.
- Toda retirada exige pelo menos uma foto do equipamento retirado.
- Se houver divergencia de IMEI ou chip na retirada, o tecnico nao pode fechar a OS. Ele registra fotos e observacao, o operador corrige o cadastro e somente depois o tecnico pode confirmar e prosseguir.
- Durante a divergencia da retirada, a OS fica como `Aguardando correcao cadastral` e retorna para `Em atendimento` depois da correcao pelo operador.
- A manutencao pode resultar em reparo sem troca, troca do rastreador, troca do chip, troca dos dois ou equipamento sem defeito.
- Itens de substituicao devem pertencer ao estoque do tecnico, e o vinculo do veiculo deve refletir os novos itens depois da finalizacao.
- Na manutencao com troca, os itens antigos entram automaticamente no estoque do tecnico que executou a OS.
- Toda manutencao exige descricao do servico ou diagnostico e pelo menos uma foto antes da solicitacao de conferencia.
- Na abertura da OS sao informados tipo, cliente, veiculo, endereco, data e horario, motivo ou descricao, observacoes e localizacao recebida pelo WhatsApp.
- A localizacao e opcional; o sistema armazena o link recebido e extrai latitude e longitude quando possivel.
- A agenda usa intervalos informados pelo tecnico e gera blocos de atendimento com duracao fixa de 40 minutos.
- A agenda aceita multiplos intervalos nao sobrepostos no mesmo dia.
- As disponibilidades sao cadastradas para datas especificas, sem recorrencia semanal.
- A rejeicao exige uma observacao do tecnico; a OS volta para `Aberta` e o motivo permanece no historico.
- Um unico link com hash e enviado por WhatsApp quando a OS entra em `Enviada`; ele serve para aceitar ou rejeitar e, depois do aceite, para executar o atendimento.
- O link com hash nao exige login nem expira por tempo. Apos a finalizacao, permite apenas consulta, sem novas acoes.
- O operador da central usa as rotas autenticadas normais do sistema, identificadas pelo ID da OS.
- Somente a central pode cancelar uma OS; o cancelamento encerra as acoes e nao movimenta equipamentos.
- O cancelamento exige motivo, que fica registrado no historico com o operador responsavel.
- Alterar data, horario ou tecnico de uma OS `Enviada` ou `Aceita` exige um novo aceite e retorna a OS para `Enviada`.
- Na troca de tecnico, o hash anterior e invalidado e um novo link e gerado para o novo responsavel.
- O tecnico recebe um lembrete automatico pelo WhatsApp duas horas antes do horario agendado.
- O lembrete de duas horas e enviado somente para OS `Aceita`.
- Uma OS `Pendente` e corrigida pelo tecnico no mesmo link; depois, ele solicita nova conferencia e o historico das tentativas e preservado.
- Ao entrar em `Pendente`, o tecnico recebe pelo WhatsApp o motivo e novamente o mesmo link de acesso.
- Equipamentos selecionados durante o atendimento nao sao reservados; a movimentacao definitiva ocorre na finalizacao.
- O estoque individual ja e controlado pelo vinculo do item ao tecnico; o fluxo de OS reutiliza essa implementacao e nao adiciona historico de abastecimento.
- Na instalacao, o chip vinculado ao rastreador e preenchido automaticamente; a selecao manual ocorre apenas quando o rastreador nao possui chip.
- O tecnico pode trocar o chip preenchido automaticamente, mas somente por outro item do estoque dele.
- A aplicabilidade do teste de bloqueio e decidida manualmente pelo operador durante a conferencia.
- O painel ganha um novo grupo de menu chamado `Ordens de Servico`.
- O modulo tera apenas as permissoes `OS_Leitura` e `OS_Escrita`; nao havera exclusao definitiva de OS.
- O endereco e preenchido pelo cadastro do cliente, mas pode ser ajustado especificamente para a OS sem alterar o cliente.
- A tela do tecnico possui o botao `Abrir rota`, usando coordenadas quando disponiveis e o endereco como alternativa.
- O atendimento pode ser iniciado a qualquer horario apos o aceite, mediante confirmacao, e o horario real de inicio fica registrado.
- O inicio tambem registra a localizacao atual do tecnico obtida pelo celular.
- A localizacao de inicio e opcional; sua ausencia nao bloqueia o atendimento nem gera registro de falha.
- Cada OS possui historico cronologico imutavel com status, data/hora, responsavel e observacao quando houver.
- Cada OS possui um numero sequencial visivel, separado do ID interno.
- A numeracao da OS e global, continua e nao reinicia por ano.
- Uma OS pode ser aberta sem tecnico; ela permanece `Aberta` ate ser vinculada a um bloco disponivel da agenda.
- A atribuicao exige um bloco livre da agenda, e seu horario passa a ser o horario efetivo da OS.
- Na primeira versao, a agenda dos tecnicos e cadastrada pela central; usuarios proprios para os tecnicos ficam para uma evolucao futura.
- Rejeicao, cancelamento, troca de tecnico e reagendamento liberam automaticamente o bloco de agenda anterior.
- Uma OS `Aceita` permanece nesse status quando o horario passa e pode ser iniciada posteriormente, sem penalidade ou transicao automatica.
- Os anexos do tecnico aceitam somente imagens da camera ou galeria, sem PDF ou outros documentos.
- Cada OS aceita no maximo quatro fotos de atendimento.
- A implementacao deve preferir compactacao automatica simples; caso contrario, deve limitar o tamanho do upload.
- Sem compactacao automatica, cada imagem fica limitada a 5 MB.
- Ao corrigir uma divergencia cadastral de retirada, a central dispara um aviso de WhatsApp para o tecnico retomar a OS pelo mesmo link.
- Manutencoes sem troca nao alteram vinculos nem movimentam estoque.
- A OS nao exige assinatura ou confirmacao do cliente nesta primeira versao.
- Ao finalizar a OS, o tecnico recebe uma confirmacao por WhatsApp e o link passa a ser somente leitura.
- Notificacoes ao cliente sao opt-in por OS, desativadas por padrao, e ocorrem no aceite do tecnico e na finalizacao.
- O WhatsApp do cliente usado pela OS vem obrigatoriamente do cadastro e nao pode ser sobrescrito na ordem.
- Os modelos de mensagem da OS ficam fixos no codigo nesta primeira versao.
- Cada OS atende somente um veiculo; clientes com frota exigem ordens separadas.
- A tela mobile mostra o telefone do cliente e oferece atalhos para ligar e abrir o WhatsApp.
- A lista da central permite filtrar por status, tipo, tecnico e periodo, e buscar por numero da OS, cliente ou placa.
- A central possui uma agenda visual por tecnico com horarios livres e OS agendadas.
- A agenda pode ser alternada entre visao diaria e semanal.
- Disponibilidades com blocos ocupados nao podem ser reduzidas ou excluidas ate que as OS afetadas sejam reagendadas ou canceladas.
- Agenda e agendamento nao aceitam datas ou horarios passados, sem bloquear o inicio tardio de uma OS ja aceita.
- Sobras inferiores a 40 minutos no fim de uma disponibilidade nao geram bloco de agenda.
- O aceite nao tem prazo automatico; uma OS pode permanecer `Enviada` ate a central intervir.
- A central nao recebe alertas internos; acompanha o fluxo pela listagem, agenda e historico.
- A listagem ordena por criacao mais recente por padrao e oferece ordenacao pelo atendimento mais proximo.
- A retirada nao pode ser criada sem rastreador e chip vinculados ao veiculo; o cadastro deve ser corrigido antes.
- A manutencao tambem exige rastreador e chip previamente vinculados ao veiculo.
- A instalacao e bloqueada quando o veiculo ja possui rastreador vinculado.
- O sistema impede duas OS ativas simultaneas para o mesmo veiculo.
- A rejeicao ou a troca de tecnico invalida o hash anterior; todo novo envio posterior usa um novo hash.
- O cancelamento tambem invalida o hash e dispara uma mensagem de WhatsApp ao tecnico com o motivo.
- Em uma OS com notificacao do cliente ativa, o cancelamento posterior ao aceite tambem gera um aviso ao cliente.
- O motivo do cancelamento e enviado ao tecnico, mas nao ao cliente.
- A mensagem de aceite ao cliente identifica OS, tipo, data, horario e tecnico.
- A mensagem de finalizacao ao cliente identifica a OS e o tipo e confirma a conclusao do atendimento.
- O cliente nao possui acesso ao sistema nem recebe link da OS.
- Antes do aceite, o tecnico visualiza todos os dados necessarios para avaliar o atendimento e pode abrir a rota.
- Instalacoes e manutencoes so podem ser finalizadas com todo o checklist de conferencia resolvido.
- Em uma pendencia, o tecnico pode remover ou substituir fotos; o historico nao conserva os arquivos removidos.
- O checklist anterior e mantido quando uma OS pendente retorna para nova conferencia.
- O atendimento mobile nao possui rascunho; o formulario e persistido ao solicitar conferencia.
- A manutencao mostra ao tecnico o IMEI e o chip atuais do veiculo antes da escolha do resultado.
- Divergencias de equipamento na manutencao seguem o mesmo fluxo de correcao cadastral usado na retirada.
- Correcoes de divergencia sao feitas pelas estruturas cadastrais existentes, fora da tela da OS.
- A acao `Cadastro corrigido` revalida os vinculos antes de devolver a OS ao tecnico.
- Em uma divergencia, a observacao e obrigatoria e as fotos sao opcionais; as exigencias normais de fotos para concluir o atendimento continuam valendo.
- As observacoes sao gerais e compartilhadas entre central e tecnico; nao ha observacao interna separada.
- O formulario tecnico fica somente leitura durante `Em conferencia` e reabre para edicao apenas em `Pendente`.
- A manutencao exige confirmacao explicita do IMEI e chip atuais antes do registro do resultado.
- Na troca de rastreador em manutencao, o chip pre-vinculado e preenchido automaticamente, mas pode ser trocado por outro do estoque do tecnico.
- Na troca somente do rastreador, o conjunto final assume a relacao rastreador-chip do novo item, sem forcar a manutencao do chip antigo.
- Na troca somente do chip, o rastreador atual e preservado e apenas o chip vinculado e substituido.
- O termino tecnico e a finalizacao administrativa possuem datas e horas registradas separadamente.
- Tipo, cliente, veiculo e tecnico ficam bloqueados para a central depois do inicio do atendimento.
- Uma OS finalizada e imutavel para o tecnico e para a central, permanecendo disponivel somente para consulta.
- A primeira versao nao gera PDF nem versao para impressao da OS finalizada.
- Um tecnico sem telefone valido nao pode receber uma OS; a ordem permanece `Aberta` ate a correcao cadastral.
- A central pode reenviar o link a qualquer momento aplicavel; falha ou ausencia de confirmacao do WhatsApp nao tira a OS de `Enviada`.
