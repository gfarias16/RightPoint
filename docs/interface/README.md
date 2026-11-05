# Interface

Esta pasta reúne o fluxo previsto e as decisões iniciais da demonstração visual. A validação final da interface no navegador ainda está pendente.

## Contrato visual e estados da v1 — 05/11/2026

Referências fornecidas pelo usuário: **03 barra recolhível + busca de atividade da 02**. Preservados grafite, verde-menta, destaque municipal amarelo, tipografia/sistema existentes, mapa Leaflet e limites reais IBGE. Não substituir o mapa por uma imagem nem remover campos obrigatórios, origem dos dados ou motivos de indisponibilidade.

- Uma faixa de consulta, mapa amplo abaixo e leitura compacta antes dos detalhes. Recolher mantém resumo e ação; limpar preserva perfis/histórico. Campo manual CNAE permanece como alternativa de rede/teclado.
- Município selecionável no mapa e em lista nativa; atividade buscável com debounce/abort e lista nativa acessível. CNAE preenchido automaticamente após escolher, editável e validado como sete dígitos. Não criar combobox ARIA incompleto apenas para imitar a referência.
- Botão desabilitado enquanto faltam campos (requisito do usuário) ou existe requisição. Estado textual informa o motivo. Mudança de seleção durante uma requisição não publica resultado da seleção antiga; atualização limpa a opção de salvar o resultado antigo.
- Visitante mantém perfis no navegador; conta mostra registros privados/paginados e cópia local explícita. Histórico aberto é identificado como fotografia, não mistura camadas atuais e não permite salvar fingindo consulta nova.
- Estados: carregando/sem catálogo/sem resultado/erro de rede, dados parciais/sem nota, sucesso experimental, sessão expirada, limite de requisições, nenhuma camada importada, H3 indisponível e resolução excessiva. Ausência nunca significa zero real.
- Rótulos/controles semânticos, foco visível, live regions, confirmação para exclusão, CSS responsivo 1 coluna em telas menores e movimento reduzido preservados. Texto longo/hash quebra; esquema escuro também nos controles nativos.
- Contextos e limites ficam expansíveis, fora do score. Relatório tem impressão clara, fontes/anos/versões e JSON. PDF depende da impressão do navegador.

**Revisão aplicada:** UI/UX e acessibilidade (`ui-ux-pro-max`, `web-design-guidelines`) e contrato/gate `anti-ui-slop`, usando referências do usuário e identidade existente; sem catálogo visual pago ou nova biblioteca de componentes. Sintaxe/HTML renderizado/HTTP e testes não substituem observação real. **Gate visual pendente:** acesso ao navegador indisponível e Edge sem aprovação concluída. Não há captura nova aprovada, teste de overflow em 375 px ou fluxo H3/conta/print observado nesta entrega. Conferir antes da apresentação. Validações antigas abaixo referem-se ao layout antigo.

## Diagnóstico e score experimental — 04/11/2026

**Refinamento após teste do usuário:** leitura principal reduzida a quatro indicadores, nota, fatores e referências curtas. Fórmula, procedência e ressalvas em três `details/summary`: “Como a nota é calculada”, “Fontes e referências dos dados”, “Limites desta análise”. Fechados inicialmente, com foco visível e abertura/fechamento por teclado. A nota usa card neutro/destaque existente; card de alerta fica para indisponibilidade. “Em avaliação” identifica o estágio sem linguagem interna; a referência CNPJ adotada/competência desconhecida permanece nos detalhes e na API. Não apresentar 2026 como competência confirmada.

Navegador real: Maricá/restaurantes manteve 50,0, renda/população/contagem/razão. Detalhes abriram e fecharam com Enter, e fontes/recorte foram conferidos; viewport 375 px com fontes expandidas sem overflow horizontal medido. Nenhuma interação de perfis foi alterada. Revisão usa princípios gerais de hierarquia/acessibilidade da UI/UX Pro Max; buscas não localizaram exemplo específico aplicável ao componente.

- Mantida a identidade escura e os componentes de fatos/avisos existentes. Nota/fatores ficam em destaque; explicação, método, conjunto e procedência podem ser expandidos. Usa texto, não só cor. Sem categorias de oportunidade ou porcentagem de sucesso.
- Estado indisponível mostra motivo, com score NULL; ausência não vira zero. Atividade fora dos quatro CNAEs não recebe nota. Mensagens específicas preservam padarias separadas e alcance amplo de `9602501`.
- Navegador Apache com banco real: Maricá/restaurantes 50,0; Barra Mansa/restaurantes 59,3; Barra Mansa/9602501 35,7, com aviso do CNAE amplo. Resultado em viewport 375 px sem overflow horizontal medido. Isso não valida todos os municípios, mapa ou CRUD por interação.
- Referência funcional e de apresentação: [decisão 001](../decisoes-arquiteturais/001-score-piloto-rj.md). Regras comerciais e pesos ainda precisam de revisão/validação; renda/população continuam 2022.

A [matriz e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) definem a direção inicial: 92 municípios do RJ, escolhidos por busca ou clique no mapa, sempre pelo mesmo código IBGE. Em 14/10/2026 foram implementados um protótipo de mapa e um CRUD de perfis **local ao navegador**, ainda sem sincronização entre máquinas.

## Decisão visual do mapa de demonstração

- A interface do RightPoint usa fundo preto/grafite e texto branco como direção visual escolhida pelo usuário em 14/10/2026. Verde claro indica seleção e ações; não substitui texto ou rótulos. O modo claro é uma opção **do mapa**, não da página inteira.
- Referência enviada pelo usuário: mapa escuro como padrão, com alternativas clara e satélite. A implementação não reproduz os marcadores das capturas, pois ainda não há estabelecimentos geocodificados no sistema.
- A base escura é a base padrão do OpenStreetMap com transformação visual no navegador; a clara usa os mesmos mosaicos sem filtro. A visão satélite usa o serviço World Imagery da Esri. O mapa usa Leaflet por CDN. Essas bases requerem rede e atribuição visível; não são parte da análise nem do score.
- Os polígonos municipais vêm da malha simplificada do IBGE, validados por código contra o catálogo local antes de entrar no cache. Busca/lista são a alternativa acessível quando a malha não carrega. Com acesso à rede, a rota local retornou os 92 polígonos e a aparência escura foi conferida em captura. Após o retorno do usuário, o mapa foi ampliado e o zoom pela roda do mouse habilitado; mover o cursor para fora do mapa permite rolar a página. Seleção por clique, troca de base, zoom pela roda e responsividade ainda exigem teste por interação.
- O código CNAE de sete dígitos identifica a atividade internamente. O catálogo de 1.332 CNAEs já foi importado na escola e no notebook do trabalho. A busca por nome/código está ligada ao formulário, com digitação direta como alternativa; capturas do usuário em 20/10/2026 confirmam seleção de atividade, consulta de Maricá e um perfil salvo. Edição/exclusão, troca de bases e responsividade ainda precisam de teste.

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

## Contagem piloto na consulta — 28/10/2026

- Interface existente preservada: fatos no card de resultado, com rótulo de estabelecimentos na base, critério ativo/CNAE principal exato, fonte, consulta UTC e período/versão. Sem coleta, mostrar indisponibilidade; referência ausente não é preenchida com a data da consulta. Texto continua inserido com `textContent`, não HTML da fonte.
- Avisos mantêm ausência de score e limites de cobertura/período; não representar 620 como chance de sucesso ou como estabelecimentos geolocalizados no mapa. UI/UX aplicada para clareza do estado vazio, sem redesenho ou dependências novas.
- Consulta HTTP real e testes automatizados aprovados; renderização visual/responsividade ainda a conferir. A ferramenta do navegador negou acesso a localhost e o bloqueio não foi contornado. Não registrar esta etapa como teste visual concluído.
- Captura posterior enviada pelo usuário confirmou o resultado de Maricá/5611201 no desktop: população 197.277/2022, 620 estabelecimentos na base, fontes/datas, referências desconhecidas e quatro avisos legíveis. Esta evidência cobre o resultado exibido, não mobile, recortes sem coleta ou todas as interações.

## Indicador exploratório e validação no Apache — 04/11/2026

- Resultado agora inclui oferta registrada por 10 mil habitantes, calculada no backend e formatada com duas casas em pt-BR. Ausência/valor inválido mostra indisponibilidade com motivo; zero registrado permanece zero. Nota textual explica fórmula, arredondamento e limites, junto das referências das fontes. Não há escala de cores de potencial ou score.
- URLs das três consultas geradas completas pelo Laravel preservam subpastas do XAMPP; antes a raiz relativa causava 404. JS/CSS com versão pela data do arquivo evitam que o navegador continue executando assets antigos. Sem mudança de configuração do Apache.
- Navegador real aprovou Maricá/5611201 = 31,43, Barra Mansa/5611201 = 18,13 e 7490104 sem coleta com motivo. Os 92 limites carregaram após correção da URL. Resultado acessível em viewport de 375 × 812, sem overflow horizontal medido. Cobertura restrita aos fluxos descritos; mapa completo/CRUD e demais dispositivos seguem com validação própria. Skill UI/UX Pro Max apoiou feedback/estados sem dados; identidade existente preservada.
