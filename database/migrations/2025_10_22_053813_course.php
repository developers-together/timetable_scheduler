<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Courses ---
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('field1', 'course_id');
            $table->renameColumn('field2', 'course_name');
            $table->renameColumn('field3', 'credits');
            $table->renameColumn('field4', 'course_type');
        });

        // --- Instructors ---
        Schema::table('instructors', function (Blueprint $table) {
            $table->renameColumn('field1', 'instructor_id');
            $table->renameColumn('field2', 'instructor_name');
            $table->renameColumn('field3', 'preferred_slots');
        });

        // --- Rooms ---
        Schema::table('rooms', function (Blueprint $table) {
            $table->renameColumn('field1', 'room_id');
            $table->renameColumn('field2', 'room_type');
            $table->renameColumn('field3', 'room_capacity');
        });

        // --- Qualified Courses (pivot table) ---
        Schema::table('qualified_courses', function (Blueprint $table) {
            $table->renameColumn('field1', 'instructor_id');
            $table->renameColumn('field2', 'course_id');
        });

        // Add foreign keys only once
        Schema::table('qualified_courses', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('instructor_id')
                ->on('instructors')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
        });

        // --- Timetable ---
        Schema::table('timetable', function (Blueprint $table) {
            $table->renameColumn('field1', 'id');
            $table->renameColumn('field2', 'day');
            $table->renameColumn('field3', 'start_time');
            $table->renameColumn('field4', 'end_time');
            $table->renameColumn('field5', 'level');
            $table->renameColumn('field6', 'room_id');
            $table->renameColumn('field7', 'course_id');
            $table->renameColumn('field8', 'instructor_id');
        });

        Schema::table('timetable', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('instructor_id')
                ->on('instructors')
                ->onDelete('cascade');

            $table->foreign('room_id')
                ->references('room_id')
                ->on('rooms')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Optional: Reverse renames and drop constraints if needed
    }
};
