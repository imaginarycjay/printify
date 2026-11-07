<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('platform:admin {email}', function (): void {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user || ! $user->isCustomer() || $user->orders()->exists()) {
        $this->error('Create a dedicated customer account with no orders first; existing owners and staff cannot be promoted.');

        return;
    }
    $user->update(['role' => User::ROLE_ADMIN]);
    $this->info('Platform administrator provisioned.');
})->purpose('Promote a dedicated existing account to platform administrator');

Artisan::command('platform:privatize-files', function (): void {
    $paths = Order::whereNotNull('payment_proof_path')->pluck('payment_proof_path')
        ->merge(OrderItem::whereNotNull('document_file_path')->pluck('document_file_path'))->unique();
    foreach ($paths as $path) {
        if (! is_string($path) || str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
            continue;
        }
        $contents = Storage::disk('public')->get($path);
        if (! Storage::disk('local')->exists($path)) {
            if (! Storage::disk('local')->put($path, $contents)) {
                throw new RuntimeException('Could not privatize '.$path);
            }
        }
        if (Storage::disk('local')->get($path) !== $contents) {
            throw new RuntimeException('Private file verification failed: '.$path);
        }
        Storage::disk('public')->delete($path);
    }
    $this->info('Referenced order files are now private.');
})->purpose('Move existing order documents and payment proofs from public to private storage');
