<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('user_keywords', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_profile_id')->constrained()->cascadeOnDelete(); // このユーザー専用
      $t->foreignId('site_id')->constrained()->cascadeOnDelete();         // 念のためテナント境界も保持
      $t->foreignId('genre_id')->nullable()->constrained()->nullOnDelete();

      $t->string('label');
      $t->string('reading')->nullable();

      // 正規化（インデックス必須）
      $t->string('label_norm')->nullable();
      $t->string('reading_norm')->nullable();

      // 並び制御（ユーザー専用側にも）
      $t->integer('weight')->default(0);
      // 可視性・微調整（UserKeywordPref 相当を内包）
      $t->enum('visibility', ['default','force_show','force_hide'])->default('default');
      $t->integer('boost')->default(0);

      $t->boolean('is_active')->default(true);

      $t->timestamps();

      $t->index(['user_profile_id','label_norm']);
      $t->index(['user_profile_id','reading_norm']);
    });
  }
  public function down(): void { Schema::dropIfExists('user_keywords'); }
};
