<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_drafts', function (Blueprint $table): void {
            $table->string('tax_label')->nullable()->after('discount_amount');
            $table->decimal('tax_rate', 7, 4)->default(0)->after('tax_label');
            $table->decimal('tax_amount', 18, 2)->default(0)->after('tax_rate');
            $table->string('additional_charge_label')->nullable()->after('tax_amount');
            $table->decimal('additional_charge_amount', 18, 2)->default(0)->after('additional_charge_label');
        });

        Schema::create('document_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_template_id')->nullable()->constrained('invoice_templates')->nullOnDelete();
            $table->foreignId('exported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('locale', 5);
            $table->string('format', 10)->default('pdf');
            $table->string('filename');
            $table->string('storage_path');
            $table->string('sha256', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['invoice_draft_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_exports');

        Schema::table('invoice_drafts', function (Blueprint $table): void {
            $table->dropColumn([
                'tax_label',
                'tax_rate',
                'tax_amount',
                'additional_charge_label',
                'additional_charge_amount',
            ]);
        });
    }
};
