<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the periods table.
     */
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('half');
            $table->boolean('closed')->default(false);
            $table->timestamps();

            $table->unique(['year', 'month', 'half']);
        });
    }

    /**
     * Drop the periods table.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
