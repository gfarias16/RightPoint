# Interface

Esta pasta reúne o fluxo previsto e as decisões iniciais da demonstração visual. A validação final da interface no navegador ainda está pendente.

A [matriz e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) definem a direção inicial: 92 municípios do RJ, escolhidos por busca ou clique no mapa, sempre pelo mesmo código IBGE. Em 14/10/2026 foram implementados um protótipo de mapa e um CRUD de perfis **local ao navegador**, ainda sem sincronização entre máquinas.

## Decisão visual do mapa de demonstração

- A interface do RightPoint usa fundo preto/grafite e texto branco como direção visual escolhida pelo usuário em 14/10/2026. Verde claro indica seleção e ações; não substitui texto ou rótulos. O modo claro é uma opção **do mapa**, não da página inteira.
- Referência enviada pelo usuário: mapa escuro como padrão, com alternativas clara e satélite. A implementação não reproduz os marcadores das capturas, pois ainda não há estabelecimentos geocodificados no sistema.
- A base escura é a base padrão do OpenStreetMap com transformação visual no navegador; a clara usa os mesmos mosaicos sem filtro. A visão satélite usa o serviço World Imagery da Esri. O mapa usa Leaflet por CDN. Essas bases requerem rede e atribuição visível; não são parte da análise nem do score.
- Os polígonos municipais vêm da malha simplificada do IBGE, validados por código contra o catálogo local antes de entrar no cache. Busca/lista são a alternativa acessível quando a malha não carrega. Com acesso à rede, a rota local retornou os 92 polígonos e a aparência escura foi conferida em captura. Após o retorno do usuário, o mapa foi ampliado e o zoom pela roda do mouse habilitado; mover o cursor para fora do mapa permite rolar a página. Seleção por clique, troca de base, zoom pela roda e responsividade ainda exigem teste por interação.
- O código CNAE de sete dígitos identifica a atividade internamente. A digitação direta é temporária porque `atividades_economicas` ainda está vazia. A próxima melhoria de usabilidade é carregar o catálogo oficial e permitir busca pelo nome da atividade, sem exigir que o usuário saiba o código.

## Fluxo mínimo previsto

1. selecionar a atividade econômica/CNAE;
2. escolher um município do RJ por busca ou no mapa;
3. solicitar a análise;
4. visualizar indicadores, concorrentes, fontes e datas; mostrar score orientativo e justificativa somente quando dados mínimos e fórmula estiverem validados;
5. no protótipo, criar/consultar/editar/excluir perfis com nome, município, CNAE e observações no armazenamento local do navegador; definir autenticação e propriedade antes de migrar esse CRUD ao banco;
6. consultar histórico, caso faça parte do escopo final.

Bairro, ponto e raio geográfico ficam para outra etapa. O perfil do negócio servirá para personalizar consultas e apoiar ajustes **manuais** dos critérios pelo grupo; não haverá treinamento automático nesta fase. Campos adicionais e necessidade de login estão `A CONFIRMAR`.

## Estados que a interface deverá representar

- entrada incompleta ou inválida;
- análise em processamento;
- resultado disponível;
- dados insuficientes;
- score indisponível por fórmula ainda não aprovada;
- fonte indisponível;
- fonte defasada, com data visível;
- ausência de concorrentes confirmada versus cobertura de CNPJ desconhecida;
- histórico vazio.

Estados já presentes no protótipo: entrada inválida, consulta em processamento, resultado com dados insuficientes, score indisponível, malha indisponível e lista vazia de perfis. Fonte defasada, concorrentes com cobertura CNPJ validada, histórico e verificação de responsividade ainda estão pendentes.
