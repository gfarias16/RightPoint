# Contagens dos perfis piloto nos municípios do RJ

Atualizado em 03/11/2026. **Código testado; exportação obtida pelo usuário e simulação real aprovada; carga ainda bloqueada por metadados pendentes.** Este procedimento amplia a preparação para restaurantes, padarias e o grupo CNAE usado como aproximação de barbearias. Não produz score, lista individual de empresas ou agrupamento automático dos dois CNAEs de padarias na interface.

## Ponto atual de retomada

O usuário já executou a query e salvou `Right_Point/storage/app/amostras/contagens-perfis-piloto.json` (83.756 bytes, ignorado pelo Git). Simulação do agente: 368 pares válidos, 92 municípios por CNAE; somas 39.423 (`5611201`), 25.188 (`1091102`), 8.992 (`4721102`) e 102.086 (`9602501`). Hash bruto e LF: `c6f18525e52c489384b8feaa9c6e700ccdf60acef3ee1b682facece2db6ce3b1`. JSON e registros completos das cinco tabelas permaneceram iguais antes/depois; banco conserva 93 coletas de restaurantes. Oito migrations `Ran`.

**Não repetir a aquisição da seção 1.** Consultar os detalhes do job existente: confirmar SQL, filtros e completude, início com fuso/UTC e bytes processados/faturados. Esses metadados ainda não foram fornecidos ao agente. A configuração continua vazia e a gravação bloqueada. Seguir da seção 3 após a conferência. Data do arquivo e soma igual à coleta antiga não substituem o instante do novo job. Para outra máquina, levar ambos os JSONs e os metadados sanitizados separadamente; ver [ambiente entre computadores](./ambiente-multiplos-computadores.md#retomada-dos-perfis-piloto--03112026).

## Escopo e compatibilidade

- CNAEs principais aceitos: `5611201`, `1091102`, `4721102` e `9602501`; situação textual `Ativa`, UF `RJ`.
- `9602501` inclui cabeleireiros, manicure e pedicure. A contagem não pode ser apresentada como exclusiva de barbearias.
- Padarias com produção própria e revenda ficam em coletas distintas. A consulta existente por CNAE já pode ler essas coletas depois de carregadas; não soma subclasses automaticamente.
- O comando antigo `cnpj:importar-contagens-rj` conserva seu contrato: 92 municípios, apenas restaurantes e hash/UTC fixos da fotografia antiga. O piloto singular também permanece disponível. Não mudar a configuração antiga para aceitar uma exportação nova.
- Nenhuma migration é necessária: a tabela existente identifica município, atividade, fonte, critério e instante de consulta. As 93 coletas locais existentes foram preservadas.

## 1. Preparar a aquisição

Usar [cnpj-rj-perfis-piloto.sql](../consultas/cnpj-rj-perfis-piloto.sql), mantendo os quatro CNAEs separados. A consulta projeta somente identificação municipal, CNAE, situação e controles de contagem; não exporta contatos, QSA ou CNPJs individuais.

1. Conferir esquema, publicação e referência da tabela atual. Não atribuir retroativamente uma edição nova à carga antiga.
2. Colar o SQL em nova aba do BigQuery e aguardar a validação/estimativa, **sem executar**.
3. Conferir o teto máximo de bytes daquela aba e compartilhar estimativa e teto para revisão. Não presumir custo igual ao lote antigo nem herdar autorizações anteriores.
4. Somente após autorização específica, executar e exportar o resultado existente, registrando SQL, instante com fuso/UTC e procedência. Não repetir a execução só para baixar.
5. Guardar a exportação na pasta ignorada `Right_Point/storage/app/amostras/`, preservando os arquivos anteriores. O nome pode ser legível ou o original do download.

A validação local do SQL usou SQLite na memória com compatibilidade de COUNTIF, dados fictícios e a mesma projeção/filtros. Isso verifica lógica de filtros/agrupamentos/controles, não valida GoogleSQL, esquema vivo, permissões, bytes ou custo.

## 2. Validar e simular localmente

Dentro de `Right_Point/`, substituindo o exemplo pelo caminho real:

```powershell
php artisan cnpj:importar-contagens-piloto "storage/app/amostras/contagens-perfis-piloto.json"
```

O novo comando não faz consultas externas. Aceita uma lista JSON não vazia de até 368 grupos município/CNAE e até 1 MiB. Exige os oito campos textuais do SQL, sem extras; valida filtros, números canônicos entre 0 e 4294967295, igualdade de linhas/distintos, ausentes zero, unicidade por município/CNAE e vínculos explícitos Receita–IBGE. Mantém zeros iniciais e a exceção restrita Parati/Paraty; todos os quatro CNAEs precisam existir no catálogo local.

Não se exige que existam 368 linhas: o GROUP BY pode omitir combinações sem registros. Ausência de linha permanece indisponível; não se criam zeros. CNAE inteiro sem grupos aparece como indisponível no resumo. Um zero explicitamente presente pode ser validado como valor, mas não comprova ausência real de concorrência e não é produzido por esse GROUP BY sem registros. Nomes diferentes para o mesmo par impedem aceitação, em vez de mesclar silenciosamente.

O resumo mostra quantos municípios possuem grupos em cada CNAE e a soma dos registros disponíveis; não declara cobertura real ou unicidade global de CNPJs. Aceitar grupos observados não certifica a completude da exportação. Essa completude deve ser conferida no resultado/job antes de registrar a fotografia para gravação.

O SHA-256 normalizado para LF identifica o conteúdo validado, não autentica a fonte. A simulação pode concluir com metadados pendentes; nesse estado, o comando avisa que a gravação está bloqueada.

## 3. Registrar a fotografia somente após revisão

O arquivo [contagens_piloto.php](../../Right_Point/config/contagens_piloto.php) começa com `coletas` vazio. Após conferir arquivo, filtros, completude e metadados do job, adicionar uma entrada cujo índice seja o hash LF conferido e cujo valor contenha **exatamente**:

| Campo | Conteúdo |
| --- | --- |
| `consultado_em_utc` | Instante real da consulta, convertido para UTC, no formato `YYYY-MM-DD HH:MM:SS` |
| `periodo_referencia` | Referência comprovada, até 20 caracteres, ou `null` |
| `versao_fonte` | Versão comprovada, até 100 caracteres, ou `null` |

Não preencher com o horário da importação, nome do arquivo ou data antiga. Um arquivo diferente exige nova conferência e novo registro; o hash antigo não autoriza o conteúdo novo. A configuração funciona como registro de conteúdos conferidos, não como prova criptográfica da origem.

Se já houver cache de configuração ativo, reconstruí-lo após atualizar o registro e confirmar novamente a conexão local. Não copiar cache entre computadores nem criar cache apenas por obrigação.

## 4. Gravação local explícita

Depois da simulação e da decisão de carregar o banco local correto:

```powershell
php artisan cnpj:importar-contagens-piloto "storage/app/amostras/contagens-perfis-piloto.json" --gravar
```

- Sem fotografia registrada, a opção falha antes de criar fonte/coletas.
- Fonte e coletas são gravadas na mesma transação, com bloqueio da fonte e até três tentativas para deadlock. O índice único existente continua sendo proteção adicional.
- O gravador compara os pares exatos de município e atividade antes de inserir. Mesmas contagens preservam registros, timestamps e metadados; divergência aborta sem sobrescrever. Uma falha no meio desfaz todo o lote.
- Coletas de outro instante são preservadas. Pares ausentes no arquivo não são apagados ou preenchidos; coletas antigas desses pares continuam sendo lidas pelo comportamento atual da consulta, com suas datas próprias. Não apresentar todos os resultados como uma fotografia simultânea sem conferir o instante de cada recorte.
- Uma reimportação não atualiza metadados existentes, mesmo que o registro de configuração seja enriquecido depois. Correção/enriquecimento persistente exigirá procedimento próprio, não alteração silenciosa por reimportação.
- A contagem permanece por CNAE principal exato, com critério `ativos_cnae_principal_exato_v1`. Não se introduziu equivalência comercial, fusão de padarias ou certeza de cobertura.

## Validação da implementação

- Suíte completa: **126 testes aprovados, 701 asserções**. Os casos novos usam dados fictícios e SQLite na memória; cobrem quatro CNAEs, conjunto completo/esparso, ausência sem zero, integridade dos pares, metadados/hash/UTC/CRLF, reimportação, preservação do piloto, divergência e rollback. Os dois casos SQL validam filtros e controles com COUNTIF compatível.
- Sintaxe PHP e Pint aprovados. Sem dependência nova, build frontend ou mudança visual.
- Simulações do comando antigo e do novo usando o lote real de restaurantes retornaram sucesso, preservando o JSON e os registros completos das cinco tabelas do domínio. MySQL permaneceu com 93 coletas. O novo resumo mostrou os outros três CNAEs indisponíveis e gravação bloqueada.
- Simulação adicional do novo JSON de quatro CNAEs aprovada: 368 pares, arquivo e cinco tabelas preservados. Escrita do novo fluxo no MySQL, concorrência real e conferência dos metadados/custo do novo job continuam pendentes. Nenhum `--gravar`, migration ou consulta BigQuery foi executado pelo agente.

## Próximo passo

Conferir os metadados do job já executado e registrar a fotografia na configuração conforme seção 3. Depois simular novamente e decidir a carga explícita no banco correto. O código pronto e o JSON validado não significam dados dos novos CNAEs já carregados. Renda municipal, indicadores derivados e score continuam etapas posteriores.
