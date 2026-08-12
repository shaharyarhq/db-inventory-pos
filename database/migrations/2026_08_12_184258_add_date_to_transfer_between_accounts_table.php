<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfer_between_accounts', function (Blueprint $table) {
            $table->date('date')->nullable()->after('attachments');
        });

        // backfill from created_at for existing rows
        DB::table('transfer_between_accounts')->update([
            'date' => DB::raw('DATE(created_at)'),
        ]);

        Schema::table('transfer_between_accounts', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_between_accounts', function (Blueprint $table) {
            //
        });
    }
};
