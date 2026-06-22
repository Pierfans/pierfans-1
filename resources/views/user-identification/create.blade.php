<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dados de Identificação - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <script src="/js/app.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <style>
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .form-subtitle {
            font-size: 14px;
            color: #706f6c;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .form-label .required {
            color: #F53003;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E5E5;
            border-radius: 8px;
            font-size: 16px;
            color: #1b1b18;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .form-input.error {
            border-color: #F53003;
        }

        .form-error {
            color: #F53003;
            font-size: 12px;
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .btn-submit {
            background: #FF6B35;
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background: #e55a2b;
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-[#F5F5F5] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation -->
    <x-topnav />

    <!-- Bottom Navigation -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 md:pb-0 pb-16">
        <div class="max-w-3xl mx-auto px-4 lg:px-8 py-8">
            <div class="form-container">
                <h1 class="form-title">Dados de Identificação</h1>
                <p class="form-subtitle">Complete seus dados para prosseguir com o pagamento</p>

                @if(session('error'))
                    <div class="mb-4 p-4 bg-[#fff2f2] border border-[#F53003] rounded-sm">
                        <p class="text-sm text-[#F53003]">{{ session('error') }}</p>
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-sm">
                        <p class="text-sm text-green-800">{{ session('success') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-[#fff2f2] border border-[#F53003] rounded-sm">
                        <ul class="text-sm text-[#F53003]">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('user-identification.store') }}" id="identificationForm">
                    @csrf
                    
                    <input type="hidden" name="plan_id" value="{{ $planId }}">
                    <input type="hidden" name="payment_method" value="{{ $paymentMethod }}">

                    <!-- Dados Pessoais -->
                    <h2 class="form-title" style="font-size: 18px; margin-top: 32px; margin-bottom: 16px;">Dados Pessoais</h2>

                    <div class="form-group">
                        <label class="form-label">
                            Nome Completo <span class="required">*</span>
                        </label>
                        <input type="text" name="name" class="form-input" value="{{ old('name', $identification->name ?? '') }}" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                CPF/CNPJ <span class="required">*</span>
                            </label>
                            <input type="text" name="document" class="form-input" value="{{ old('document', $identification->document ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Telefone <span class="required">*</span>
                            </label>
                            <input type="text" name="phone_number" class="form-input" value="{{ old('phone_number', $identification->phone_number ?? '') }}" required>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <h2 class="form-title" style="font-size: 18px; margin-top: 32px; margin-bottom: 16px;">Endereço</h2>

                    <div class="form-group">
                        <label class="form-label">
                            CEP <span class="required">*</span>
                        </label>
                        <input type="text" name="zip_code" id="zip_code" class="form-input" value="{{ old('zip_code', $identification->zip_code ?? '') }}" required>
                        <div id="cep-error" class="form-error" style="display: none;"></div>
                        <div id="cep-loading" style="display: none; color: #706f6c; font-size: 12px; margin-top: 4px;">
                            Buscando endereço...
                        </div>
                    </div>

                    <!-- Código IBGE oculto -->
                    <input type="hidden" name="cod_ibge" id="cod_ibge" value="{{ old('cod_ibge', $identification->cod_ibge ?? '') }}">

                    <div class="form-group">
                        <label class="form-label">
                            Rua/Logradouro <span class="required">*</span>
                        </label>
                        <input type="text" name="street" class="form-input" value="{{ old('street', $identification->street ?? '') }}" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Número <span class="required">*</span>
                            </label>
                            <input type="text" name="number" class="form-input" value="{{ old('number', $identification->number ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Complemento
                            </label>
                            <input type="text" name="complement" class="form-input" value="{{ old('complement', $identification->complement ?? '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Bairro <span class="required">*</span>
                        </label>
                        <input type="text" name="neighborhood" class="form-input" value="{{ old('neighborhood', $identification->neighborhood ?? '') }}" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Cidade <span class="required">*</span>
                            </label>
                            <input type="text" name="city" class="form-input" value="{{ old('city', $identification->city ?? '') }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Estado (UF) <span class="required">*</span>
                            </label>
                            <input type="text" name="state" class="form-input" maxlength="2" value="{{ old('state', $identification->state ?? '') }}" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" style="margin-top: 32px;">
                        Salvar e Continuar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        // Variáveis para controlar campos editados manualmente
        const manuallyEditedFields = {
            street: false,
            neighborhood: false,
            city: false,
            state: false
        };

        // Função para validar CPF
        function validateCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            
            if (cpf.length !== 11) return false;
            if (/^(\d)\1+$/.test(cpf)) return false; // Todos os dígitos iguais
            
            let sum = 0;
            let remainder;
            
            // Valida primeiro dígito verificador
            for (let i = 1; i <= 9; i++) {
                sum += parseInt(cpf.substring(i-1, i)) * (11 - i);
            }
            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.substring(9, 10))) return false;
            
            // Valida segundo dígito verificador
            sum = 0;
            for (let i = 1; i <= 10; i++) {
                sum += parseInt(cpf.substring(i-1, i)) * (12 - i);
            }
            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.substring(10, 11))) return false;
            
            return true;
        }

        // Máscara para CPF (apenas CPF, não CNPJ)
        const documentInput = document.querySelector('input[name="document"]');
        if (documentInput) {
            documentInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                // Limita a 11 dígitos (apenas CPF)
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                // Aplica máscara CPF: 000.000.000-00
                if (value.length <= 11) {
                    e.target.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }
                
                // Validação visual
                const cpfValue = value;
                if (cpfValue.length === 11) {
                    if (validateCPF(cpfValue)) {
                        e.target.classList.remove('error');
                        const errorDiv = e.target.parentElement.querySelector('.form-error');
                        if (errorDiv) errorDiv.style.display = 'none';
                    } else {
                        e.target.classList.add('error');
                        let errorDiv = e.target.parentElement.querySelector('.form-error');
                        if (!errorDiv) {
                            errorDiv = document.createElement('div');
                            errorDiv.className = 'form-error';
                            e.target.parentElement.appendChild(errorDiv);
                        }
                        errorDiv.textContent = 'CPF inválido';
                        errorDiv.style.display = 'block';
                    }
                } else if (cpfValue.length > 0) {
                    e.target.classList.remove('error');
                    const errorDiv = e.target.parentElement.querySelector('.form-error');
                    if (errorDiv) errorDiv.style.display = 'none';
                }
            });

            // Validação ao perder o foco
            documentInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                if (value.length === 11 && !validateCPF(value)) {
                    e.target.classList.add('error');
                    alert('Por favor, insira um CPF válido.');
                }
            });
        }

        // Máscara para telefone (apenas celular: (00) 00000-0000)
        const phoneInput = document.querySelector('input[name="phone_number"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                // Limita a 11 dígitos (celular)
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                // Aplica máscara celular: (00) 00000-0000
                if (value.length === 0) {
                    e.target.value = '';
                } else if (value.length <= 2) {
                    e.target.value = '(' + value;
                } else if (value.length <= 7) {
                    e.target.value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                } else if (value.length <= 11) {
                    e.target.value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
                }
                
                // Remove mensagem de erro enquanto digita (apenas se tiver 11 dígitos)
                if (value.length === 11) {
                    e.target.classList.remove('error');
                    const errorDiv = e.target.parentElement.querySelector('.form-error');
                    if (errorDiv) errorDiv.style.display = 'none';
                }
            });

            // Validação ao perder o foco
            phoneInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                
                if (value.length === 0) {
                    // Campo vazio - não valida
                    e.target.classList.remove('error');
                    const errorDiv = e.target.parentElement.querySelector('.form-error');
                    if (errorDiv) errorDiv.style.display = 'none';
                    return;
                }
                
                if (value.length !== 11) {
                    e.target.classList.add('error');
                    let errorDiv = e.target.parentElement.querySelector('.form-error');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'form-error';
                        e.target.parentElement.appendChild(errorDiv);
                    }
                    errorDiv.textContent = 'Número de celular incompleto. Digite 11 dígitos.';
                    errorDiv.style.display = 'block';
                } else {
                    // Verifica se começa com 9 (celular)
                    if (value[2] !== '9') {
                        e.target.classList.add('error');
                        let errorDiv = e.target.parentElement.querySelector('.form-error');
                        if (!errorDiv) {
                            errorDiv = document.createElement('div');
                            errorDiv.className = 'form-error';
                            e.target.parentElement.appendChild(errorDiv);
                        }
                        errorDiv.textContent = 'Por favor, insira um número de celular válido (deve começar com 9 após o DDD).';
                        errorDiv.style.display = 'block';
                    } else {
                        e.target.classList.remove('error');
                        const errorDiv = e.target.parentElement.querySelector('.form-error');
                        if (errorDiv) errorDiv.style.display = 'none';
                    }
                }
            });
        }

        // Máscara para CEP e consulta automática via API
        const zipCodeInput = document.getElementById('zip_code');
        if (zipCodeInput) {
            zipCodeInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                // Limita a 8 dígitos
                if (value.length > 8) {
                    value = value.substring(0, 8);
                }
                
                // Aplica máscara CEP: 00000-000
                e.target.value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
                
                // Esconde mensagens de erro anteriores
                document.getElementById('cep-error').style.display = 'none';
            });

            // Consulta CEP ao perder o foco (quando tiver 8 dígitos)
            zipCodeInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/\D/g, '');
                
                if (value.length === 8) {
                    consultarCEP(value);
                } else if (value.length > 0) {
                    document.getElementById('cep-error').textContent = 'CEP deve ter 8 dígitos';
                    document.getElementById('cep-error').style.display = 'block';
                }
            });
        }

        // Função para consultar CEP via ViaCEP
        function consultarCEP(cep) {
            const cepError = document.getElementById('cep-error');
            const cepLoading = document.getElementById('cep-loading');
            cepError.style.display = 'none';
            cepLoading.style.display = 'block';

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    cepLoading.style.display = 'none';

                    if (data.erro) {
                        cepError.textContent = 'CEP não encontrado. Por favor, preencha o endereço manualmente.';
                        cepError.style.display = 'block';
                        return;
                    }

                    // Preenche campos automaticamente (apenas se não foram editados manualmente)
                    if (!manuallyEditedFields.street && data.logradouro) {
                        document.querySelector('input[name="street"]').value = data.logradouro;
                    }
                    if (!manuallyEditedFields.neighborhood && data.bairro) {
                        document.querySelector('input[name="neighborhood"]').value = data.bairro;
                    }
                    if (!manuallyEditedFields.city && data.localidade) {
                        document.querySelector('input[name="city"]').value = data.localidade;
                    }
                    if (!manuallyEditedFields.state && data.uf) {
                        document.querySelector('input[name="state"]').value = data.uf;
                    }
                    if (data.ibge) {
                        document.getElementById('cod_ibge').value = data.ibge;
                    }

                    // Remove classe de erro se houver
                    zipCodeInput.classList.remove('error');
                })
                .catch(error => {
                    cepLoading.style.display = 'none';
                    cepError.textContent = 'Erro ao consultar CEP. Por favor, preencha o endereço manualmente.';
                    cepError.style.display = 'block';
                    console.error('Erro ao consultar CEP:', error);
                });
        }

        // Marca campos como editados manualmente
        document.querySelector('input[name="street"]')?.addEventListener('input', function() {
            manuallyEditedFields.street = true;
        });
        document.querySelector('input[name="neighborhood"]')?.addEventListener('input', function() {
            manuallyEditedFields.neighborhood = true;
        });
        document.querySelector('input[name="city"]')?.addEventListener('input', function() {
            manuallyEditedFields.city = true;
        });
        document.querySelector('input[name="state"]')?.addEventListener('input', function() {
            manuallyEditedFields.state = true;
        });

        // Validação antes de submeter o formulário
        document.getElementById('identificationForm')?.addEventListener('submit', function(e) {
            const documentValue = document.querySelector('input[name="document"]').value.replace(/\D/g, '');
            const phoneValue = document.querySelector('input[name="phone_number"]').value.replace(/\D/g, '');
            const zipCodeValue = document.querySelector('input[name="zip_code"]').value.replace(/\D/g, '');

            // Valida CPF
            if (documentValue.length !== 11 || !validateCPF(documentValue)) {
                e.preventDefault();
                alert('Por favor, insira um CPF válido.');
                document.querySelector('input[name="document"]').focus();
                return false;
            }

            // Valida telefone
            if (phoneValue.length !== 11) {
                e.preventDefault();
                alert('Por favor, insira um número de celular completo (11 dígitos).');
                document.querySelector('input[name="phone_number"]').focus();
                return false;
            }

            // Valida CEP
            if (zipCodeValue.length !== 8) {
                e.preventDefault();
                alert('Por favor, insira um CEP válido (8 dígitos).');
                document.querySelector('input[name="zip_code"]').focus();
                return false;
            }

            // Valida código IBGE (deve estar preenchido)
            const codIbge = document.getElementById('cod_ibge').value;
            if (!codIbge) {
                e.preventDefault();
                alert('CEP inválido ou não encontrado. Por favor, verifique o CEP informado.');
                document.querySelector('input[name="zip_code"]').focus();
                return false;
            }
        });
    </script>
</body>
</html>

