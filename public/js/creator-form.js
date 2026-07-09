// Creator Form Multi-Step Handler
(function() {
    let currentStep = 1;
    const totalSteps = 3;

    // Inicialização
    $(document).ready(function() {
        loadSavedData();
        updateStepDisplay();
        
        // Event listeners
        $('#nextBtn').on('click', function() {
            if (validateStep(currentStep)) {
                saveStep(currentStep).then(function() {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        updateStepDisplay();
                    }
                });
            }
        });

        $('#prevBtn').on('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        });

        // Preview de imagens ao selecionar
        $('#creator_document_front').on('change', function() {
            previewImage(this, 'preview_front');
        });

        $('#creator_document_back').on('change', function() {
            previewImage(this, 'preview_back');
        });

        $('#creator_selfie').on('change', function() {
            previewImage(this, 'preview_selfie');
        });

        // Máscaras
        $('#creator_cpf').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        $('#creator_zipcode').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        // Autofill de endereço via ViaCEP (grátis, sem chave). Ao completar 8 dígitos.
        $('#creator_zipcode').on('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length !== 8) return;
            $.ajax({
                url: `https://viacep.com.br/ws/${cep}/json/`,
                type: 'GET',
                timeout: 6000,
                success: function(data) {
                    // CEP inexistente ({erro:true}): deixa o usuário preencher na mão.
                    if (!data || data.erro) return;
                    if (data.logradouro) $('#creator_address').val(data.logradouro);
                    if (data.bairro) $('#creator_neighborhood').val(data.bairro);
                    if (data.localidade) $('#creator_city').val(data.localidade);
                    if (data.uf) $('#creator_state').val(data.uf.toUpperCase());
                }
                // timeout/correios fora do ar: silencioso, não trava o passo (preenche manual)
            });
        });

        $('#creator_state').on('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '').substring(0, 2);
        });
    });

    // Preview de imagem
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(`#${previewId}`).html(`
                    <img src="${e.target.result}" class="max-w-xs rounded-lg border border-gray-300" alt="Preview">
                `);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Atualiza a exibição do step atual
    function updateStepDisplay() {
        // Esconde todos os steps
        $('.step-content').addClass('hidden');
        
        // Mostra o step atual
        $(`#step${currentStep}`).removeClass('hidden');
        
        // Atualiza indicadores
        $('.step-indicator').each(function() {
            const stepNum = parseInt($(this).data('step'));
            const circle = $(this).find('div:first');
            const text = $(this).find('span');
            
            if (stepNum < currentStep) {
                circle.removeClass('bg-gray-300 text-gray-600').addClass('bg-green-500 text-white');
                text.removeClass('text-gray-500').addClass('text-gray-700');
            } else if (stepNum === currentStep) {
                circle.removeClass('bg-gray-300 text-gray-600').addClass('bg-pink-500 text-white');
                text.removeClass('text-gray-500').addClass('text-gray-700 font-semibold');
            } else {
                circle.removeClass('bg-pink-500 bg-green-500 text-white').addClass('bg-gray-300 text-gray-600');
                text.removeClass('text-gray-700 font-semibold').addClass('text-gray-500');
            }
        });
        
        // Atualiza barra de progresso
        const progress = (currentStep / totalSteps) * 100;
        $('#progressBar').css('width', progress + '%');
        
        // Atualiza botões
        $('#prevBtn').toggleClass('hidden', currentStep === 1);
        $('#nextBtn').toggleClass('hidden', currentStep === totalSteps);
        $('#submitBtn').toggleClass('hidden', currentStep !== totalSteps);
    }

    // Valida um step específico
    function validateStep(step) {
        let isValid = true;
        const stepElement = $(`#step${step}`);
        
        // Limpa mensagens de erro anteriores
        stepElement.find('.error-message').text('').addClass('hidden');
        stepElement.find('input, select').removeClass('border-red-500');
        
        // Valida campos obrigatórios
        stepElement.find('[required]').each(function() {
            const field = $(this);
            
            // Para campos de arquivo
            if (field.attr('type') === 'file') {
                if (!field[0].files || field[0].files.length === 0) {
                    showError(field.attr('id'), 'Este documento é obrigatório');
                    isValid = false;
                }
            }
            // Para outros campos
            else if (!field.val() || field.val().trim() === '') {
                showError(field.attr('id'), 'Este campo é obrigatório');
                isValid = false;
            }
        });
        
        // Passo 1: CPF precisa ter dígito verificador válido (pega erro de digitação antes do Didit).
        if (parseInt(step) === 1) {
            const cpf = $('#creator_cpf').val();
            if (cpf && !isValidCpf(cpf)) {
                showError('creator_cpf', 'CPF inválido — confira os números');
                isValid = false;
            }
        }

        return isValid;
    }

    // Valida os dois dígitos verificadores do CPF. Rejeita repetidos (111.111.111-11).
    function isValidCpf(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        let sum = 0;
        for (let i = 0; i < 9; i++) sum += parseInt(cpf[i]) * (10 - i);
        let d1 = (sum * 10) % 11;
        if (d1 === 10) d1 = 0;
        if (d1 !== parseInt(cpf[9])) return false;
        sum = 0;
        for (let i = 0; i < 10; i++) sum += parseInt(cpf[i]) * (11 - i);
        let d2 = (sum * 10) % 11;
        if (d2 === 10) d2 = 0;
        return d2 === parseInt(cpf[10]);
    }

    function showError(fieldId, message) {
        const field = $(`#${fieldId}`);
        field.addClass('border-red-500');
        field.siblings('.error-message').text(message).removeClass('hidden');
    }

    // Salva um step
    function saveStep(step) {
        return new Promise(function(resolve, reject) {
            const formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            
            // Step 4: Upload de documentos
            if (parseInt(step) === 4) {
                // Desabilita botão
                $('#submitBtn').prop('disabled', true).text('Enviando documentos...');

                // Adiciona os arquivos ao FormData
                const docFront = $('#creator_document_front')[0].files[0];
                const docBack = $('#creator_document_back')[0].files[0];
                const selfie = $('#creator_selfie')[0].files[0];

                if (docFront) formData.append('creator_document_front', docFront);
                if (docBack) formData.append('creator_document_back', docBack);
                if (selfie) formData.append('creator_selfie', selfie);
            } else {
                // Steps 1-3: dados simples
                $(`#step${step} input, #step${step} select`).each(function() {
                    if ($(this).attr('type') !== 'file') {
                        formData.append($(this).attr('name'), $(this).val());
                    }
                });
            }
            
            $.ajax({
                url: `/creator/save-step/${step}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (parseInt(step) === 4) {
                            // Último step - redireciona
                            window.location.href = response.redirect || '/creator?status=pending';
                        } else {
                            resolve();
                        }
                    } else {
                        alert(response.message || 'Erro ao salvar dados');
                        $('#submitBtn').prop('disabled', false).text('Enviar Documentos');
                        reject();
                    }
                },
                error: function(xhr) {
                    $('#submitBtn').prop('disabled', false).text('Enviar Documentos');
                    
                    const errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function(field) {
                        showError(field, errors[field][0]);
                    });
                    
                    if (xhr.responseJSON?.message) {
                        alert(xhr.responseJSON.message);
                    }
                    reject();
                }
            });
        });
    }

    // Carrega dados salvos
    function loadSavedData() {
        $.ajax({
            url: '/creator/get-data',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    // Preenche os campos com dados salvos
                    Object.keys(response.data).forEach(function(stepKey) {
                        const stepData = response.data[stepKey];
                        Object.keys(stepData).forEach(function(field) {
                            const input = $(`#${field}`);
                            if (input.length && input.attr('type') !== 'file') {
                                input.val(stepData[field] || '');
                            }
                            
                            // Se for um campo de documento e tiver URL, exibe preview
                            if (field === 'creator_document_front' && stepData[field]) {
                                $('#preview_front').html(`<img src="${stepData[field]}" class="max-w-xs rounded-lg border border-gray-300" alt="Preview">`);
                            } else if (field === 'creator_document_back' && stepData[field]) {
                                $('#preview_back').html(`<img src="${stepData[field]}" class="max-w-xs rounded-lg border border-gray-300" alt="Preview">`);
                            } else if (field === 'creator_selfie' && stepData[field]) {
                                $('#preview_selfie').html(`<img src="${stepData[field]}" class="max-w-xs rounded-lg border border-gray-300" alt="Preview">`);
                            }
                        });
                    });
                }
            }
        });
    }

    // Submit do formulário (Step 4): inicia a verificação de identidade na Didit
    $('#creatorForm').on('submit', function(e) {
        e.preventDefault();
        startVerification();
    });

    function startVerification() {
        const $btn = $('#submitBtn');
        $btn.prop('disabled', true).text('Abrindo verificação...');

        $.ajax({
            url: '/creator/verificar',
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success && response.url) {
                    window.location.href = response.url;
                } else {
                    alert(response.message || 'Erro ao iniciar a verificação');
                    $btn.prop('disabled', false).text('Verificar identidade');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Erro ao iniciar a verificação');
                $btn.prop('disabled', false).text('Verificar identidade');
            }
        });
    }
})();
