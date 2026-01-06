<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'lang' column to user_webhooks table.
     * This column stores the preferred language for webhook status reasons.
     * Supported values: 'fr', 'en', 'ar' (default: 'fr')
     */
    public function up(): void
    {
        Schema::table('user_webhooks', function (Blueprint $table) {
            $table->string('lang', 5)->default('fr')->after('security_token')
                ->comment('Preferred language for status reasons: fr, en, ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_webhooks', function (Blueprint $table) {
            $table->dropColumn('lang');
        });
    }
};
