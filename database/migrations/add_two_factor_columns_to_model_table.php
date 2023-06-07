<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger("chat_bale_id")->nullable()->default(null);
            $table->string("secret_bot", 75)->nullable()->default(null);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumns("chat_bale_id");
            $table->dropColumns("secret_bot");
        });
    }
};
