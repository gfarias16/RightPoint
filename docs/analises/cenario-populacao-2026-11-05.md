# Cenário de população — Censo 2022 versus estimativa 2026

Execução local de leitura em 05/11/2026 com `diagnostico:comparar-populacoes CNAE --json`. Mesmos 92 municípios, renda 2022, fotografia CNPJ do método piloto e pesos 50/50. Somente o denominador muda; **consulta padrão não foi alterada**. JSON emitido inclui entradas/metadados/resultados; este resumo não substitui o arquivo completo para reprodução futura.

| CNAE | Cidades com posição alterada / 92 | SHA-256 do cenário emitido |
| --- | ---: | --- |
| 5611201 | 64 | `b369b652c4d2ae40c2a5ac016f5657f58b18719841432166008bc323bf0d0a18` |
| 1091102 | 63 | `c9a365b9bb11bd97135d4a66e25948d96c2b46c3d04cb25dedd5a91b1076c8eb` |
| 4721102 | 71 | `e47ae784fbd48c18f5791dc49840c8de27db2c27313405c64e8268b68b52f20a` |
| 9602501 | 80 | `b204da03d44917fb1ccadb93396bd769e91e2efdc62c0f88abc168789f181830` |

| CNAE | Município | Nota Censo → estimativa | Posição Censo → estimativa |
| --- | --- | --- | --- |
| 5611201 | Maricá | 50,0 → 50,5 | 45 → 44 |
| 5611201 | Barra Mansa | 59,3 → 60,4 | 22 → 20 |
| 1091102 | Maricá | 44,5 → 44,5 | 60 → 60,5 |
| 1091102 | Barra Mansa | 51,1 → 52,2 | 48,5 → 46 |
| 4721102 | Maricá | 62,6 → 64,8 | 20 → 17 |
| 4721102 | Barra Mansa | 39,0 → 39,6 | 70 → 69 |
| 9602501 | Maricá | 44,5 → 44,5 | 64 → 62 |
| 9602501 | Barra Mansa | 35,7 → 36,3 | 75 → 75 |

Notas visuais têm uma casa; posições usam o resultado sem esse arredondamento e empates médios. Uma nota visual igual pode ocultar diferença precisa ou mudança relativa aos demais municípios. O número de alterações considera deslocamento numérico diferente de zero, inclusive meios postos; não conta a mera mudança de tipo numérico PHP.

Maricá tem população estimada 2026 de **212.703**, mantendo a fonte Censo 2022 no diagnóstico padrão. Os contextos de idade 2022 importados são 35.313 (0–14), 136.345 (15–64) e 25.619 (65+), não clientes estimados da atividade.

O cenário demonstra dependência da referência escolhida, não melhora de previsão, oportunidade garantida ou chance de sucesso. A renda continua nominal de 2022; CNPJ 2026 continua premissa do projeto, sem competência confirmada pela fonte. Não compatibiliza todos os fatores no mesmo ano. Cargas/contratos/limites no [procedimento](../procedimentos/workspace-contextos-h3.md).
