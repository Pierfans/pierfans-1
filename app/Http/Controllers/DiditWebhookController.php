<?php

namespace App\Http\Controllers;

use App\Mail\CreatorStatusMail;
use App\Models\User;
use App\Services\DiditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DiditWebhookController extends Controller
{
    public function handle(Request $request, DiditService $didit)
    {
        $raw = $request->getContent();

        if (! $didit->verifyWebhook($raw, $request->header('X-Signature'), $request->header('X-Timestamp'))) {
            Log::warning('Didit webhook: assinatura invalida');
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $payload = json_decode($raw, true) ?: [];
        $status = $payload['status'] ?? null;
        $vendorData = $payload['vendor_data'] ?? null;
        $sessionId = $payload['session_id'] ?? null;
        $decision = $payload['decision'] ?? [];

        // Sempre 200 quando nao ha o que fazer, pra Didit nao reenviar em loop.
        $user = User::withoutGlobalScope('active')->find($vendorData);
        if (! $user) {
            Log::warning('Didit webhook: usuario nao encontrado', ['vendor_data' => $vendorData]);
            return response()->json(['message' => 'ok'], 200);
        }

        // Webhook de uma sessao antiga (o usuario ja recomecou outra): ignora.
        if ($user->didit_session_id && $sessionId && $user->didit_session_id !== $sessionId) {
            return response()->json(['message' => 'ignored'], 200);
        }

        $this->applyDecision($user, $status, is_array($decision) ? $decision : []);

        return response()->json(['message' => 'ok'], 200);
    }

    private function applyDecision(User $user, ?string $status, array $decision): void
    {
        $idv = $decision['id_verification'] ?? [];
        $age = $idv['age'] ?? $this->ageFromDob($idv['date_of_birth'] ?? null);

        $extractedCpf = preg_replace('/\D/', '', (string) ($idv['extra_fields']['tax_number'] ?? $idv['personal_number'] ?? ''));
        $typedCpf = preg_replace('/\D/', '', (string) $user->creator_cpf);
        $cpfMatches = $extractedCpf !== '' && $extractedCpf === $typedCpf;

        $snapshot = [
            'status' => $status,
            'age' => $age,
            'date_of_birth' => $idv['date_of_birth'] ?? null,
            'full_name' => $idv['full_name'] ?? null,
            'extracted_cpf' => $extractedCpf ?: null,
            'cpf_matches' => $cpfMatches,
            'id_verification' => $idv['status'] ?? null,
            'liveness' => $decision['liveness']['status'] ?? null,
            'face_match' => $decision['face_match']['status'] ?? null,
        ];

        $update = [
            'didit_status' => $status,
            'didit_decision' => $snapshot,
            'didit_verified_at' => now(),
        ];

        // Deixou a verificacao pela metade: volta pra 'none' pra poder tentar de novo.
        if (in_array($status, ['Abandoned', 'Expired', 'Kyc Expired'], true)) {
            $update['creator_status'] = 'none';
            $update['didit_verified_at'] = null;
            $user->update($update);
            return;
        }

        $approved = false;

        if ($status === 'Approved') {
            if ($age !== null && $age < 18) {
                $update['creator_status'] = 'rejected'; // menor de 18: barra sempre
            } elseif ($cpfMatches && $age !== null && $age >= 18) {
                $update['creator_status'] = 'approved';
                $approved = true;
                if (! $user->username) {
                    $update['username'] = $this->uniqueUsername();
                }
            } else {
                // Aprovou na Didit mas o CPF nao bate (ou idade incerta): humano decide no admin.
                $update['creator_status'] = 'pending';
            }
        } elseif ($status === 'Declined') {
            $update['creator_status'] = 'rejected';
        } else {
            // In Review / In Progress / Awaiting User / Resubmitted: continua pendente.
            $update['creator_status'] = 'pending';
        }

        $user->update($update);

        if ($approved) {
            $this->notify($user);
        }
    }

    private function ageFromDob(?string $dob): ?int
    {
        if (! $dob) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($dob)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ponytail: username + notificacao duplicam AdminCreatorController (aprovacao manual).
    // Extrair pra um servico/trait de aprovacao se aparecer um 3o caminho de aprovacao.
    private function uniqueUsername(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $username = Str::random(8);
            if (! User::withoutGlobalScope('active')->where('username', $username)->exists()) {
                return $username;
            }
        }
        return Str::random(6) . time();
    }

    private function notify(User $user): void
    {
        try {
            Mail::to($user->email)->send(new CreatorStatusMail($user, 'approved'));
        } catch (\Exception $e) {
            Log::error('Didit webhook: falha ao enviar email de aprovacao', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
