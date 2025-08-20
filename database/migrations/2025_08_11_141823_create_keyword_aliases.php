<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('keyword_aliases', function (Blueprint $t) {
      $t->id();
      $t->foreignId('keyword_id')->constrained()->cascadeOnDelete();
      $t->string('alias');            // 表示されない別名（検索用）
      $t->string('alias_norm');       // 正規化（前方一致インデックス）
      $t->timestamps();

      $t->unique(['keyword_id','alias_norm']);
      $t->index('alias_norm');
    });
  }
  public function down(): void {
    Schema::dropIfExists('keyword_aliases');
  }
};
