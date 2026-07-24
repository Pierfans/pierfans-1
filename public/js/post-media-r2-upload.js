// Upload direto do navegador para o Cloudflare R2.
// O arquivo NAO passa pelo servidor: pedimos uma URL assinada, o browser faz PUT
// nela e depois avisamos o Laravel qual chave gravar no banco.
(function () {
    var ALLOWED = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'webm'];

    function extensionOf(name) {
        return String(name).split('.').pop().toLowerCase();
    }

    function csrf() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.content : '';
    }

    window.postMediaIsAllowedFile = function (name) {
        return ALLOWED.indexOf(extensionOf(name)) !== -1;
    };

    // O content-type entra na assinatura da URL, entao o PUT tem que mandar
    // exatamente o mesmo valor que foi usado pra assinar. Por isso é calculado
    // uma vez só e reaproveitado nos dois passos.
    function contentTypeOf(file) {
        if (file.type) return file.type;
        var ext = extensionOf(file.name);
        var mapa = {
            jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp',
            mp4: 'video/mp4', mov: 'video/quicktime', webm: 'video/webm'
        };
        return mapa[ext] || 'application/octet-stream';
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf()
            },
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().catch(function () {
                return { success: false, message: 'Resposta inválida do servidor.' };
            });
        });
    }

    function putToR2(uploadUrl, file, contentType, onProgress) {
        return new Promise(function (resolve) {
            var xhr = new XMLHttpRequest();
            xhr.open('PUT', uploadUrl, true);
            xhr.setRequestHeader('Content-Type', contentType);

            xhr.upload.onprogress = function (e) {
                if (onProgress && e.lengthComputable) onProgress(e.loaded, e.total);
            };
            xhr.onload = function () {
                resolve(xhr.status >= 200 && xhr.status < 300
                    ? { success: true }
                    : { success: false, message: 'R2 respondeu ' + xhr.status });
            };
            xhr.onerror = function () { resolve({ success: false, message: 'Falha de rede no envio.' }); };
            xhr.ontimeout = function () { resolve({ success: false, message: 'Tempo esgotado no envio.' }); };
            xhr.send(file);
        });
    }

    /**
     * Sobe um arquivo e registra no post. Resolve com {success, message, media}.
     * ponytail: PUT unico, sem multipart. Se a conexao cair no meio, o arquivo
     * inteiro é reenviado (uma vez). Trocar por multipart se isso incomodar em 4G.
     */
    window.postMediaUploadFile = function (file, postId, order, onProgress) {
        var contentType = contentTypeOf(file);
        var tipo = contentType.indexOf('video/') === 0 ? 'video' : 'image';

        // o PUT vai direto pro Cloudflare, entao o servidor nao ve o que deu errado.
        // sem isso, falha de upload some no console do criador.
        function avisarFalha(etapa, mensagem) {
            postJson('/post-media/report-failure', {
                etapa: etapa,
                arquivo: file.name,
                bytes: file.size,
                mensagem: String(mensagem || '').slice(0, 300)
            });
        }

        return postJson('/post-media/request-upload-url', {
            filename: file.name,
            content_type: contentType,
            post_id: postId,
            size: file.size
        }).then(function (res) {
            if (!res.success || !res.upload_url) {
                avisarFalha('url-assinada', res.message);
                return { success: false, message: res.message || 'Não foi possível iniciar o envio.' };
            }

            return putToR2(res.upload_url, file, contentType, onProgress)
                .then(function (envio) {
                    // uma segunda tentativa cobre queda de rede pontual
                    if (envio.success) return envio;
                    avisarFalha('envio-r2-tentativa-1', envio.message);
                    if (onProgress) onProgress(0, file.size);
                    return putToR2(res.upload_url, file, contentType, onProgress);
                })
                .then(function (envio) {
                    if (!envio.success) {
                        avisarFalha('envio-r2', envio.message);
                        return envio;
                    }

                    return postJson('/post-media/confirm-upload', {
                        key: res.key,
                        post_id: postId,
                        filename: file.name,
                        size: file.size,
                        type: tipo,
                        order: order
                    }).then(function (conf) {
                        if (conf.success) return { success: true, media: conf.media };
                        avisarFalha('confirmacao', conf.message);
                        return { success: false, message: conf.message || 'Erro ao registrar a mídia.' };
                    });
                });
        }).catch(function (e) {
            avisarFalha('rede', e && e.message);
            return { success: false, message: 'Erro de rede.' };
        });
    };
})();
