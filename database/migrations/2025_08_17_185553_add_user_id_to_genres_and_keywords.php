<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('genres', function (Blueprint $t) {
      $t->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
      $t->index(['user_id','name']);
    });
    Schema::table('keywords', function (Blueprint $t) {
      $t->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
      $t->index(['user_id','label']);
      $t->index(['user_id','label_norm']);
      $t->index(['user_id','reading_norm']);
    });
  }
  public function down(): void {
    Schema::table('genres', function (Blueprint $t) { $t->dropConstrainedForeignId('user_id'); });
    Schema::table('keywords', function (Blueprint $t) { $t->dropConstrainedForeignId('user_id'); });
  }
};

