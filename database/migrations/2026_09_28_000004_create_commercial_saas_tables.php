<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('trial_days')->default(7);
            $table->unsignedInteger('term_months')->default(12);
            $table->unsignedBigInteger('setup_fee_afn')->default(0);
            $table->unsignedBigInteger('first_term_fee_afn')->default(0);
            $table->unsignedBigInteger('renewal_fee_afn')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('sellers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('commission_type')->default('percent');
            $table->decimal('commission_rate', 8, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('trialing')->index();
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable()->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable()->index();
            $table->timestamp('grace_ends_at')->nullable()->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('commercial_sequences', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        Schema::create('platform_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('issued')->index();
            $table->string('currency', 3)->default('AFN');
            $table->unsignedBigInteger('subtotal_afn');
            $table->unsignedBigInteger('discount_afn')->default(0);
            $table->unsignedBigInteger('total_afn');
            $table->timestamp('issued_at')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('description');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->unsignedBigInteger('unit_price_afn');
            $table->unsignedBigInteger('amount_afn');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['platform_invoice_id', 'position']);
        });

        Schema::create('platform_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('platform_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('method')->index();
            $table->string('status')->default('recorded')->index();
            $table->unsignedBigInteger('amount_afn');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('activation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->text('request_note')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('requested_at')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seller_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('platform_payment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('amount_afn');
            $table->decimal('rate_snapshot', 8, 2)->default(0);
            $table->string('commission_type_snapshot')->default('percent');
            $table->timestamp('earned_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('actor_type')->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event')->index();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_audit_events');
        Schema::dropIfExists('seller_commissions');
        Schema::dropIfExists('activation_requests');
        Schema::dropIfExists('platform_payments');
        Schema::dropIfExists('platform_invoice_lines');
        Schema::dropIfExists('platform_invoices');
        Schema::dropIfExists('commercial_sequences');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('sellers');
        Schema::dropIfExists('plans');
    }
};
