# Contas, histórico, contextos e H3 — operação da v1

Implementado em 05/11/2026. Consulte o [STATUS](../STATUS.md) para a evidência de carga e os limites de validação. Execute os comandos dentro de `Right_Point/`.

## Preparação em outro computador

1. Preserve alterações locais e confira branch/status antes do pull. Não sobrescreva `.env`.
2. Confira PHP/dependências e conexão local. Use `composer install`, não `update`, quando `vendor/` estiver ausente.
3. Execute `php artisan migrate:status`. As migrations `2026_11_05_180000` e `180100` apenas acrescentam tabelas; confira `php artisan migrate --pretend` antes de `php artisan migrate`. Nunca use `migrate:fresh` para preparar este projeto.
4. Confira os catálogos e as cargas de população, renda e CNPJ existentes. Não repita BigQuery nem invente os dados ausentes. Os procedimentos anteriores continuam válidos.
5. Somente se faltarem os novos contextos, simule e depois autorize a gravação local conforme abaixo. Não é necessário alterar os pesos ou reimportar CNPJ.

Git transporta código, migrations, contratos, testes e documentação. Não transporta banco, contas, perfis, diagnósticos, JSON ignorados, caches, dependências instaladas ou perfis do navegador. Contas são do mesmo servidor/banco: criar uma conta no trabalho não a cria no servidor local da escola. Sincronização entre bancos não faz parte desta entrega.

## Fluxo de uso

- Explorar é público. Buscar município ou clicar no mapa sincroniza a seleção; buscar atividade permite escolher e preencher o CNAE. A entrada manual de sete números continua disponível. O botão só libera com município e CNAE válidos; disponibilidade dos dados é conferida no servidor.
- Recolher mantém resumo, seleção e ação visíveis; Limpar não apaga perfis nem histórico.
- Criar conta/Entrar protege perfis e diagnósticos por proprietário. Senha com pelo menos dez caracteres, letras/números e até 72 bytes. Não há confirmação de e-mail ou recuperação de senha nesta versão acadêmica.
- Visitantes continuam com os perfis locais antigos. Após entrar, **Copiar perfis deste navegador** é uma ação explícita, aceita até 100 e preserva os originais. A origem local impede cópia duplicada e não sobrescreve a edição na conta. Se houver erro, a mensagem informa a interrupção; repetir não duplica origens já copiadas.
- **Salvar diagnóstico** recalcula no servidor no instante do salvamento, em transação. O resultado pode diferir da consulta anterior se os dados tiverem mudado. Não se aceita score enviado pelo cliente.
- Histórico → Reabrir mostra a fotografia, sem nova consulta aos fatores; camadas atuais ficam ocultas. Relatório / PDF abre a versão imprimível. O PDF usa **Imprimir → Salvar como PDF** do navegador; não existe geração automática no servidor. JSON exporta a fotografia completa, inclusive as 92 entradas/fontes quando o conjunto estava apto. Diagnóstico parcial pode ser salvo com nota indisponível e seus motivos.
- O hash corresponde ao objeto `snapshot` serializado pela aplicação com `json_encode(..., JSON_UNESCAPED_UNICODE)`, não ao arquivo JSON completo/indentado de download. Identifica integridade do conteúdo; não prova autenticidade, eficácia ou cobertura das fontes. Impressão não substitui o JSON para reprodução.

## IBGE: novos indicadores, sem trocar o score

```powershell
php artisan ibge:importar-contexto-rj estimativa --ano=2026
php artisan ibge:importar-contexto-rj idades
# Após conferir a simulação:
php artisan ibge:importar-contexto-rj estimativa --ano=2026 --gravar
php artisan ibge:importar-contexto-rj idades --gravar
```

- Estimativa: tabela **6579**, variável **9324**, Pessoas, N6/RJ, 92 códigos e ano publicado. A interface atual lê 2026; outro ano pode ser importado, mas não será apresentado automaticamente como 2026.
- Idades: tabela **9514**, variável **93**, Censo 2022. Totais de sexo e forma de declaração; grupos quinquenais não sobrepostos somados em **0–14**, **15–64** e **65+**. As três faixas devem somar o total de cada município. Não são clientes potenciais observados nem público-alvo definido automaticamente para uma atividade.
- Metadados, categorias, unidades, códigos, período, duplicatas e valores são validados antes de qualquer escrita. O símbolo `-` é normalizado para zero absoluto **somente nas contagens etárias**, conforme [legenda oficial do IBGE](https://www.ibge.gov.br/explica/producao-agropecuaria/soja/). `...`, `X`, ausência e outros símbolos não viram zero; bloqueiam o lote. A soma municipal continua obrigatória.
- `--gravar` usa transação e upsert somente nos indicadores destas fontes/períodos. Reimportar não duplica, mas atualiza valor/data de coleta; não é um arquivo histórico imutável. População Censo, renda e CNPJ existentes são preservados. Uma nova análise salva guarda a fotografia então disponível.

Metadados oficiais: [6579](https://servicodados.ibge.gov.br/api/v3/agregados/6579/metadados), [9514](https://servicodados.ibge.gov.br/api/v3/agregados/9514/metadados).

### Cenário populacional separado

```powershell
php artisan diagnostico:comparar-populacoes 5611201
php artisan diagnostico:comparar-populacoes 5611201 --json
```

Somente leitura, requer as 92 estimativas compatíveis e o conjunto piloto completo. Compara denominador Censo 2022 versus estimativa 2026, mantendo renda, contagens e pesos 50/50. Posições usam notas sem arredondamento visual e empates médios. Deslocamento positivo significa posição numérica maior, não melhora. JSON emitido contém entradas, resultados e hash; o comando não cria arquivos nem muda a consulta padrão. Pode repetir para os outros três CNAEs. Mudança de posição não prova que uma referência é comercialmente superior.

## OSM: piloto manual limitado

```powershell
php artisan osm:importar-contexto 3302700
# Somente depois de conferir um retorno válido:
php artisan osm:importar-contexto 3302700 --gravar
```

Consulta pontual ao [Overpass](https://wiki.openstreetmap.org/wiki/Overpass_API), com timeout, limite de tamanho, até 3.000 elementos e cache local de 24 horas. Não executar em massa ou em paralelo. Simulação pode preencher cache, mas não grava dados de domínio. Limite municipal simplificado vem do IBGE; pontos fora dele são descartados. Escolas, hospitais/clínicas e estações/plataformas/paradas cadastradas são contextos distintos de concorrentes. Áreas usam centro representativo, não endereço ou entrada geocodificada. Equipamentos podem ter mais de uma representação OSM; não interpretar número de objetos como número exato de equipamentos únicos.

Resposta parcial (`remark`), erro ou recorte excessivo falha sem camada vazia fictícia. Na execução real desta entrega, a consulta não passou na validação de disponibilidade/completude; **nenhum OSM foi gravado**. Cobertura, funcionamento, atualização e fluxo não são certificados. Atribuição OSM deve acompanhar qualquer uso/redistribuição; termos da fonte continuam aplicáveis.

## Trabalho, setores e estabelecimentos: contrato de arquivo normalizado

```powershell
php artisan contexto:importar storage/app/amostras/contexto.json
php artisan contexto:importar storage/app/amostras/contexto.json --gravar
```

Não é um leitor genérico de ZIP/CSV/RAIS/microdados/KML/KMZ. A transformação da base oficial para o contrato abaixo e a conferência do recorte continuam necessárias. Não foram adquiridos dados reais dessas três famílias nesta entrega. Não baixar bases nacionais extensas para esta etapa.

Envelope comum, JSON até 10 MiB:

```json
{
  "tipo": "trabalho-caged",
  "codigo_ibge": "3302700",
  "fonte_url": "https://www.gov.br/trabalho-e-emprego/",
  "periodo_referencia": "2026-09",
  "versao_fonte": "SUBSTITUIR pela identificacao da publicacao e do recorte",
  "coletado_em": "2026-11-05T12:00:00Z",
  "dados": {
    "indicadores": [
      {"codigo": "admissoes", "valor": 100, "unidade": "Vínculos"},
      {"codigo": "desligamentos", "valor": 80, "unidade": "Vínculos"}
    ]
  }
}
```

**Exemplo sintético, não representa Maricá e não deve ser importado no banco real.** Os valores devem ser substituídos por agregado municipal conferido, não por totais estaduais, saldo ou amostra incompleta. URL permitida identifica a origem declarada, não certifica a procedência de um arquivo local.

| Tipo | `dados` e regras específicas |
| --- | --- |
| `trabalho-caged` | Exatamente `admissoes` e `desligamentos`, unidade `Vínculos`, inteiros não negativos; período `YYYY-MM`. Sem inferir emprego informal, clientes ou demanda. |
| `trabalho-rais` | Exatamente `vinculos_ativos` (Vínculos, inteiro) e `remuneracao_media` (Reais), não negativos; ano `YYYY`. Média e cobertura devem corresponder ao mesmo recorte oficial. |
| `setores-ibge` | Período `2022`; `features` GeoJSON, entre 1 e 3.000, Polygon/MultiPolygon fechado. `properties.codigo_setor`: 15 dígitos, prefixo municipal; `properties.populacao`: inteiro ou `null` se ausente/suprimido. Não colocar `X`/zero fictício. Não faz rateio populacional para H3. |
| `estabelecimentos` | `geometria_municipio` GeoJSON e `pontos` até 3.000; cada ponto tem `id` público/opaco único, `latitude`, `longitude`, `tipo: estabelecimento`, `cnae` de sete dígitos e `geocodificacao: validada`. Coordenadas devem estar no polígono declarado. Campos extras de nome/documento/contatos são descartados; o operador não deve colocar dados pessoais no `id`. A validação de endereço/coordenada é responsabilidade da preparação, não um geocodificador implementado. |
| `osm` | Mesmo envelope de pontos, tipos `escola`, `saude`, `transporte`; preferir o importador específico. |

Hosts HTTPS aceitos são restritos em `ValidadorContextoTerritorial`; o importador de arquivo não acessa a URL declarada. Geometria bem formada e ponto dentro do polígono **não comprovam** que o polígono representa o município ou que o estabelecimento existe. A preparação deve confrontar a malha oficial e documentar método/qualidade. Arquivos sem essa conferência não devem receber `validada`.

Gravação preserva lotes por hash. O mesmo conteúdo normalizado não duplica; nova coleta vira novo lote. A leitura usa a coleta mais recente por tipo/município (ID desempata), não um arquivo antigo importado depois. Setores suprimidos permanecem NULL. Emprego/POIs/setores ficam fora do score. Não há agendador ou atualização automática.

## H3 no mapa

- [h3-js](https://github.com/uber/h3-js) 4.3.0 fixado com SRI. Resoluções 6/7/8, limite de 3.000 células por município; resolução excessiva é bloqueada. CDN/rede indisponível informa falha, sem bloquear a consulta por formulário.
- Grade pela malha simplificada IBGE, inclusão pelo centro da célula. Células não são recortadas na divisa; podem extrapolá-la. Pontos de borda podem cair fora da grade e essa quantidade é informada.
- Pontos CNPJ importados são filtrados pelo CNAE selecionado; POIs permanecem contexto. Contagem é de pontos do lote, não de concorrentes reais completos. Camadas são consultadas sob demanda; consulta principal não transfere geometrias completas. Snapshots preservam as camadas disponíveis no salvamento, mas a reabertura não mistura camadas atuais com dados antigos.
- **Sem população, renda ou score por célula.** Não há inferência de facções/criminalidade, roteamento de risco, alocação uniforme ou decisão automatizada sobre pessoas/bairros. Setores/endereços reais e uma metodologia espacial própria são pré-requisitos para aprofundar esse diagnóstico.

## Validação e publicação

Testes de contratos/HTTP falso/SQLite não provam cobertura externa nem experiência visual. Na entrega: HTTP real e MySQL transacional foram conferidos; navegador automatizado indisponível. Conferir manualmente desktop/mobile, teclado, mapa ↔ lista, busca/CNAE, recolher/limpar, criação de conta, perfis, histórico, impressão e H3 antes da apresentação. Não há build Vite novo: esta tela usa arquivos públicos diretos; os quatro scripts foram conferidos com `node --check`.

Antes de publicar fora do ambiente local: revisar secrets historicamente rastreados, HTTPS/cookies seguros, modo produção, backups/retensão/privacidade, limites de armazenamento e recuperação de senha/e-mail. Esta implementação não equivale a implantação segura de produção.
