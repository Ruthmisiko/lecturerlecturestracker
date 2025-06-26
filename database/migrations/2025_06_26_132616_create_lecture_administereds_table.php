<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lecture_administereds', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('lecturer_id');
            $table->unsignedBigInteger('classs_id');
            $table->date('lecture_date');
            $table->time('lecture_time');

            $table->timestamps();

            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
            $table->foreign('classs_id')->references('id')->on('classses')->onDelete('cascade');
                    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('lecture_administereds');
    }
};
