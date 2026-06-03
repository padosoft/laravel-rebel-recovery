<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rebel_recovery_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('tenant_id')->nullable();
            $table->string('subject_type');
            $table->string('subject_id');

            // Keyed HMAC of (salt|code); the plaintext code is shown ONCE at generation.
            $table->string('code_hmac', 128);
            $table->string('salt', 64);
            $table->unsignedTinyInteger('key_version');

            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rebel_recovery_codes');
    }
};
