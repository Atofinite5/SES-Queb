<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('framework');
            $table->json('features');
            $table->json('default_config');
            $table->timestamps();
        });

        Schema::create('saved_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config');
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('scaffold_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('status');
            $table->json('config');
            $table->string('output_path')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('progress')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_id')->unique();
            $table->string('audit_type');
            $table->string('project_path');
            $table->json('vulnerabilities')->nullable();
            $table->json('license_issues')->nullable();
            $table->json('security_configs')->nullable();
            $table->json('summary');
            $table->timestamps();
        });

        Schema::create('git_hub_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('github_username');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_hub_connections');
        Schema::dropIfExists('audit_reports');
        Schema::dropIfExists('scaffold_jobs');
        Schema::dropIfExists('saved_configs');
        Schema::dropIfExists('templates');
    }
};
