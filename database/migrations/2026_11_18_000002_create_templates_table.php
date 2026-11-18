<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the templates table.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('estimated', 14, 2);
            $table->string('frequency');
            $table->unsignedTinyInteger('half')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Drop the templates table.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
