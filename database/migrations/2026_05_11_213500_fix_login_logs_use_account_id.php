<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('login_logs')) {
            return;
        }

        Schema::table('login_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('login_logs', 'account_id')) {
                $table->uuid('account_id')->nullable()->after('user_id');
                $table->index('account_id');
            }
        });

        // Best-effort backfill for environments that still have users table and email link.
        if (
            Schema::hasTable('users') &&
            Schema::hasTable('accounts') &&
            Schema::hasColumn('users', 'id') &&
            Schema::hasColumn('users', 'email') &&
            Schema::hasColumn('accounts', 'id') &&
            Schema::hasColumn('accounts', 'email') &&
            Schema::hasColumn('login_logs', 'user_id')
        ) {
            DB::table('login_logs')
                ->select('id', 'user_id')
                ->whereNull('account_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $userEmail = DB::table('users')
                            ->where('id', $row->user_id)
                            ->value('email');

                        if (!$userEmail) {
                            continue;
                        }

                        $accountId = DB::table('accounts')
                            ->where('email', $userEmail)
                            ->value('id');

                        if (!$accountId) {
                            continue;
                        }

                        DB::table('login_logs')
                            ->where('id', $row->id)
                            ->update(['account_id' => $accountId]);
                    }
                });
        }

        // Add foreign key only when accounts table exists and no invalid rows are present.
        if (Schema::hasTable('accounts')) {
            $orphanCount = DB::table('login_logs')
                ->whereNotNull('account_id')
                ->whereNotIn('account_id', function ($query) {
                    $query->select('id')->from('accounts');
                })
                ->count();

            if ($orphanCount === 0) {
                Schema::table('login_logs', function (Blueprint $table) {
                    $table->foreign('account_id')
                        ->references('id')
                        ->on('accounts')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('login_logs')) {
            return;
        }

        try {
            Schema::table('login_logs', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
            });
        } catch (\Throwable $e) {
            // Ignore when foreign key was never created.
        }

        Schema::table('login_logs', function (Blueprint $table) {
            if (Schema::hasColumn('login_logs', 'account_id')) {
                $table->dropIndex(['account_id']);
                $table->dropColumn('account_id');
            }
        });
    }
};

