<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('class_unit', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('classs_id'); // reference classses table
            $table->unsignedBigInteger('unit_id');   // reference units table

            $table->foreign('classs_id')
                ->references('id')->on('classses')
                ->onDelete('cascade');

            $table->foreign('unit_id')
                ->references('id')->on('units')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_unit');
    }
};
