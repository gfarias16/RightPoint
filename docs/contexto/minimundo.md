# Minimundo - RightPoint

## Identificação

- Sistema: RightPoint
- Grupo: Gabriel Farias, Larissa e Diego
- Fase: modelagem inicial
- Origem: versão estruturada do documento `RightPoint - MINIMUNDO.docx`

## Contexto

O RightPoint é uma plataforma de apoio a empreendedores que desejam avaliar o potencial comercial de uma atividade econômica em determinada região. O sistema reúne dados públicos sobre o local e sobre empresas existentes para ajudar o usuário a compreender a demanda provável e o nível de concorrência.

O resultado serve como apoio à decisão. Ele não garante o sucesso ou o fracasso do empreendimento.

## Objetivo do sistema

Permitir que um empreendedor selecione um tipo de negócio e uma região ou localização para obter:

- um score de potencial comercial entre 0 e 100;
- a identificação dos concorrentes considerados;
- os indicadores utilizados na avaliação;
- uma justificativa compreensível para o resultado.

## Participantes

### Empreendedor

Pessoa interessada em abrir um negócio e consultar o potencial de uma atividade econômica em um local.

### Fontes públicas de dados

Fornecem dados socioeconômicos, geográficos e cadastrais usados na análise. Podem incluir bases de CNPJ, CNAE, população, renda, faixa etária, densidade demográfica e clima.

A CONFIRMAR: quais fontes serão adotadas oficialmente na modelagem.

## Informações mantidas

- atividades econômicas relacionadas a CNAEs ou categorias equivalentes;
- regiões geográficas, como bairros e municípios;
- localizações representadas por coordenadas ou endereço georreferenciado;
- indicadores regionais e sua data de referência;
- estabelecimentos existentes, sua atividade, situação cadastral e localização;
- análises realizadas, com score, fatores, justificativa e dados usados no cálculo.

## Operações principais

1. O usuário informa a atividade econômica que deseja analisar.
2. O usuário escolhe uma região ou uma localização e, quando aplicável, um raio.
3. O sistema obtém os indicadores correspondentes à área analisada.
4. O sistema identifica estabelecimentos concorrentes na região ou no raio.
5. O sistema cruza atividade, indicadores regionais e concorrência.
6. O sistema calcula um score entre 0 e 100.
7. O sistema apresenta o score e explica os fatores que mais influenciaram o resultado.
8. Quando o histórico estiver habilitado, o sistema preserva os dados usados na análise.

## Relações principais

- uma região possui diversos indicadores;
- uma atividade econômica pode estar associada a vários estabelecimentos;
- um estabelecimento pode exercer uma ou mais atividades econômicas;
- uma análise considera uma atividade econômica e uma região ou localização;
- uma análise pode considerar vários estabelecimentos como concorrentes;
- uma análise é composta por fatores que contribuem para o score e para a justificativa.

## Condições do domínio

- concorrentes devem ser identificados pela relação entre atividade e área geográfica;
- somente fatores efetivamente considerados podem aparecer na justificativa;
- resultados históricos devem conservar o contexto usado no cálculo;
- ausência ou baixa qualidade de dados deve ser informada ao usuário;
- o score é orientativo e precisa ser interpretado junto com sua justificativa.

## Escopo ainda não definido

- cadastro e autenticação do empreendedor;
- fórmula e pesos do score;
- limites mínimo e máximo do raio;
- fontes públicas oficiais;
- tratamento de empresas inativas;
- critérios de equivalência entre atividades;
- obrigatoriedade do histórico de análises.
