# Historico Operacional Do Conectta

Este arquivo preserva fatos datados que foram retirados do `AGENTS.md` para reduzir o contexto carregado em cada sessao. Ele nao representa necessariamente o estado atual. Antes de agir, conferir Git, banco, auditorias e producao.

## 2026-07-07 - Restore E Dados Legados

- `tipo_veiculo_id`: 9.230 veiculos corrigidos por mapa legado.
- `cliente_origem_id`: 793 clientes corrigidos.
- `status_contrato_id`: 2.202 clientes e 457 contratos corrigidos.
- `status_rastreador_id`: um rastreador corrigido para `Lixo`.
- `lancamentos.numero_boleto`: 6.756 valores restaurados para `-`.
- `lancamentos.observacao`: 6.637 valores restaurados para `-`, preservando conteudo real.
- Backup anterior aos hifens: `conectta-conectta-20260707-195453.sql.gz`, SHA256 `c4eb88e94e21298db005d194ea15614e8858d94a125edd02d501737847e41945`.
- Alerta GitGuardian `Generic Database Assignment` no commit `62644c6`: a verificacao nao encontrou segredo real; o alerta veio dos nomes de variaveis do script MySQL. O script passou a usar arquivo temporario com permissao `600` em vez de `MYSQL_PWD`.

## 2026-07-08 - Migracao Do Vinculo De Chips

- `rastreadores.chip_id` passou a ser a fonte do vinculo; 4.259 rastreadores receberam chips e nao restaram chips compartilhados entre rastreadores ativos.
- Em 12 conflitos legados, o chip ficou no registro mais recente por `data_instalacao`, `updated_at` e `id`; os demais rastreadores ficaram sem chip para verificacao manual.
- Casos: veiculo/rastreador `19942/23138`, `19952/20724`, `20222/19937`, `20468/21205`, `21456/18807`, `22071/31176`, `22207/18741`, `23323/31833`, `23329/31868`, `23533/22974`, `23799/31957` e `23918/21106`.
- Artefatos auxiliares foram gerados em `tmp/`: `conflitos-veiculos-rastreadores-ativos.xlsx`, `chips-vinculados-a-mais-de-um-rastreador-ativo.txt` e `rastreadores-sem-chip-apos-migracao.txt`.
- Producao foi validada no commit `58cc7e1`, com login HTTP 200 e sem migrations pendentes naquele momento.

## 2026-08-04 A 2026-08-15 - Primeira Versao De OS

- O modulo de OS entrou em producao em 2026-08-04. A versao com fotos no Google Drive foi validada como `1.1.3`, commit `d19afa2`.
- Commits historicos do modulo: `df3f3ea`, `cf26028`, `da301a7` e `d19afa2`.
- A unica foto anterior ao ajuste de armazenamento permaneceu local em `storage/app/private/ordens-servico/1/`; qualquer migracao desse arquivo real exige autorizacao.
- As primeiras OS reais foram 7 e 8, manutencoes do tecnico Hiago em 2026-08-15.
- A OS 7 teve status alterado indevidamente de `enviada` para `aberta` apos edicao. O status foi restaurado com historico `correcao_status_pre_operacao`.
- A causa foi corrigida para impedir que o formulario comum sobrescrevesse workflow. Tambem foi corrigida a troca somente do rastreador para reaproveitar o chip atual.
- Uma simulacao transacional completa das OS 7 e 8 foi executada e revertida sem deixar dados ou fotos.
- Correcoes publicadas no commit `5710fc5`.
- Backup anterior: `conectta-conectta-20260814-173902.sql.gz`, SHA256 `deca34a59b9541bc046022995e5b0628301b4e2bed9d636f9a9dbbfd65e4f5ca`.

## 2026-08-20 - Regularizacoes De Estoque

- Backup base: `conectta-conectta-20260820-092610.sql.gz`, SHA256 `d131131fcba2d70add51a6aee9d0663d1c102588c5c3332c74c0805fa8cd127e`, salvo localmente, na VPS e no Google Drive.
- O tecnico `Temporario - Estoque` (ID 19537) iniciou com 1.072 chips e 15 rastreadores.
- 555 chips instalados em veiculos ativos foram alterados para `Ativo`, sem tecnico. Auditorias `estoque.chip_instalado_regularizado` e resumo `estoque.temporario_instalados_regularizados`.
- Depois, 14 chips e 3 rastreadores ligados somente a veiculos cancelados foram disponibilizados. Auditorias `estoque.chip_cancelado_disponibilizado`, `estoque.rastreador_cancelado_disponibilizado` e resumo correspondente.
- Os 12 rastreadores ativos restantes e seus 12 chips, sem veiculo ou OS, foram disponibilizados e retirados do tecnico temporario.
- Por fim, 499 chips disponiveis foram retirados do tecnico temporario. Estado final: nenhum chip ou rastreador vinculado ao tecnico 19537.
- Uma regularizacao global tratou 29 rastreadores e 39 chips `Ativo` ainda com tecnicos. Instalados perderam o tecnico; sem veiculo ativo ficaram `Disponivel`. A validacao zerou equipamentos ativos com tecnico e rastreadores ativos marcados como estoque.
- 3.334 chips `Disponivel` ligados a rastreadores ativos foram sincronizados para `Ativo`, sem tecnico; 247 ainda tinham tecnico antes da correcao.
- Rastreador 19777/chip 5892 e rastreador 21556/chip 6338 foram corrigidos individualmente.
- As 102 divergencias restantes entre pares vinculados sem veiculo ativo foram sincronizadas para `Disponivel`; 91 chips e 89 rastreadores mudaram. A validacao terminou com zero divergencias entre 4.527 pares.
- No estoque do tecnico Erivan (ID 19501), 60 chips sem par no estoque foram retirados. Ficou apenas o chip 8161 pareado ao rastreador 32492.
- Auditorias detalhadas permanecem em `audit_logs`; consultar por prefixo `estoque.` e pela data antes de repetir qualquer procedimento.

## 2026-08-20 - Regras Corrigidas De Cancelamento

- O cancelamento direto passou a disponibilizar rastreador e chip para o tecnico da remocao e marcar o rastreador em estoque.
- A sincronizacao das telas passou a reativar equipamentos somente quando o veiculo esta `Ativo`.
- A cobertura foi adicionada a `RastreadorFlowTest` e `EditRastreadorResourceTest`.

## 2026-08-21 - Agenda, Fotos E Exclusao De Veiculo

- No banco local foram aplicadas individualmente as migrations de `tipo` em disponibilidades e `nome_tecnico_externo` em OS. A migration de restauracao de historico de retiradas ficou pendente naquele momento.
- O formulario de disponibilidade recebeu `->columnSpanFull()` na section principal.
- A tela publica do tecnico recebeu acoes separadas para camera traseira e galeria, mantendo limite conjunto de quatro fotos.
- O fluxo de exclusao logica de veiculo ganhou protecao para duplicidades legadas e cobertura em `VeiculoExclusaoTest`.
- Referencias a worktree, migrations pendentes, contagens de testes e publicacao eram retratos daquele momento e nao devem ser usadas como estado atual.

## 2026-08-21 - Regularizacao Do IMEI 861768071896419

- Rastreador ID 23210 e chip ID 6131 haviam sido retirados do veiculo `SCZ-0I21`, mas ficaram `Ativo`, sem tecnico e fora do estoque por causa do fluxo antigo.
- A retirada apontava o tecnico Hiago (ID 19496) e data 2026-08-19; nao havia veiculo nem OS ativa vinculada.
- Com autorizacao explicita e sem backup, rastreador e chip foram alterados transacionalmente para `Disponivel` e atribuidos ao Hiago; o rastreador recebeu `is_estoque = true`.
- Auditorias: IDs 17023 (`estoque.rastreador_retirada_regularizado`) e 17024 (`estoque.chip_retirada_regularizado`).

## Homologacoes E Artefatos Antigos

- Cliente usado em testes controlados: Diel Oliveira de Faria, ID 1470. Seu uso sempre depende de autorizacao.
- Houve envio real de WhatsApp de teste, envio 11, geracao de boleto em homologacao e validacao de linha digitavel/PIX via `transactions` da Lytex.
- Banners de apresentacao de OS foram gerados em `tmp/`.
- Esses dados servem apenas para rastreabilidade; nao presumir que arquivos, lancamentos ou configuracoes ainda existam.
