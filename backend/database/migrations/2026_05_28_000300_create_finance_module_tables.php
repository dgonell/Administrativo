<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('rnc', 30)->nullable()->index();
            $table->string('contact')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->unsignedInteger('distance')->default(0);
            $table->decimal('base_rate', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('finance_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('finance_client_id')->nullable()->constrained('finance_clients')->nullOnDelete();
            $table->date('service_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('final_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('finance_quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_quote_id')->constrained('finance_quotes')->cascadeOnDelete();
            $table->string('route_name');
            $table->string('capacity', 60);
            $table->unsignedInteger('days')->default(1);
            $table->unsignedInteger('buses')->default(1);
            $table->decimal('price_per_bus', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->string('pickup_point')->nullable();
            $table->string('dropoff_point')->nullable();
            $table->string('schedule')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_histories', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 40)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('action', 40);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_histories');
        Schema::dropIfExists('finance_quote_lines');
        Schema::dropIfExists('finance_quotes');
        Schema::dropIfExists('finance_routes');
        Schema::dropIfExists('finance_clients');
    }
};
