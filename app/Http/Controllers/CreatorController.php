<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class CreatorController extends Controller
{
    /**
     * Mostra a página de tornar-se criador
     */
    public function index()
    {
        $user = Auth::user();
        
        // Se já aprovado, redireciona
        if ($user->creator_status === 'approved') {
            return redirect()->route('dashboard')->with('success', 'Você já é um criador aprovado!');
        }
        
        // Se pendente, mostra mensagem
        if ($user->creator_status === 'pending') {
            return view('creator.index', [
                'status' => 'pending',
                'message' => 'Seus documentos estão em análise.',
            ]);
        }
        
        // Se rejeitado, mostra mensagem e permite reenvio
        if ($user->creator_status === 'rejected') {
            return view('creator.index', [
                'status' => 'rejected',
                'message' => 'Seu cadastro foi recusado, envie novamente.',
                'user' => $user,
            ]);
        }
        
        // Se nenhum, mostra formulário
        return view('creator.index', [
            'status' => 'none',
            'user' => $user,
        ]);
    }

    /**
     * Salva dados de um step específico
     */
    public function saveStep(Request $request, $step)
    {
        $user = Auth::user();
        
        // Não permite editar se já está pendente ou aprovado
        if (in_array($user->creator_status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode editar seus dados neste momento.',
            ], 403);
        }

        $validated = [];

        switch ($step) {
            case 1: // Dados pessoais
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'creator_full_name' => 'required|string|max:255',
                    'creator_cpf' => 'required|string|size:11|regex:/^[0-9]{11}$/',
                    'creator_birth_date' => 'required|date|before:today',
                    'creator_phone' => 'required|string|max:20',
                ]);
                break;

            case 2: // Endereço
                $validated = $request->validate([
                    'creator_zipcode' => 'required|string|size:8|regex:/^[0-9]{8}$/',
                    'creator_address' => 'required|string|max:255',
                    'creator_address_number' => 'required|string|max:20',
                    'creator_address_complement' => 'nullable|string|max:255',
                    'creator_neighborhood' => 'required|string|max:255',
                    'creator_city' => 'required|string|max:255',
                    'creator_state' => 'required|string|size:2',
                ]);
                break;

            case 3: // Dados bancários
                $validated = $request->validate([
                    'creator_bank_name' => 'required|string|max:255',
                    'creator_bank_agency' => 'required|string|max:20',
                    'creator_bank_account' => 'required|string|max:20',
                    'creator_bank_account_type' => ['required', Rule::in(['checking', 'savings'])],
                    'creator_pix_key' => 'required|string|max:255',
                ]);
                break;

            case 4: // Documentos
                $validated = $request->validate([
                    'creator_document_front' => 'required|image|mimes:jpeg,jpg,png,heic,heif|max:307200',
                    'creator_document_back' => 'required|image|mimes:jpeg,jpg,png,heic,heif|max:307200',
                    'creator_selfie' => 'required|image|mimes:jpeg,jpg,png,heic,heif|max:307200',
                ]);
                
                $uploadErrors = [];
                
                // Processa upload tradicional
                if ($request->hasFile('creator_document_front')) {
                    $uploadedFile = $this->saveDocumentLocally($request->file('creator_document_front'));
                    if ($uploadedFile) {
                        $validated['creator_document_front'] = $uploadedFile;
                    } else {
                        $uploadErrors[] = 'Erro ao fazer upload do documento frente.';
                    }
                }
                
                if ($request->hasFile('creator_document_back')) {
                    $uploadedFile = $this->saveDocumentLocally($request->file('creator_document_back'));
                    if ($uploadedFile) {
                        $validated['creator_document_back'] = $uploadedFile;
                    } else {
                        $uploadErrors[] = 'Erro ao fazer upload do documento verso.';
                    }
                }
                
                if ($request->hasFile('creator_selfie')) {
                    $uploadedFile = $this->saveDocumentLocally($request->file('creator_selfie'));
                    if ($uploadedFile) {
                        $validated['creator_selfie'] = $uploadedFile;
                    } else {
                        $uploadErrors[] = 'Erro ao fazer upload da selfie.';
                    }
                }
                
                // Se houve erros no upload, retorna erro
                if (!empty($uploadErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao processar documentos.',
                        'errors' => $uploadErrors,
                    ], 500);
                }
                
                // Marca como pendente, salva data de submissão e encerra o onboarding
                $validated['creator_status'] = 'pending';
                $validated['creator_submitted_at'] = now();
                $validated['creator_onboarding'] = false;
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Step inválido.',
                ], 400);
        }

        // Atualiza o usuário
        $user->update($validated);

        // Etapa 4 concluída: encerra sessão e redireciona conforme verificação de e-mail
        if ((int) $step === 4) {
            $emailVerified = $user->fresh()->email_verified_at !== null;

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            // Verificação de e-mail desativada: já entra verificada, pula a tela de verificar
            if ($emailVerified) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cadastro enviado! Aguarde a aprovação da equipe PierFans.',
                    'redirect' => route('login'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cadastro enviado! Verifique seu e-mail e aguarde a aprovação da equipe PierFans.',
                'redirect' => '/email/verify?email=' . urlencode($user->email),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dados salvos com sucesso!',
            'step' => $step,
        ]);
    }

    /**
     * Retorna os dados salvos do usuário
     */
    public function getData()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'step1' => [
                    // 'name' (Nome de exibição) não é retornado: o usuário deve preencher sempre
                    'creator_full_name' => $user->creator_full_name,
                    'creator_cpf' => $user->creator_cpf,
                    'creator_birth_date' => $user->creator_birth_date,
                    'creator_phone' => $user->creator_phone,
                ],
                'step2' => [
                    'creator_zipcode' => $user->creator_zipcode,
                    'creator_address' => $user->creator_address,
                    'creator_address_number' => $user->creator_address_number,
                    'creator_address_complement' => $user->creator_address_complement,
                    'creator_neighborhood' => $user->creator_neighborhood,
                    'creator_city' => $user->creator_city,
                    'creator_state' => $user->creator_state,
                ],
                'step3' => [
                    'creator_bank_name' => $user->creator_bank_name,
                    'creator_bank_agency' => $user->creator_bank_agency,
                    'creator_bank_account' => $user->creator_bank_account,
                    'creator_bank_account_type' => $user->creator_bank_account_type,
                    'creator_pix_key' => $user->creator_pix_key,
                ],
                'step4' => [
                    'creator_document_front' => $user->creator_document_front_url,
                    'creator_document_back' => $user->creator_document_back_url,
                    'creator_selfie' => $user->creator_selfie_url,
                ],
            ],
        ]);
    }

    /**
     * Inicia a verificacao de identidade na Didit (substitui o upload manual do step 4).
     * Cria a sessao, marca o cadastro como pendente e devolve a URL pro front redirecionar.
     */
    public function startVerification(\App\Services\DiditService $didit)
    {
        $user = Auth::user();

        // Ja aprovado ou aguardando resultado: nao inicia de novo.
        if (in_array($user->creator_status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Voce nao pode iniciar a verificacao neste momento.',
            ], 403);
        }

        // Precisa dos dados dos passos anteriores pra cruzar com o documento depois.
        if (! $user->creator_cpf || ! $user->creator_birth_date) {
            return response()->json([
                'success' => false,
                'message' => 'Preencha seus dados antes de verificar a identidade.',
            ], 422);
        }

        try {
            $session = $didit->createSession($user, route('creator.index'));
        } catch (\Throwable $e) {
            \Log::error('Didit: falha ao criar sessao', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Nao foi possivel iniciar a verificacao agora. Tente novamente em instantes.',
            ], 502);
        }

        // Marca como pendente ja no inicio; o webhook da Didit decide aprovado/rejeitado.
        // ponytail: se o usuario abandonar, so destrava quando a Didit mandar Abandoned/Expired
        // (ate 7 dias). Casos raros: admin reseta pra 'none'.
        $user->update([
            'didit_session_id' => $session['session_id'],
            'didit_status' => 'Not Started',
            'creator_status' => 'pending',
            'creator_submitted_at' => now(),
            'creator_onboarding' => false,
        ]);

        return response()->json([
            'success' => true,
            'url' => $session['url'],
        ]);
    }

    /**
     * Salva um documento localmente na pasta public/_files_/documents/
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|null Retorna o nome do arquivo salvo ou null em caso de erro
     */
    private function saveDocumentLocally($file)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $destinationPath = public_path('_files_/documents');
            
            $file->move($destinationPath, $fileName);
            
            \Log::info('Documento salvo localmente', [
                'original_name' => $file->getClientOriginalName(),
                'saved_name' => $fileName
            ]);
            
            return $fileName;
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar documento localmente: ' . $e->getMessage());
            return null;
        }
    }
}
