---
title: Importar equipe de uma planilha
summary: Baixe o modelo de funcionário, preencha-o e carregue-o – reenviar com as mesmas identidades nacionais atualiza a equipe existente em vez de duplicá-la.
category: hr
slug: importing-staff-from-spreadsheet
order: 2
published: true
updated: 2026-10-27
---

# Importar equipe de uma planilha

O importador cria registros de equipe em massa, com os mesmos campos do assistente **Novo Funcionário**. Também é seguro executar mais de uma vez: os funcionários são acompanhados por **ID nacional**, portanto, um novo upload atualiza os funcionários existentes em vez de criar duplicatas.

## 1. Baixe o modelo

1. Abra **RH e folha de pagamento → Diretório de funcionários e RH → Funcionários**.
2. Clique em **Importar funcionários (Excel/CSV)**.
3. No assistente, clique em **Baixar modelo do Excel**.

O modelo chega com os cabeçalhos de coluna corretos e cinco linhas de exemplo mostrando como os dados devem ser variados. Exclua os exemplos antes de entrar na sua pauta real, ou guarde-os e fixe os valores.

## 2. Preencha

Apenas estas seis colunas devem estar presentes com valores:

| Coluna obrigatória | Notas |
| --- | --- |
| Nome | |
| Sobrenome | |
| E-mail | Deve ser exclusivo nas contas da equipe e do portal |
| Número de telefone | |
| Carteira de Identidade Nacional | A chave correspondente — nunca a altere para uma pessoa existente |
| Designação | Cargo, por ex. “Professor de Matemática” |

Todo o resto é opcional e tem um padrão sensato:

| Coluna | Padrão quando em branco |
| --- | --- |
| Gênero | Feminino |
| Data de Nascimento | 01/01/1990 |
| Estado civil | Único |
| Endereço Físico | Não fornecido |
| Nome/telefone do contato de emergência | Não fornecido |
| Departamento | Em branco (ver Departamentos abaixo) |
| Função | Corpo Docente |
| Tipo de emprego | Permanente |
| Data de adesão | Hoje |
| Grau salarial | Em branco |
| Estado | Ativo |

**A data de término do contrato é necessária quando o tipo de emprego é Contrato.** As datas devem ser datas do Excel ou `YYYY-MM-DD`.

### Listas e pares

Algumas colunas contêm mais de um valor. Use dois pontos entre o nome e o valor e um ponto e vírgula entre as entradas:

- `Individual Allowances` → `Teaching Allowance: 150; Transport: 80`
- `Spouse Details` → `Spouse: Rudo Maphosa; Occupation: Nurse`
- `Dependents` → `Child 1: Tadiwa (8); Child 2: Kuda (5)`
- `Next of Kin` → `Name: Rudo Maphosa; Phone: 0771234567; Relationship: Spouse`
- `Custom Fields` → `TSC Number: 123456; Pension Number: PN-88`

## 3. Faça upload

1. Clique em **Importar funcionários (Excel/CSV)** e escolha o arquivo preenchido. As colunas são correspondidas automaticamente a partir dos nomes dos cabeçalhos — não há tela de mapeamento.
2. Revise o resumo e confirme a importação.

## 4. O que acontece no reenvio

Esta é a parte importante para atualizar a equipe em massa:

- **Mesma identificação nacional, detalhes alterados** → o funcionário existente é atualizado. Nenhum novo registro, nenhuma conta duplicada do portal e nenhum segundo e-mail de ativação.
- **Mesma identificação nacional com um novo e-mail** → o e-mail no registro do funcionário e a conta de login vinculada são atualizados.
- **Nova ID nacional** → um novo funcionário é criado e uma conta do portal é provisionada.
- **O e-mail pertence a outra pessoa** → a linha é rejeitada com uma mensagem clara; corrija o e-mail e faça upload novamente.

Portanto, para atualizar uma lista de funcionários, baixe o modelo (ou sua última exportação), altere apenas os e-mails ou outros detalhes necessários e carregue o mesmo arquivo novamente.

## Departamentos e níveis salariais

- **Departamento** corresponde a um departamento existente por nome. Caso não exista, o importador pergunta se deseja criá-lo ou ignorar esse valor — criá-lo também gera um código de departamento.
- **Classificação salarial** corresponde a uma classificação existente por nome. Deixe em branco se a pessoa ainda não tiver nota.

## Quando as linhas falham

A importação nunca é salva pela metade: as linhas com problemas são listadas nos resultados com o motivo exato (por exemplo, uma data de término do contrato ausente, um gênero inválido, uma função desconhecida ou uma data malformada). Baixe as linhas rejeitadas como CSV, corrija-as e importe apenas esse arquivo. As linhas válidas no mesmo upload ainda serão importadas.

> Mantenha a identificação nacional estável para os funcionários existentes – essa única coluna é o que torna um reenvio uma atualização em vez de uma duplicata.
