<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('job_seeker');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (! Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable();
            }

            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable();
            }

            if (! Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('users', 'role') ? 'role' : null,
                Schema::hasColumn('users', 'phone') ? 'phone' : null,
                Schema::hasColumn('users', 'profile_picture') ? 'profile_picture' : null,
                Schema::hasColumn('users', 'bio') ? 'bio' : null,
                Schema::hasColumn('users', 'preferences') ? 'preferences' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
