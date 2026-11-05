# Rendimento domiciliar per capita municipal — RJ

Atualizado em 04/11/2026. Fonte e cobertura consultadas; importador/API/interface preparados e testados. **Nenhuma renda gravada no MySQL pelo agente.** A carga explícita fica com o usuário no banco local correto.

## Fonte e significado

- [Tabela SIDRA 10295](https://sidra.ibge.gov.br/tabela/10295), pesquisa Censo Demográfico 2022, divulgação de Trabalho e rendimento baseada na amostra. [Metadados oficiais](https://servicodados.ibge.gov.br/api/v3/agregados/10295/metadados) consultados com HTTP 200 pelo Laravel e TLS verificado em 04/11.
- Variável **13431**: média do rendimento nominal mensal domiciliar por pessoa. Unidade da API: `Reais`; referência de rendimento: julho de 2022. Não é mediana (13534), salário individual, PIB per capita ou rendimento estadual da PNAD.
- Recorte municipal N6 dos municípios da UF 33. Totais explícitos de sexo `2[6794]`, cor ou raça `86[95251]` e grupo de idade `58[95253]`. Não adicionar médias de subgrupos para produzir o total.
- O universo do indicador exclui moradores classificados como pensionistas, empregados domésticos e parentes de empregados domésticos. É distinto do total de população residente. Fonte oficial de apoio: [apresentação Trabalho e rendimento](https://agenciadenoticias.ibge.gov.br/media/com_mediaibge/arquivos/53fde9c24f6d8df3108c83f6378e018f.pdf), que identifica resultados preliminares da amostra; o código da tabela não é um identificador de revisão editorial.
- Valores nominais de 2022 não equivalem ao poder de compra atual ou ao gasto destinado à atividade. Médias podem esconder desigualdades e não descrevem bairros, fluxo de consumidores ou públicos específicos.

Consulta de valores, derivada dos metadados conferidos:

```text
https://servicodados.ibge.gov.br/api/v3/agregados/10295/periodos/2022/variaveis/13431?localidades=N6%5BN3%5B33%5D%5D&classificacao=2%5B6794%5D%7C86%5B95251%5D%7C58%5B95253%5D
```

Leitura real retornou uma variável/resultado e **92 municípios únicos**, iguais ao catálogo local, sem valor ausente ou não numérico. Exemplos: Barra Mansa `1407.80`, Maricá `1707.99` e Niterói `3577.32` reais/mês/pessoa. As páginas SIDRA/portal retornaram 403 no navegador da ferramenta de pesquisa; a API oficial respondeu 200 via cliente Laravel, sem desativar TLS. Não foi usada fonte secundária para preencher dados faltantes.

## Simular

Dentro de `Right_Point/`:

```powershell
php artisan ibge:importar-renda-rj
```

O comando faz duas leituras públicas: metadados e valores. Não usa BigQuery, chave privada ou JSON manual. `IbgeHttp` usa o pacote CA local quando existente. Conferir conexão/catalogo/migrations da própria máquina antes de carregar; esta entrega não acrescenta migration.

Valida tabela/pesquisa/período/nível municipal, variável média nominal/nome/unidade e o resultado único dos três totais. Exige 92 códigos RJ distintos, nível N6 e correspondência exata ao catálogo. Cada série contém somente 2022 e decimal textual não negativo com até 14 casas inteiras e quatro decimais, compatível com o banco. Não converte símbolos de ausência, vírgulas, números JSON ou negativos em zero. Zero textual explícito é aceito. Falha impede toda a carga.

Simulação real após implementação: saída 0, 92 valores, exemplos acima; comparação integral dos registros das cinco tabelas confirmou preservação. Banco permaneceu com 92 populações, **zero rendas** e 461 coletas CNPJ. Não é evidência de escrita real do novo comando.

## Gravar explicitamente

Após conferir a simulação e o banco desejado:

```powershell
php artisan ibge:importar-renda-rj --gravar
```

Esperado neste banco: `Importação concluída: 92 rendimentos municipais processados.` Indicadores passam de 92 para **184** (92 populações + 92 rendas), mantendo 461 coletas CNPJ. Isso depende do estado local; conferir antes em outra máquina.

- Fonte: `ibge-censo-2022-renda-domiciliar`.
- Indicador: `rendimento_domiciliar_per_capita_medio`; unidade `Reais`; período `2022`.
- `versao_fonte`: `Censo 2022 - tabela 10295 - variavel 13431 - totais`. Identifica o recorte, **não** uma revisão oficial comprovada. `coletado_em` é a consulta local UTC, não julho de 2022.
- Transação inclui fonte/92 indicadores, bloqueio da fonte e três tentativas para deadlock. Falha desfaz os registros da transação; erro do banco não expõe credenciais no console.
- `upsert` atualiza a mesma chave município/fonte/indicador/período, preservando IDs/criação, população e outras fontes/períodos. Reimportação pode atualizar valores e data da coleta, não é uma fotografia imutável. Não usá-la para reescrever análises históricas futuras; tais análises precisarão de snapshot próprio.

## Consulta, interface e validação

**Atualização posterior à preparação — 04/11:** leitura local confirmou 92 rendas e 184 indicadores, fonte `ibge-censo-2022-renda-domiciliar`, Reais, 2022 e identificação de recorte uniformes; coleta `2026-11-04 18:05:59` UTC. Navegador mostrou valores reais de Maricá/Barra Mansa. A carga não foi repetida pelo agente. A renda agora entra no [score experimental v1](../decisoes-arquiteturais/001-score-piloto-rj.md); a indicação de ausência/pendência nos registros de preparação abaixo é histórica.

- Consulta demonstrativa acrescenta `renda`, objeto com valor, unidade, período, fonte/código/URL, coleta e identificação do recorte; fica NULL sem carga. Lê somente este indicador/fonte/período/unidade no município escolhido. Não faz chamada externa durante a consulta do usuário.
- Tela mostra média mensal por pessoa, reais de 2022, fonte/coleta, identificação da tabela e limites da amostra; sem renda, informa que ainda não foi importada. Não usa o valor no score e não muda o indicador por 10 mil habitantes.
- **179 testes/954 asserções aprovados** na suíte completa; 32 casos novos com HTTP falso e SQLite na memória cobrem contrato, recortes, ausência/zero, catalogo, reimportação, preservação, falhas e rollback. Não comprovam concorrência ou escrita real no MySQL. Sintaxe/Pint/JS aprovados.
- Navegador real confirmou renda ausente e oferta 31,43 em Maricá. Apresentação de renda preenchida foi exercitada com **resposta HTTP simulada apenas no navegador**, sem gravar no banco, incluindo formato monetário e texto em largura 375 px sem overflow medido. Interceptação removida e resposta real ausente restaurada. Não registrar isso como renda importada.

## Próximo passo

Usuário executar carga explícita e compartilhar a saída. Conferir por leitura os 92 valores contra a resposta oficial, procedência, população e CNPJ preservados; depois conferir Maricá e Barra Mansa na interface. Em seguida revisar regras dos perfis e compatibilidade temporal antes de qualquer índice experimental. CNPJ continua com período/versão desconhecidos; compartilhar ano 2022 entre renda e população não torna a fotografia CNPJ contemporânea.
