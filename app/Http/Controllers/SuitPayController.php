<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SuitPayController extends Controller
{
    private string $MODE;
    private string $SANDBOX_URL = 'https://sandbox.ws.suitpay.app';
    private string $PROD_URL    = 'https://ws.suitpay.app';
    private string $CLIENT_ID;
    private string $CLIENT_SECRET;
    private string $WEBHOOK_URL;

    public function __construct()
    {
        $this->MODE          = env('SUITPAY_MODE', 'production');
        $this->CLIENT_ID     = env('SUITPAY_CLIENT_ID', '');
        $this->CLIENT_SECRET = env('SUITPAY_CLIENT_SECRET', '');
        $this->WEBHOOK_URL   = env('SUITPAY_WEBHOOK_URL', '');
    }

    private function host(): string
    {
        return $this->MODE === 'sandbox'
            ? $this->SANDBOX_URL
            : $this->PROD_URL;
    }

    private function headers(): array
    {
        return [
            'ci' => trim($this->CLIENT_ID),
            'cs' => trim($this->CLIENT_SECRET),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * =====================================
     * PIX – GERAÇÃO DE QRCODE
     * =====================================
     *
     * @param float $amount Valor do pagamento
     * @param string $requestNumber UUID único da requisição
     * @param array $client Dados do cliente (name, document, phoneNumber, email, address)
     * @param string $productDescription Descrição do produto/plano
     * @param string|null $callbackUrl URL do webhook (opcional, usa padrão se null)
     */
    public function pix(float $amount, string $requestNumber, array $client, string $productDescription, ?string $callbackUrl = null)
    {
        $payload = [
            'requestNumber' => $requestNumber,
            'dueDate' => now()->addDay()->format('Y-m-d'),
            'amount' => $amount,
            'shippingAmount' => 0.0,
            'discountAmount' => 0.0,
            'usernameCheckout' => 'checkout',
            'callbackUrl' => $callbackUrl ?? $this->WEBHOOK_URL,

            'client' => $client,

            'products' => [
                [
                    'description' => $productDescription,
                    'quantity' => 1,
                    'value' => $amount,
                ],
            ],
        ];

        Log::info('SUITPAY PIX REQUEST', [
            'url' => $this->host() . '/api/v1/gateway/request-qrcode',
            'payload' => $payload,
        ]);

        $response = Http::withHeaders($this->headers())
            ->post($this->host() . '/api/v1/gateway/request-qrcode', $payload);

        Log::info('SUITPAY PIX RESPONSE', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json() ?? null,
            'raw' => $response->body(),
        ];
    }

    /**
     * =====================================
     * CARTÃO – V3 (VENDA DIRETA)
     * =====================================
     *
     * @param float $amount Valor do pagamento
     * @param string $requestNumber UUID único da requisição
     * @param array $cardData Dados do cartão (number, expirationMonth, expirationYear, cvv, installment)
     * @param array $client Dados do cliente (name, document, phoneNumber, email, address)
     * @param string $productDescription Descrição do produto/plano
     * @param string|null $callbackUrl URL do webhook (opcional, usa padrão se null)
     */
    public function card(float $amount, string $requestNumber, array $cardData, array $client, string $productDescription, ?string $callbackUrl = null)
    {
        $payload = [
            'requestNumber' => $requestNumber,

            'card' => [
                'number' => $cardData['number'],
                'expirationMonth' => $cardData['expirationMonth'],
                'expirationYear' => $cardData['expirationYear'],
                'cvv' => $cardData['cvv'],
                'installment' => $cardData['installment'] ?? 1,
                'amount' => $amount,
            ],

            'client' => $client,

            'products' => [
                [
                    'productName' => $productDescription,
                    'idCheckout' => (string) Str::uuid(),
                    'quantity' => 1,
                    'value' => $amount,
                ],
            ],

            'callbackUrl' => $callbackUrl ?? $this->WEBHOOK_URL,
        ];

        Log::info('SUITPAY CARD V3 REQUEST', [
            'url' => $this->host() . '/api/v3/gateway/card',
            'payload' => $payload,
        ]);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30) // Timeout de 30 segundos
                ->post($this->host() . '/api/v3/gateway/card', $payload);

            $data = $response->json();
            $responseBody = $response->body();

            Log::info('SUITPAY CARD V3 RESPONSE', [
                'requestNumber' => $requestNumber,
                'http_status' => $response->status(),
                'successful' => $response->successful(),
                'payload' => $data,
                'raw_body' => $responseBody,
            ]);

            // Se não conseguiu fazer parse do JSON, loga o body bruto
            if ($data === null && !empty($responseBody)) {
                Log::warning('SUITPAY CARD V3 - RESPOSTA NÃO É JSON VÁLIDO', [
                    'requestNumber' => $requestNumber,
                    'http_status' => $response->status(),
                    'raw_body' => $responseBody,
                ]);
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $data ?? null,
                'raw' => $responseBody,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SUITPAY CARD V3 - ERRO DE CONEXÃO', [
                'requestNumber' => $requestNumber,
                'error' => $e->getMessage(),
                'url' => $this->host() . '/api/v3/gateway/card',
            ]);

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'raw' => null,
                'error' => 'Erro de conexão com o gateway de pagamento.',
            ];
        } catch (\Exception $e) {
            Log::error('SUITPAY CARD V3 - EXCEÇÃO GERAL', [
                'requestNumber' => $requestNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'data' => null,
                'raw' => null,
                'error' => $e->getMessage(),
            ];
        }
    }



    /**
     * =====================================
     * PIX TRANSFER – SAQUE PARA USUÁRIO
     * =====================================
     *
     * @param string $pixKey Chave PIX do destinatário
     * @param string $typeKey Tipo da chave PIX (document | phoneNumber | email | randomKey | paymentCode)
     * @param float $value Valor da transferência
     * @param string|null $externalId ID externo único (opcional, será gerado se não fornecido)
     * @return array Resposta estruturada com sucesso, status, dados e mensagens
     */
    public function pixTransfer(string $pixKey, string $typeKey, float $value, ?string $externalId = null): array
    {
        // Mapeia tipos de chave do sistema para tipos da SuitPay
        $suitPayTypeKeyMap = [
            'cpf' => 'document',
            'email' => 'email',
            'telefone' => 'phoneNumber',
            'aleatoria' => 'randomKey',
        ];

        $suitPayTypeKey = $suitPayTypeKeyMap[$typeKey] ?? $typeKey;

        // Gera externalId se não fornecido
        if (!$externalId) {
            $externalId = (string) Str::uuid();
        }

        $payload = [
            'key' => $pixKey,
            'typeKey' => $suitPayTypeKey,
            'value' => (float) $value,
            'externalId' => $externalId,
            'callbackUrl' => $this->WEBHOOK_URL,
        ];

        Log::info('SUITPAY PIX CASHOUT REQUEST', [
            'url' => $this->host() . '/api/v1/gateway/pix-payment',
            'payload' => $payload,
        ]);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post($this->host() . '/api/v1/gateway/pix-payment', $payload);

            $data = $response->json();
            $responseBody = $response->body();

            Log::info('SUITPAY PIX CASHOUT RESPONSE', [
                'http_status' => $response->status(),
                'successful' => $response->successful(),
                'payload' => $data,
                'raw_body' => $responseBody,
            ]);

            // Se não conseguiu fazer parse do JSON
            if ($data === null && !empty($responseBody)) {
                Log::warning('SUITPAY PIX CASHOUT - RESPOSTA NÃO É JSON VÁLIDO', [
                    'http_status' => $response->status(),
                    'raw_body' => $responseBody,
                ]);

                return [
                    'success' => false,
                    'status' => 'ERROR',
                    'http_status' => $response->status(),
                    'message' => 'Resposta inválida da API SuitPay.',
                    'data' => null,
                    'raw' => $responseBody,
                ];
            }

            $responseCode = $data['response'] ?? null;
            $transactionId = $data['idTransaction'] ?? null;

            // ✅ Sucesso
            if ($responseCode === 'OK') {
                return [
                    'success' => true,
                    'status' => 'PAID_OUT',
                    'http_status' => $response->status(),
                    'message' => 'Transferência PIX realizada com sucesso.',
                    'transaction_id' => $transactionId,
                    'external_id' => $externalId,
                    'data' => $data,
                    'raw' => $responseBody,
                ];
            }

            // ❌ Erros conhecidos
            $errorMessages = [
                'NO_FUNDS' => 'Saldo insuficiente para realizar a transferência.',
                'PIX_KEY_NOT_FOUND' => 'Chave PIX não encontrada.',
                'UNAUTHORIZED_IP' => 'IP do servidor não autorizado na SuitPay.',
                'DOCUMENT_VALIDATE' => 'Documento não corresponde à chave PIX.',
                'DUPLICATE_EXTERNAL_ID' => 'External ID já utilizado.',
                'ACCOUNT_DOCUMENTS_NOT_VALIDATED' => 'Conta não validada na SuitPay.',
            ];

            return [
                'success' => false,
                'status' => 'ERROR',
                'http_status' => $response->status(),
                'response_code' => $responseCode,
                'message' => $errorMessages[$responseCode] ?? 'Erro não mapeado. Verifique logs.',
                'transaction_id' => $transactionId,
                'external_id' => $externalId,
                'data' => $data,
                'raw' => $responseBody,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SUITPAY PIX CASHOUT - ERRO DE CONEXÃO', [
                'error' => $e->getMessage(),
                'url' => $this->host() . '/api/v1/gateway/pix-payment',
            ]);

            return [
                'success' => false,
                'status' => 'CONNECTION_ERROR',
                'http_status' => 0,
                'message' => 'Erro de conexão com o gateway de pagamento.',
                'data' => null,
                'raw' => null,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('SUITPAY PIX CASHOUT - EXCEÇÃO GERAL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status' => 'EXCEPTION',
                'http_status' => 0,
                'message' => 'Erro ao processar transferência PIX.',
                'data' => null,
                'raw' => null,
                'error' => $e->getMessage(),
            ];
        }
    }


}
