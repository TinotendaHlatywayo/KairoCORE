---
title: Solução de problemas comuns
summary: Correções para os problemas mais atingidos pelas escolas: problemas de login, alunos ausentes, boletins em branco e entrega de e-mails.
category: troubleshooting
slug: common-issues
order: 1
published: true
updated: 2026-10-27
---

# Solução de problemas comuns

Verificações rápidas dos problemas que surgem com mais frequência. Caso algo aqui não resolva, entre em contato com o suporte através do site da plataforma.

## Não consigo fazer login

- Confirme que você está no URL do espaço de trabalho da sua escola (por exemplo, `your-school.kairo.co.zw/workspace`), e não no endereço da plataforma.
- Use **Esqueci a senha** na página de login para redefinir.
- Se você nunca recebeu o e-mail de ativação, verifique o spam e peça a um administrador para reenviá-lo em **Administração do sistema → Gerenciamento de usuários → Contas de usuário**.

## Um aluno não aparece nas listas

- Verifique a tabela **filtros** — um formulário restante ou filtro de ano oculta alunos com correspondências diferentes.
- Pesquise por número de admissão ou número de carteira de estudante em vez de nome (a grafia difere mais do que você imagina).
- Caso o aluno tenha sido admitido por meio de Candidaturas, confirme se a candidatura realmente atingiu o status de admitido.

## O mecanismo de faturamento automático não criou nada

- Verifique se os alunos têm uma **matrícula** (ano letivo + formulário) — o faturamento tem como alvo as matrículas, e não as linhas vazias dos alunos.
- Verifique se existem **estruturas de taxas** para esse formulário e ano letivo.
- Se já existirem faturas para esse escopo e ainda não forem pagas, o mecanismo ignora esses alunos propositalmente para evitar cobrança dupla.

## Os boletins são impressos com marcas de assunto vazias

- Confirme se as **marcas foram obtidas** em relação à avaliação desse período e disciplina.
- Confirme se os relatórios foram **gerados após** a inserção das notas — gere novamente se as notas chegarem posteriormente.
- Verifique se você está imprimindo o **termo** correto.

## Os e-mails de anúncio não chegaram

- Verifique se o **Canal de entrega** era **Somente e-mail** ou **Sistema e e-mail**, e não apenas sistema.
- Confirme se o público realmente inclui os destinatários (a segmentação baseada em função corresponde ao armazenamento de função real).
- Verifique a pasta de spam da escola; peça à TI para permitir o domínio de envio da plataforma.

## Um pagamento está na fatura errada

- Registre pagamentos somente por meio de **Registrar pagamento** na linha da fatura, o que mantém os saldos e os recebimentos consistentes.
- Se um pagamento foi capturado na fatura errada, entre em contato com o suporte em vez de editar o banco de dados – o histórico de pagamentos alimenta recibos e extratos.

## Uma importação de CSV da equipe relata erros

- Use **Baixar modelo do Excel** na tabela Funcionários para que os nomes das colunas correspondam exatamente.
- **Nome, sobrenome, e-mail, número de telefone, identidade nacional e designação** são obrigatórios.
- A ID Nacional é a chave correspondente na reimportação: uma ID nacional idêntica atualiza o funcionário existente em vez de criar uma duplicata. Altere o e-mail para atualizá-lo.
- O pessoal contratado deve ter uma **Data de término do contrato**.
- Corrija as linhas rejeitadas e faça upload apenas delas — consulte [Importar equipe de uma planilha](/docs/hr/importing-staff-from-spreadsheet).
