<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('vehicle_categories')->restrictOnDelete();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('license_plate');
            $table->integer('daily_rate');
            $table->string('status')->default('available'); // available, reserved, maintenance, retired
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'license_plate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
