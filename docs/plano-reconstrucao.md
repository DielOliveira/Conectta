# Plano de reconstrucao

Este projeto sera reconstruido em PHP a partir do sistema atual em OutSystems.

## Principio principal

Primeiro objetivo: equivalencia funcional.

Nao vamos melhorar, simplificar ou redesenhar regras antes de reproduzir o que ja funciona hoje. Evolucoes ficam para uma segunda fase, depois que o sistema PHP estiver confiavel.

## Etapas sugeridas

1. Congelar o modelo atual
   - documentar entidades: primeira versao feita a partir do diagrama e do OutDoc
   - documentar campos: primeira versao feita com tipos do OutDoc
   - documentar relacionamentos: primeira versao feita com FKs do OutDoc
   - confirmar obrigatoriedade, valores padrao, unicidade e regras

2. Escolher stack PHP
   - WSL definido: Ubuntu
   - PHP definido: versao estavel atual no momento da instalacao
   - framework definido: Laravel
   - banco definido: MySQL
   - painel administrativo definido: Filament
   - interface dinamica definida: Livewire
   - CSS/base visual: Tailwind

3. Criar base do projeto
   - estrutura PHP
   - variaveis de ambiente
   - conexao com banco
   - migrations
   - seeders das tabelas de apoio

4. Implementar cadastros principais
   - clientes
   - veiculos
   - vendedores
   - tabelas de status/tipo/origem

5. Implementar estoque
   - rastreadores
   - chips
   - tecnicos
   - vinculo entre estoque e veiculo

6. Implementar financeiro
   - lancamentos
   - logs
   - faturamento
   - invoice

7. Implementar integracoes
   - Z-Api
   - Lytex
   - ZapSign

8. Migracao e conferencia
   - importar dados do OutSystems
   - comparar totais por tabela
   - conferir clientes, veiculos, rastreadores e financeiro
   - validar fluxos criticos com dados reais

## Decisoes pendentes

- Hospedagem final: VPS, hospedagem compartilhada, Docker, painel ou outro ambiente.
- Como extrair os dados atuais do OutSystems: Excel, API, exportacao de banco ou relatorios.

## Decisoes tomadas

- Framework PHP: Laravel.
- Banco de dados: MySQL.
- Painel administrativo: Filament.
- Interface dinamica: Livewire.
- CSS/base visual: Tailwind.
- Ambiente inicial: WSL com Ubuntu.
- PHP: versao estavel atual no momento da instalacao.
- Autenticacao: login simples desde o inicio, com um usuario administrador inicial.
- Permissoes: controle granular por roles sera implementado somente depois dos modulos principais estarem funcionais.
- Estrategia: reconstruir com equivalencia funcional ao OutSystems antes de evoluir ou redesenhar regras.
- Idioma: toda interface, botoes, validacoes e mensagens visiveis ao usuario devem ficar em portugues do Brasil. Nao usar textos em ingles na experiencia do usuario.
- Tema visual: manter o tema sugerido pelo Filament em tons amber/laranja. Acoes primarias usam botao laranja preenchido; acoes secundarias usam botao branco com borda/texto laranja ou neutro. Evitar azul em botoes, icones de acao e focos de campos, mesmo quando prints antigos do OutSystems estiverem em azul.

## Estrategia de autenticacao e permissoes

O Conectta tera autenticacao desde o inicio, para que o sistema ja nasca com painel protegido e registros associados a usuario quando necessario.

Na fase inicial:

- login simples;
- um usuario administrador;
- todas as telas liberadas para usuarios logados.

Na fase final antes de producao:

- implementar roles/permissoes equivalentes ao OutSystems;
- esconder menus conforme perfil;
- bloquear acoes por permissao;
- testar usuarios por area.

## Proxima etapa tecnica

Antes de criar migrations, revisar com o dono do sistema:

- quais campos sao obrigatorios;
- quais campos sao unicos;
- como funcionam exclusoes logicas;
- como sao gerados `NUMR`;
- o papel real das actions de cliente, veiculo e lancamentos;
- onde estao implementadas as chamadas para Z-Api, Lytex e ZapSign, ja que o OutDoc deste eSpace nao listou REST/SOAP consumidos.

## Decisao de ambiente de trabalho

A partir de 22/06/2026, o desenvolvimento e a execucao do Conectta serao feitos prioritariamente dentro do filesystem nativo do Ubuntu/WSL, em `~/Conectta`.

Motivos:

- melhor desempenho do Laravel;
- menos problemas de permissao;
- Composer e Artisan mais estaveis;
- ambiente mais parecido com producao Linux;
- servidor local mais confiavel.

A pasta `C:\Conectta` fica como espelho/backup e apoio para arquivos acessados pelo Windows.
