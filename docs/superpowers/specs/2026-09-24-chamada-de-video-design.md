# Chamada de vídeo paga no chat

**Data:** 2026-09-24
**Origem:** Bento, áudio 11 ("tem um botão chamar de vídeo, o cara clica, paga, e aí libera pra ela fazer a chamada") e decisões de 21/09 por WhatsApp (LiveKit no plano grátis, sem gravação, estorno em crédito na carteira, registro de conexão como prova, "medir a febre" antes de pagar servidor).
**Decisões do Pedro em 24/09:** criadora configura e fã pede; criadora marca o horário, fã pode sugerir; "ela entrou = aconteceu"; duas entregas.

## 1. Regras de negócio

- A criadora liga a chamada na página de planos e define **preço** e **duração em minutos**. Quem não liga não tem botão.
- O fã pede pela conversa, **paga com saldo da carteira** na hora do pedido e pode **sugerir um horário** (opcional). Sem saldo, vai pra recarga levando o id do pedido; quando o PIX cai, o webhook conclui o pedido sozinho (mesmo caminho da mensagem trancada).
- O dinheiro fica **reservado**: sai da carteira do fã e não entra no saldo de ninguém até a chamada acontecer.
- A criadora **marca** o horário (a sugestão do fã vem preenchida) ou **recusa**. Pode remarcar quantas vezes quiser enquanto não aconteceu. Marcar sem horário = agora.
- **Aconteceu = a criadora entrou na sala** dentro da janela. A partir daí o valor é dela, com a divisão e o prazo do chat (80/15/5 no PIX ou carteira, 76/19/5 se o saldo veio de cartão; afiliado sai da parte da plataforma; 7 dias de liberação contados da entrada dela). Fã que não aparece é problema do fã.
- **Devolução automática ao fã, sempre como saldo na carteira**, em três casos: ela recusou (`refused`); passou o horário marcado mais a tolerância e ela não entrou (`no_show`); pedido sem horário há 7 dias, ou qualquer chamada não realizada 30 dias depois do pagamento (`expired`).
- Fora de escopo, de propósito: cancelamento pelo fã (ele pede pra ela recusar), avaliação, gravação, notificação por e-mail/push, mais de um pedido aberto por conversa, fã escolher duração, chat dentro da chamada, tela compartilhada, mais de duas pessoas, chamada só de áudio.

## 2. Dados

### Tabela `video_calls` (primeira entrega)

| coluna | tipo | pra quê |
|---|---|---|
| conversation_id, creator_id, user_id | fk | de quem é (user_id = fã) |
| message_id | fk nullable | primeira mensagem do pedido na conversa |
| price, duration_minutes | decimal, int | copiados da configuração dela no momento do pedido |
| status | enum | `awaiting_payment`, `requested`, `scheduled`, `done`, `refunded` |
| suggested_at | datetime nullable | sugestão do fã |
| scheduled_at | datetime nullable | horário marcado por ela (UTC) |
| creator_joined_at | datetime nullable | quando ela entrou; é o que torna a chamada `done` |
| amount_paid, platform_percentage, platform_amount, affiliate_user_id, affiliate_amount, creator_amount, payment_transaction_id | iguais a `message_purchases` | divisão do dinheiro |
| refund_reason, refunded_at | enum nullable (`refused`, `no_show`, `expired`), datetime | por que e quando voltou |
| timestamps | | |

Índices: `(conversation_id, status)`, `(creator_id, status)`, `(user_id)`.

`awaiting_payment` (decidido no plano, 24/09): a linha nasce quando o fã clica em pagar. Com saldo, vira `requested` na mesma requisição. Sem saldo, o fã vai pra recarga com `video_call_id`, e o webhook conclui o pagamento quando o PIX cai. Sem dinheiro, sem mensagem na conversa e invisível pra criadora; não conta como pedido aberto nem entra em saldo. Linha que nunca foi paga fica parada, sem efeito.

### Colunas na `users`
`video_call_enabled` (bool, padrão false), `video_call_price` (decimal nullable), `video_call_minutes` (int nullable).

### Configurações no admin (`platform_settings`)
`video_calls_enabled` (padrão 0; fica desligado até a segunda entrega), `video_call_tolerance_minutes` (padrão 30).

### Segunda entrega (migration própria)
`room_name`, `user_joined_at`, `ended_at` em `video_calls`. Chaves do LiveKit no `.env`: `LIVEKIT_URL`, `LIVEKIT_API_KEY`, `LIVEKIT_API_SECRET`, lidas por `config/services.php`.

### Estados

```
requested --marcar--> scheduled --criadora entrou na janela--> done
requested --recusar--> refunded (refused)
requested --7 dias--> refunded (expired)
scheduled --recusar--> refunded (refused)
scheduled --horário + tolerância sem ela--> refunded (no_show)
qualquer não done --30 dias do pagamento--> refunded (expired)
```

`done` é terminal para o robô: nunca vira `refunded` sozinho. Só o admin devolve uma chamada `done` (segunda entrega).

## 3. Fluxo e dinheiro

1. **Configuração da criadora** (`subscription-plans/index`): bloco "Chamada de vídeo" com interruptor, preço, minutos e "você recebe X%". Salva no mesmo botão dos planos. Só aparece com `video_calls_enabled` ligado no admin. Validação: preço ≥ 1, minutos entre 5 e 120.
2. **Pedido** (cabeçalho da conversa, só se ela ligou): botão "Chamada de vídeo · R$ P · N min" abre caixa com "sugerir horário" (input `datetime-local`) e "Pagar R$ P com saldo", com o aviso de que o valor fica reservado e volta se ela recusar ou não aparecer. No servidor, dentro de `DB::transaction` com `lockForUpdate` na carteira: recusa se já existe `requested`/`scheduled` na conversa; debita a carteira (`subtractBalance`, "Chamada de vídeo com @x (reservado)"); cria `PaymentTransaction` tipo `wallet` com `video_call_id`; calcula a divisão como `PaidMessageController::comprarComSaldo`; cria a `video_call` em `requested`; grava mensagem `video_call` na conversa. Sem saldo: JSON com `recarregar` e `redirect` pra `wallet.index` com `amount` e `video_call_id`; o webhook da recarga chama a mesma função estática que o clique.
3. **Resposta da criadora** (card na conversa): "Marcar" (datetime-local, vem com a sugestão; vazio = agora) e "Recusar". Marcar: `scheduled` + mensagem. Remarcar: mesmo endpoint, mensagem nova. Recusar: transação com lock na linha, `refunded/refused`, `addBalance` na carteira do fã ("Devolução da chamada com @x"), mensagem.
4. **Janela** (dos dois lados): de 10 min antes de `scheduled_at` até `scheduled_at + tolerância` (e, depois de `done`, até `creator_joined_at + duração`), o card mostra "Entrar na chamada". Na primeira entrega o botão fica desabilitado com "em breve".
5. **Robô** (`php artisan chamadas:rodar`, no cron a cada minuto): devolve `no_show` e `expired` conforme a seção 1. Cada devolução em transação com `lockForUpdate` e reconferência do estado, pra rodar duas vezes sem creditar duas vezes. Na segunda entrega também fecha sala (seção 4).
6. **Onde o dinheiro aparece:** carteira do fã (débito reservado, crédito de devolução); saldo da criadora e do afiliado leem `video_calls` com `status = done` e prazo do chat sobre `creator_joined_at`, no mesmo lugar em que somam assinatura, avulso e mensagem; tela da criadora mostra "reservado em chamadas marcadas"; fluxo de caixa e vendas do admin só somam `done`.

Mensagens na conversa: toda transição grava uma mensagem `message_type = video_call` com `video_call_id`. O `toChatPayload` leva o estado atual da chamada; o card mais recente desenha o estado com os botões, os anteriores viram linha de histórico. Isso resolve atualização de tela, aviso pro outro lado, "última mensagem" e não lidas sem nada novo.

Fuso: a criadora marca em Brasília (`America/Sao_Paulo`), o banco guarda UTC, a tela converte de volta.

## 4. Segunda entrega: LiveKit

- **Token:** JWT HS256 assinado com o segredo, sem pacote (header, payload com `iss` = chave, `sub` = id do usuário, `name` = @, `nbf`, `exp`, `video` = `{room, roomJoin, canPublish, canSubscribe}`). Endpoint nosso confere: chamada `scheduled` ou `done`, quem pede é a criadora ou o fã dela, dentro da janela. Criadora entrando em `scheduled` vira `done` com `creator_joined_at` na mesma requisição. Fã entrando não muda estado. `exp` = fim da duração + tolerância.
- **Página da chamada** (`/chamada/{id}`): vídeo remoto grande, local pequeno, microfone, câmera, sair, cronômetro regressivo a partir de `creator_joined_at` vindo do servidor. Zerou, desconecta. `livekit-client` por CDN. Sem `navigator.mediaDevices` (navegador do Instagram), aviso "abra no Chrome ou Safari".
- **Fechar sala:** o robô, pra chamada `done` com `creator_joined_at + duração` passado e sem `ended_at`, chama `RoomService/DeleteRoom` e grava `ended_at`.
- **Webhook** (rota pública, fora do CSRF, assinatura conferida): grava `user_joined_at` e `ended_at`. Nunca muda `done` nem devolve dinheiro.
- **Medir a febre:** no admin, chamadas realizadas no mês e minutos somados (plano grátis ≈ 37 h/mês).

## 5. Admin

- Configurações: "Chamada de vídeo ligada" e "tolerância de atraso da criadora (min)".
- `/admin/chamadas`: data, criadora, fã, valor, duração, estado, horário marcado, entrada dela, motivo da devolução; filtro por estado. Na segunda entrega, botão "devolver ao fã" (única intervenção humana prevista).
- Vendas por criadora e fluxo de caixa somam chamada `done` junto da mensagem do chat.

## 6. Teste (prod, transação + rollback, conta de teste id 1928)

1. Configuração: salva; recusa preço zero; mostra "você recebe 80%".
2. Pedido: debita e cria `requested` com 80/15/5 (76/19/5 com saldo de cartão); sem saldo devolve link de recarga com o id; clique duplo não cria dois; pedido com outro aberto é recusado; conversa recebe a mensagem.
3. Resposta: marcar vira `scheduled` com UTC certo ("sábado 21h" Brasília = 00h UTC); remarcar; recusar devolve o valor exato e fica `refunded/refused`.
4. Robô: 31 min depois do horário devolve; 29 não; `requested` com 8 dias devolve; rodar duas vezes credita uma vez; `done` nunca devolve.
5. Saldo: `done` de hoje em "a liberar", de 8 dias atrás em "liberado", `scheduled` só no "reservado"; afiliado idem; fluxo de caixa ignora o que não é `done`.
6. Render em prod das views (planos, conversa dos dois lados, admin) pela pasta temporária antes do deploy.
7. Segunda entrega: token só na janela; criadora entrando vira `done`; fã entrando não muda; robô fecha sala e grava `ended_at`; webhook com assinatura errada recusado; webhook repetido não muda nada.

## 7. Entregas

**Primeira:** migration (tabela + 3 colunas na users), `VideoCall` model, `VideoCallController`, comando `chamadas:rodar` + cron, ajustes em planos, conversa, `Message::toChatPayload`, saldos (User, afiliado), admin (configurações, lista, vendas, fluxo de caixa). Tudo atrás de `video_calls_enabled`.
**Segunda:** migration das colunas da sala, `LiveKitService` (token, DeleteRoom, verificação do webhook), endpoint do token, página da chamada, webhook, "devolver ao fã" no admin, contador no admin.

## 8. Riscos conhecidos

- Cron: o projeto não agenda nada hoje. Se o servidor não tiver `schedule:run` no crontab, entra uma linha (pedir ao Pedro antes).
- Dinheiro reservado não é de ninguém: todo lugar que soma por tabela (saldos, vendas, fluxo de caixa, afiliado) precisa filtrar `status = done`. Mapear cada ponto antes de mexer.
- Criadora desativada ou bloqueada com chamada aberta: devolução usa `withoutGlobalScope('active')`.
