<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('client_id', 50)->nullable();
            $table->string('cpf_cnpj', 50)->nullable();
            $table->string('fatura_id', 100)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();
            $table->string('created_at_external', 50)->nullable();
            $table->string('updated_at_external', 50)->nullable();
            $table->string('hash_id', 500)->nullable();
            $table->foreignId('lancamento_id')->nullable()->constrained('lancamentos')->cascadeOnDelete();
            $table->string('status', 50)->nullable();
            $table->dateTime('vencimento')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
