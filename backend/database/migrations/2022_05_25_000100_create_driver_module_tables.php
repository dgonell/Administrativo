<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('identity_document', 50)->unique();
            $table->date('birth_date')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->string('rehire_status', 20)->default('review')->index();
            $table->text('rehire_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('license_number', 50)->unique();
            $table->string('category', 30)->nullable()->index();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('issuing_entity', 120)->nullable();
            $table->text('restrictions')->nullable();
            $table->text('observations')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_required_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type', 80);
            $table->boolean('requires_expiration_date')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->string('name');
            $table->string('file_path');
            $table->string('file_disk', 40)->default('local');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 80)->nullable();
            $table->string('phone', 40);
            $table->string('secondary_phone', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('driver_medical_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type', 80);
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_conduct_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->date('event_date');
            $table->string('type', 80)->index();
            $table->string('severity', 30)->default('low')->index();
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_termination_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->date('termination_date');
            $table->string('termination_type', 50)->index();
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('rehire_status', 20)->index();
            $table->text('rehire_reason')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_traffic_fine_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('license_number', 50)->nullable()->index();
            $table->string('vehicle_plate', 30)->nullable()->index();
            $table->timestamp('checked_at')->nullable();
            $table->string('source')->default('manual');
            $table->string('result_status', 50)->default('not_checked')->index();
            $table->text('result_summary')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('file_path')->nullable();
            $table->date('next_check_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('driver_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_status_histories');
        Schema::dropIfExists('driver_traffic_fine_checks');
        Schema::dropIfExists('driver_termination_records');
        Schema::dropIfExists('driver_conduct_reports');
        Schema::dropIfExists('driver_medical_leaves');
        Schema::dropIfExists('driver_emergency_contacts');
        Schema::dropIfExists('driver_documents');
        Schema::dropIfExists('driver_required_documents');
        Schema::dropIfExists('driver_licenses');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('contract_types');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
