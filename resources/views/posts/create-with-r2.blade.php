{{--
    Example: Create post then upload media to R2 (direct browser → R2).
    Use this as reference; adapt to your existing post create flow.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nova postagem (R2) - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Nova postagem (upload direto R2)</h1>

        {{-- Step 1: Create post (no file through server) --}}
        <form id="createPostForm" class="space-y-4 mb-8">
            @csrf
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm px-3 py-2"></textarea>
            </div>
            <div>
                <label for="visibility" class="block text-sm font-medium text-gray-700">Visibilidade</label>
                <select id="visibility" name="visibility" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="free">Gratuito</option>
                    <option value="subscriber">Assinantes</option>
                </select>
            </div>
            <button type="submit" id="createPostBtn" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white font-medium hover:bg-indigo-700">
                Criar postagem
            </button>
        </form>

        {{-- Step 2: Add media to post (only shown after post is created) --}}
        <div id="uploadSection" class="hidden space-y-4">
            <input type="hidden" id="postId" value="">
            <label class="block text-sm font-medium text-gray-700">Mídias (imagens: jpg, png, webp; vídeos: mp4, mov, webm)</label>
            <input type="file" id="fileInput" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.webm" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:rounded file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700">
            <div id="fileList" class="space-y-2"></div>
            <button type="button" id="uploadAllBtn" class="hidden w-full rounded-md bg-green-600 px-4 py-2 text-white font-medium hover:bg-green-700">
                Enviar todos para R2
            </button>
            <div id="uploadLog" class="text-sm text-gray-600 space-y-1"></div>
            <a id="goToPostLink" href="#" class="hidden inline-block rounded-md bg-gray-200 px-4 py-2 text-gray-800">Ver postagem</a>
        </div>
    </div>

    <script src="/js/post-media-r2-upload.js"></script>
    <script>
        (function () {
            var postIdEl = document.getElementById('postId');
            var uploadSection = document.getElementById('uploadSection');
            var fileInput = document.getElementById('fileInput');
            var fileList = document.getElementById('fileList');
            var uploadAllBtn = document.getElementById('uploadAllBtn');
            var uploadLog = document.getElementById('uploadLog');
            var goToPostLink = document.getElementById('goToPostLink');

            var selectedFiles = [];
            var createdPostId = null;
            var createdPostUsername = null;

            document.getElementById('createPostForm').addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('createPostBtn');
                btn.disabled = true;
                btn.textContent = 'Criando...';

                var fd = new FormData();
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fd.append('description', document.getElementById('description').value);
                fd.append('visibility', document.getElementById('visibility').value);
                fd.append('create_without_media', '1');

                fetch('/posts', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.user_username) {
                        createdPostId = data.post_id || createdPostId;
                        createdPostUsername = data.user_username;
                        postIdEl.value = createdPostId ? String(createdPostId) : '';
                        uploadSection.classList.remove('hidden');
                        uploadLog.innerHTML = '<p class="text-green-600">Post criado. Adicione arquivos e clique em "Enviar todos para R2".</p>';
                    } else {
                        uploadLog.innerHTML = '<p class="text-red-600">' + (data.message || 'Erro ao criar post.') + '</p>';
                    }
                })
                .catch(function () {
                    uploadLog.innerHTML = '<p class="text-red-600">Erro de rede.</p>';
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = 'Criar postagem';
                });
            });

            fileInput.addEventListener('change', function () {
                selectedFiles = [];
                fileList.innerHTML = '';
                var files = Array.from(this.files || []);
                files.forEach(function (file) {
                    if (!window.postMediaIsAllowedFile(file.name)) return;
                    selectedFiles.push(file);
                    var div = document.createElement('div');
                    div.className = 'flex items-center justify-between py-1 border-b border-gray-100';
                    div.innerHTML = '<span class="text-gray-700">' + file.name + '</span> <span class="text-gray-500 text-xs">' + (file.size / 1024).toFixed(1) + ' KB</span>';
                    fileList.appendChild(div);
                });
                uploadAllBtn.classList.toggle('hidden', selectedFiles.length === 0);
            });

            uploadAllBtn.addEventListener('click', function () {
                var postId = parseInt(postIdEl.value, 10) || createdPostId;
                if (!postId) {
                    uploadLog.innerHTML = '<p class="text-red-600">Crie a postagem antes de enviar mídias.</p>';
                    return;
                }
                uploadAllBtn.disabled = true;
                var order = 0;
                var done = 0;
                var total = selectedFiles.length;
                uploadLog.innerHTML = '<p>Enviando ' + total + ' arquivo(s)...</p>';

                function next() {
                    if (done >= total) {
                        uploadAllBtn.disabled = false;
                        uploadLog.innerHTML += '<p class="text-green-600">Todos os arquivos foram enviados.</p>';
                        if (createdPostUsername) {
                            goToPostLink.href = '/' + createdPostUsername;
                            goToPostLink.classList.remove('hidden');
                        }
                        return;
                    }
                    var file = selectedFiles[done];
                    window.postMediaUploadFile(file, postId, order++, function (loaded, totalBytes) {
                        var pct = totalBytes ? Math.round((loaded / totalBytes) * 100) : 0;
                        uploadLog.querySelector('p').textContent = 'Enviando ' + (done + 1) + '/' + total + ': ' + file.name + ' (' + pct + '%)';
                    }).then(function (result) {
                        if (result.success) {
                            uploadLog.innerHTML += '<p class="text-green-600">OK: ' + file.name + '</p>';
                        } else {
                            uploadLog.innerHTML += '<p class="text-red-600">Erro ' + file.name + ': ' + (result.message || '') + '</p>';
                        }
                        done++;
                        next();
                    });
                }
                next();
            });
        })();
    </script>
    <x-whatsapp-float :clear-mobile-nav="false" />
</body>
</html>
