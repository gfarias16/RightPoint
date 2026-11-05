# Entrega funcional v1 — execução autorizada em 05/11/2026

O usuário desenvolve sozinho e autorizou implementar o roteiro completo. As decisões técnicas desta versão são registradas no projeto, sem depender de resposta de um grupo. Isso não equivale a validação preditiva do score.

## Contrato da entrega

- Preservar alterações locais e o método 50/50 existente. Consulta municipal pública; dados ausentes continuam indisponíveis.
- Interface da referência 03 com busca da 02: barra recolhível, resumo, mapa amplo, seleção bidirecional, CNAE automático e alternativa manual, estados acessíveis e mobile.
- Cadastro/login Laravel e dados privados por usuário para perfis persistentes e diagnósticos. Não publicar perfis de terceiros nem apagar perfis antigos do navegador automaticamente.
- Salvar diagnóstico calculado no servidor, com entradas da comparação, fontes, método, resultado e hash. Reabrir a fotografia sem recalcular. Relatório imprimível/PDF pelo navegador e exportação JSON.
- Novos dados como contexto: estimativa populacional, idades, OSM, trabalho e setores. Importadores com contrato validado, simulação padrão e gravação explícita. Não executar nova consulta paga no BigQuery, baixar bases nacionais grandes ou inventar dados ausentes.
- H3 como grade espacial e agrupamento de pontos validados, não distribuição uniforme de população municipal. Setores e endereços geocodificados precisam de aquisição e conferência próprias.

## Etapas e verificação

1. Persistência/autorização/snapshots e testes SQLite; migrations aditivas, sem reset.
2. Interface, relatório e integração; manter identidade grafite/verde e mapa real do IBGE.
3. Adaptadores/contextos e piloto espacial; verificar contratos e falhas com dados fictícios; distinguir código pronto de carga real.
4. Sintaxe, Pint, JavaScript, suíte completa, validação no navegador, segurança e documentação/commits separados. Push pelo usuário.

## Limites operacionais

Migrations executadas só após conferir estado/SQL. Cargas externas não repetem o que já existe e não alteram o Censo/renda/lote CNPJ atuais. Outra máquina precisa de banco/dependências próprios. Cadastro acadêmico não inclui serviço de e-mail ou recuperação de senha sem configuração. Publicação requer HTTPS, configuração de produção e revisão de secrets historicamente rastreados.

## Resultado da execução

Persistência, conta/autorização, snapshots, relatório/JSON, interface, contexto IBGE, comparação populacional separada e piloto H3 implementados. Migrations aditivas e carga IBGE real concluídas preservando fatores antigos. Suíte 255 testes/1.383 asserções, Pint, sintaxe JS, HTTP real e teste de escrita/releitura MySQL com rollback aprovados.

Gate visual **não concluído**: browser integrado indisponível, acesso alternativo ao Edge expirou. Não afirmar validação desktop/mobile/H3/PDF. Adaptadores territoriais testados com dados sintéticos; OSM real não passou na validação e não foi gravado. RAIS/Caged, setores e endereços reais aguardam aquisição e normalização, não geração fictícia. Nenhum score espacial ou expansão comercialmente validada foi entregue.

O [STATUS](../STATUS.md) separa implementado/testado/carga real/pendente; o [procedimento](../procedimentos/workspace-contextos-h3.md) permite retomar em outro PC. Push continua pelo usuário.
