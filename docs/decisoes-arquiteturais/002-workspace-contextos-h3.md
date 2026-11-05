# 002 — Histórico privado e camadas territoriais separadas do score

Data: 05/11/2026. Estado: implementado por autorização do usuário, que agora desenvolve sozinho. Não representa aprovação acadêmica ou validação comercial.

## Contexto e alternativas

A consulta era pública e perfis existiam somente em localStorage. Guardar todos os perfis num banco sem proprietário exporia análises de outras pessoas; manter somente no navegador impediria histórico consistente entre dispositivos do mesmo servidor. Foi escolhida sessão/autenticação nativa Laravel, sem outra stack ou provedor externo. Alternativas: continuar localStorage ou introduzir provedor de identidade; a primeira não atende à persistência privada e a segunda amplia desnecessariamente dependências nesta versão acadêmica.

Guardar somente nota/hash não permite reproduzir percentis dos 92 municípios. Recalcular ao reabrir apagaria o sentido histórico. Por isso o servidor recalcula no salvamento em transação e preserva snapshot JSON, entradas comparativas/fontes/método/referências/limitações e hash. Reabrir não recalcula. Relatório HTML imprimível mais JSON evita dependência de gerador PDF no servidor; PDF é salvo pelo navegador.

## Decisão de dados e espaço

Migrations aditivas criam perfis, diagnósticos e contextos territoriais. Registros privados sempre filtram proprietário; o cliente não escolhe `user_id` ou score. Cópia do localStorage é explícita e idempotente por origem, sem apagar os originais. As tabelas existentes e as regras do piloto são preservadas.

Novas estimativas/idades entram em `indicadores_municipais`, com fonte/unidade/edição próprias; não substituem Censo/renda do método. Comando de leitura compara referências populacionais em cenário separado. POIs, trabalho, setores e pontos geocodificados usam lotes versionados em `contextos_territoriais`. Importações simulam por padrão; não confundir validação estrutural de JSON com certificação da fonte.

H3 organiza pontos disponíveis numa grade municipal limitada. Não há população municipal distribuída uniformemente, percentual de sucesso ou novo fator de score. Setores reais podem ser visualizados sem rateio para células. Alternativas descartadas: transformar ausência em zero, distribuir população por área sem hipótese validada ou introduzir banco geoespacial/serviços adicionais antes de adquirir dados úteis.

## Consequências, riscos e reversibilidade

- Contas/dados ficam no banco local, não no Git. Portabilidade exige procedimento e backups; não há sincronização de bancos.
- Snapshot consome espaço e pode conter geometrias do recorte; limites dos pilotos, paginação e transferência sob demanda reduzem o custo, mas retenção/cotas operacionais continuam pendentes para produção.
- Sem recuperação de senha/e-mail, cadastro é acadêmico. Publicação requer HTTPS/configuração/segurança e revisão dos secrets legados; não ampliar a exceção do `.env`.
- H3/CDN depende de rede, malha simplificada e inclusão por centro: bordas são aproximações; o usuário é informado.
- OSM real não foi carregado; trabalho/setores/pontos reais exigem preparação do contrato. Testes sintéticos não resolvem a aquisição.
- Rollback funcional: retornar à versão anterior do código, preservando backup e tabelas novas. Não fazer rollback de migrations com perfis/diagnósticos reais sem exportação e autorização: o método `down()` remove as tabelas e seus dados. Os fatores do score anterior não dependem dessas cargas novas.

Operação/contratos e limites: [procedimento](../procedimentos/workspace-contextos-h3.md). Evidência atual: [STATUS](../STATUS.md).
