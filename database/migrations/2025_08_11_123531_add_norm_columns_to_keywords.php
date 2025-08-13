<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('keywords', function (Blueprint $t) {
      $t->string('label_norm')->after('label')->nullable();
      $t->string('reading_norm')->after('reading')->nullable();
      $t->index('label_norm');
      $t->index('reading_norm');
    });
  }
  public function down(): void {
    Schema::table('keywords', function (Blueprint $t) {
      $t->dropIndex(['label_norm']);
      $t->dropIndex(['reading_norm']);
      $t->dropColumn(['label_norm','reading_norm']);
    });
  }
};
