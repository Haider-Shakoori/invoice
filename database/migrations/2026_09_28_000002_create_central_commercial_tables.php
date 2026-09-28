<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('operator')->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('businesses', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->string('display_name');
            $table->string('owner_name');
            $table->string('owner_email')->index();
            $table->string('owner_phone')->nullable();
            $table->string('status')->default('trial')->index();
            $table->timestamp('trial_ends_at')->nullable()->index();
            $table->timestamp('subscription_ends_at')->nullable()->index();
            $table->string('provisioning_status')->default('pending')->index();
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->restrictOnDelete();
        });
        Schema::create('provisioning_events', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('step');
            $table->string('status')->index();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioning_events');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('admin_users');
    }
};
