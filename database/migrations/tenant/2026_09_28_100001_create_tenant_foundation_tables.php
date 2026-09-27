<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('staff')->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('business_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('display_name')->nullable();
            $table->string('secondary_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city_province')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->string('default_locale',5)->default('en');
            $table->string('default_currency',3)->default('AFN');
            $table->boolean('onboarding_completed')->default(false);
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
        Schema::create('invoice_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('template_number')->unique();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('invoice_templates');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('business_profiles');
        Schema::dropIfExists('users');
    }
};
