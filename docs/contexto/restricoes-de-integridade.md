# Restrições de Integridade - RightPoint

## Objetivo

Estas restrições definem condições para que os dados do RightPoint permaneçam válidos e coerentes. Elas complementam as [regras de negócio](./regras-de-negocio.md), que descrevem como o domínio funciona.

## Restrições

### RI-001 - Identificação da análise

Cada análise armazenada deve possuir um identificador único.

### RI-002 - Atividade obrigatória

Toda análise deve estar associada a exatamente uma atividade econômica principal.

Relacionada a: `RN-001`.

### RI-003 - Referência geográfica obrigatória

Toda análise deve possuir pelo menos uma referência geográfica válida: uma região ou uma localização.

Relacionada a: `RN-002`.

### RI-004 - Faixa do score

O score, quando calculado, deve ser um valor entre 0 e 100, inclusive.

Relacionada a: `RN-009`.

### RI-005 - Raio válido

Quando uma análise utilizar raio, o valor deve ser maior que zero e sua unidade deve ser metros ou quilômetros.

A CONFIRMAR: limites mínimo e máximo permitidos.

### RI-006 - Coordenadas válidas

Quando informadas, a latitude deve estar entre -90 e 90 e a longitude entre -180 e 180.

### RI-007 - CNPJ único

Cada CNPJ deve identificar no máximo um estabelecimento armazenado. O valor deve conter 14 dígitos e passar pela validação de CNPJ quando essa informação estiver disponível.

### RI-008 - Atividade do estabelecimento

Todo estabelecimento considerado na análise de concorrência deve estar associado a pelo menos uma atividade econômica.

### RI-009 - Domínio da situação cadastral

A situação cadastral deve usar valores reconhecidos pela fonte de dados adotada. Valores desconhecidos devem ser registrados como não classificados, sem serem convertidos silenciosamente em situação ativa.

### RI-010 - Origem do indicador

Todo indicador regional usado em uma análise deve registrar tipo, valor, unidade quando aplicável, fonte e data de referência.

### RI-011 - Valor compatível com o indicador

O valor de um indicador deve respeitar seu domínio. População e quantidade de concorrentes não podem ser negativas; percentuais devem permanecer entre 0 e 100.

### RI-012 - Rastreabilidade do cálculo

Uma análise concluída deve guardar os fatores e indicadores que produziram o score e a justificativa.

Relacionada a: `RN-012`, `RN-013` e `RN-014`.

### RI-013 - Preservação histórica

Os dados consolidados de uma análise concluída não devem ser alterados por atualizações posteriores das fontes públicas. Uma nova execução deve criar uma nova análise.

Relacionada a: `RN-014` e `RN-015`.

### RI-014 - Coerência da justificativa

Cada fator citado na justificativa deve corresponder a um fator registrado na análise.

### RI-015 - Resultado sem dados suficientes

Uma análise classificada como sem dados suficientes não pode ser apresentada como resultado confiável.

A CONFIRMAR: se essa situação impedirá a geração do score ou produzirá um score acompanhado de nível de confiança.

## Pontos para validação

- uma análise poderá combinar região e localização ou deverá escolher apenas uma delas;
- um estabelecimento poderá ter várias atividades ou somente uma atividade principal;
- quais valores formarão o domínio oficial da situação cadastral;
- quais indicadores aceitam valor ausente;
- qual precisão decimal será usada no score e nas coordenadas.
