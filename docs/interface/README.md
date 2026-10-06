# Interface

Esta pasta reunirá diretrizes visuais, fluxos de navegação, estados de tela e decisões de experiência do usuário quando a interface for modelada.

A [matriz e o fluxograma](../arquitetura/matriz-dados-diagnostico.md) definem a direção inicial: 92 municípios do RJ, escolhidos por busca ou clique no mapa, sempre pelo mesmo código IBGE. O mapa e o CRUD de perfil do negócio **ainda não foram implementados**.

## Fluxo mínimo previsto

1. selecionar a atividade econômica/CNAE;
2. escolher um município do RJ por busca ou no mapa;
3. solicitar a análise;
4. visualizar indicadores, concorrentes, fontes e datas; mostrar score orientativo e justificativa somente quando dados mínimos e fórmula estiverem validados;
5. futuramente, criar/consultar/editar/excluir perfil do negócio com atividade, município e preferências, após definir autenticação e propriedade dos dados;
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

Diretrizes visuais e protótipos ainda estão `A CONFIRMAR`.
