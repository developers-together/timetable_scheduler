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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('course_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('type', ['Lecture', 'Tutorial', 'Lab']);
        });

        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->timestamps();
        });

        Schema::create('instructor_roles', function (Blueprint $table) {
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->enum('role', ['prof', 'ta', 'lab_ta']);
        });

        Schema::create('course_instructor', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->primary(['course_id', 'instructor_id']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->enum('type', ['Classroom', 'ComputerLab', 'Theatre', 'Hall', 'BioLab', 'DrawingStudio', 'PhysicsLab', 'DrawingLab']);
            $table->integer('capacity');
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->enum('day', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']);
            $table->time('start');
            $table->time('end');
            // $table->timestamps();
        });

        Schema::create('required_courses', function (Blueprint $table) {

            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->integer('required_capacity');
            $table->integer('level');
            $table->enum('term', ['fall', 'spring', 'summer']);
            //$table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->enum('faculty', ['CSIT', 'FOE', 'BAS', 'FIBH', 'Art', 'Pharma', 'ARCH']);
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->enum('term', ['fall', 'spring', 'summer']);
            $table->enum('faculty', ['CSIT', 'FOE', 'BAS', 'FIBH', 'Art', 'Pharma', 'ARCH']);
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('course_component_id')->constrained('course_components')->onDelete('cascade');
            $table->enum('slot', ['full', 'first_half', 'second_half']);
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained('time_slots')->onDelete('cascade');
            $table->integer('groupNO');
            $table->integer('sectionNO');
            $table->unique(['room_id', 'time_slot_id']);
            $table->unique(['instructor_id', 'time_slot_id']);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
