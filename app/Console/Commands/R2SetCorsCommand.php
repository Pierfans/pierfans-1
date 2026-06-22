<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;

class R2SetCorsCommand extends Command
{
    protected $signature = 'r2:set-cors 
                            {--origins=* : Origens permitidas (ex: http://127.0.0.1:8000 https://seudominio.com)}';

    protected $description = 'Aplica política CORS no bucket R2 para permitir upload direto do navegador';

    public function handle(): int
    {
        $bucket = config('filesystems.disks.r2.bucket');
        $endpoint = config('filesystems.disks.r2.endpoint');

        if (empty($bucket) || empty($endpoint)) {
            $this->error('Configure R2_BUCKET e R2_ENDPOINT no .env');
            return self::FAILURE;
        }

        $origins = $this->option('origins');
        if (empty($origins) || (is_array($origins) && count($origins) === 0)) {
            $origins = [
                'http://127.0.0.1:8000',
                'http://localhost:8000',
                'http://localhost',
            ];
            $this->line('Usando origens padrão: ' . implode(', ', $origins));
        }

        if (is_string($origins)) {
            $origins = array_map('trim', explode(',', $origins));
        }

        $config = config('filesystems.disks.r2');
        $client = new S3Client([
            'version' => 'latest',
            'region' => $config['region'],
            'endpoint' => $config['endpoint'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
        ]);

        $cors = [
            'CORSRules' => [
                [
                    'AllowedHeaders' => ['Content-Type', 'Content-Length', 'Content-Range'],
                    'AllowedMethods' => ['GET', 'PUT', 'HEAD'],
                    'AllowedOrigins' => $origins,
                    'ExposeHeaders' => ['ETag', 'Content-Length'],
                    'MaxAgeSeconds' => 3600,
                ],
            ],
        ];

        try {
            $client->putBucketCors([
                'Bucket' => $bucket,
                'CORSConfiguration' => $cors,
            ]);
            $this->info('CORS aplicado no bucket "' . $bucket . '" com sucesso.');
            $this->line('Origens: ' . implode(', ', $origins));
            $this->line('Aguarde até 30 segundos para propagar.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Erro: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
