<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * --- COURSES ---
         */
        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_id')->primary()->change();
            $table->string('course_name')->nullable(false)->change();
            $table->integer('credits')->nullable()->change();
            $table->string('course_type')->nullable()->change();
        });

        /*
         * --- INSTRUCTORS ---
         */
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('instructor_id')->primary()->change();
            $table->string('instructor_name')->nullable(false)->change();
            $table->string('preferred_slots')->nullable()->change();
        });

        /*
         * --- ROOMS ---
         */
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_id')->primary()->change();
            $table->string('room_type')->change();
            $table->integer('room_capacity')->nullable()->change();
        });

        /*
         * --- QUALIFIED_COURSES ---
         * Many-to-many: instructors ↔ courses
         */
        /*
 * --- QUALIFIED_COURSES ---
 */
    Schema::table('qualified_courses', function (Blueprint $table) {
        $table->dropForeign(['instructor_id']);
        $table->dropForeign(['course_id']);

        // Make them nullable and string type
        $table->string('instructor_id')->change();
        $table->string('course_id')->change();

        $table->foreign('instructor_id')
            ->references('instructor_id')
            ->on('instructors')
            ->onDelete('cascade');

        $table->foreign('course_id')
            ->references('course_id')
            ->on('courses')
            ->onDelete('cascade');
    });

    /*
    * --- TIMETABLE ---
    */
    Schema::table('timetable', function (Blueprint $table) {
        $table->dropForeign(['instructor_id']);
        $table->dropForeign(['course_id']);
        $table->dropForeign(['room_id']);

        // Make them nullable and string type
        $table->string('instructor_id')->nullable()->change();
        $table->string('course_id')->nullable()->change();
        $table->string('room_id')->nullable()->change();

        $table->foreign('instructor_id')
            ->references('instructor_id')
            ->on('instructors')
            ->onDelete('cascade');

        $table->foreign('course_id')
            ->references('course_id')
            ->on('courses')
            ->onDelete('cascade');

        $table->foreign('room_id')
            ->references('room_id')
            ->on('rooms')
            ->onDelete('cascade');
    });

    }

    public function down(): void
    {
        // Optional rollback if you need to revert later
    }
};
