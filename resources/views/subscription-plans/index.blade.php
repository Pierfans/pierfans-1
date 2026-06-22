<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configurar Planos de Assinatura - {{ config('app.name', 'Laravel') }}</title>

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
        .plan-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .plan-title {
            font-size: 18px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .plan-description {
            font-size: 14px;
            color: #706f6c;
            margin-bottom: 20px;
        }

        .plan-label {
            font-size: 14px;
            font-weight: 500;
            color: #1b1b18;
            margin-bottom: 8px;
            display: block;
        }

        .plan-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #E5E5E5;
            border-radius: 8px;
            font-size: 16px;
            color: #1b1b18;
            margin-bottom: 20px;
        }

        .plan-input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .plan-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .plan-status-label {
            font-size: 14px;
            font-weight: 500;
            color: #1b1b18;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #E5E5E5;
            transition: 0.3s;
            border-radius: 28px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #FF6B35;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }

        .save-button {
            background-color: #FF6B35;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 30px auto 0;
            transition: background-color 0.2s;
        }

        .save-button:hover {
            background-color: #e55a2b;
        }

        .save-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            padding: 32px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 16px;
        }

        .modal-message {
            font-size: 16px;
            color: #706f6c;
            margin-bottom: 24px;
        }

        .modal-button {
            background-color: #FF6B35;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        .modal-button:hover {
            background-color: #e55a2b;
        }
    </style>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation -->
    <x-topnav />

    <!-- Bottom Navigation -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#1b1b18] mb-2">Configurar Planos de Assinatura</h1>
                <p class="text-[#706f6c]">Configure os preços e ative ou desative seus planos de assinatura</p>
            </div>

            <form id="plansForm">
                @csrf
                @foreach($plans as $plan)
                    <div class="plan-card">
                        <h3 class="plan-title">{{ $plan->name }}</h3>
                        <p class="plan-description">{{ $plan->duration_days }} dias de acesso</p>
                        
                        <label class="plan-label" for="price_{{ $plan->id }}">Preço</label>
                        <input 
                            type="text" 
                            id="price_{{ $plan->id }}" 
                            name="plans[{{ $plan->id }}][price]" 
                            class="plan-input price-input" 
                            value="R$ {{ number_format($plan->price, 2, ',', '.') }}"
                            data-plan-id="{{ $plan->id }}"
                            placeholder="R$ 0,00"
                        >
                        <input type="hidden" name="plans[{{ $plan->id }}][id]" value="{{ $plan->id }}">
                        
                        <div class="plan-status">
                            <span class="plan-status-label">
                                {{ $plan->is_active ? 'Plano ativo' : 'Plano inativo' }}
                            </span>
                            <label class="toggle-switch">
                                <input 
                                    type="checkbox" 
                                    name="plans[{{ $plan->id }}][is_active]" 
                                    value="1"
                                    {{ $plan->is_active ? 'checked' : '' }}
                                >
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="save-button" id="saveButton">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar Planos
                </button>
            </form>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Sucesso!</h3>
            <p class="modal-message">Planos salvos com sucesso!</p>
            <button class="modal-button" onclick="closeModal()">OK</button>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        // Função para formatar moeda BRL
        function formatCurrency(value) {
            if (!value || value === '') {
                return 'R$ 0,00';
            }
            
            // Remove tudo que não é número
            let numbers = value.replace(/\D/g, '');
            
            if (numbers === '') {
                return 'R$ 0,00';
            }
            
            // Converte para centavos e depois formata
            let formatted = (parseInt(numbers) / 100).toFixed(2);
            formatted = formatted.replace('.', ',');
            formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            return 'R$ ' + formatted;
        }

        // Formatação automática de moeda BRL ao carregar
        document.querySelectorAll('.price-input').forEach(function(input) {
            // Formata o valor inicial se não estiver formatado
            if (input.value && !input.value.includes('R$')) {
                input.value = formatCurrency(input.value);
            }
            
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value === '') {
                    e.target.value = '';
                    return;
                }
                
                e.target.value = formatCurrency(value);
            });

            input.addEventListener('blur', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = formatCurrency(value);
            });
        });

        // Submit do formulário
        document.getElementById('plansForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const plans = {};
            
            // Converte FormData para objeto
            for (let [key, value] of formData.entries()) {
                const match = key.match(/plans\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const planId = match[1];
                    const field = match[2];
                    
                    if (!plans[planId]) {
                        plans[planId] = {};
                    }
                    
                    if (field === 'is_active') {
                        plans[planId][field] = '1';
                    } else {
                        plans[planId][field] = value;
                    }
                }
            }
            
            // Garante que todos os planos tenham is_active definido
            document.querySelectorAll('input[name*="[is_active]"]').forEach(function(checkbox) {
                const match = checkbox.name.match(/plans\[(\d+)\]/);
                if (match) {
                    const planId = match[1];
                    if (!plans[planId] || !plans[planId].hasOwnProperty('is_active')) {
                        if (!plans[planId]) {
                            plans[planId] = {};
                        }
                        plans[planId].is_active = '0';
                    }
                }
            });
            
            // Converte para array
            const plansArray = Object.values(plans);
            
            // Prepara dados para envio
            const data = {
                _token: document.querySelector('input[name="_token"]').value,
                plans: plansArray
            };
            
            // Desabilita o botão
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.textContent = 'Salvando...';
            
            // Envia requisição
            fetch('{{ route("subscription-plans.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showModal();
                } else {
                    alert('Erro ao salvar planos. Tente novamente.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao salvar planos. Tente novamente.');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar Planos
                `;
            });
        });

        function showModal() {
            document.getElementById('successModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('successModal').classList.remove('active');
        }

        // Fecha modal ao clicar fora
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

