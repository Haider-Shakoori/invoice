<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('whatsapp')->nullable();
            $table->string('address')->nullable();
            $table->string('city_province')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('prefix', 20)->default('INV');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();
        });

        Schema::create('invoice_drafts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_template_id')->nullable()->constrained('invoice_templates')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('duplicated_from_id')->nullable()->constrained('invoice_drafts')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->string('locale', 5)->default('en');
            $table->string('currency', 3)->default('AFN');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->json('customer_snapshot');
            $table->json('company_snapshot');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 18, 4)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->unsignedInteger('version_no')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'issue_date']);
        });

        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_draft_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('description');
            $table->decimal('quantity', 18, 3);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_percent', 7, 4)->default(0);
            $table->decimal('line_subtotal', 18, 2);
            $table->decimal('line_discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['invoice_draft_id', 'position']);
        });

        Schema::create('invoice_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_draft_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['invoice_draft_id', 'version']);
        });

        Schema::create('document_activity', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_draft_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event')->index();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['invoice_draft_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_activity');
        Schema::dropIfExists('invoice_versions');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoice_drafts');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('customers');
    }
};
