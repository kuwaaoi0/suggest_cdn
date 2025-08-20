<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('keywords', function (Blueprint $t) {
      $t->id();
      $t->string('label');               // 表示名
      $t->string('reading')->nullable(); // かな等（任意）
      $t->foreignId('genre_id')->nullable()->constrained()->nullOnDelete();
      $t->unsignedInteger('weight')->default(0); // 並び順の調整用
      $t->boolean('is_active')->default(true);
      $t->timestamps();
      $t->index(['label', 'reading']);
    });
  }
  public function down(): void { Schema::dropIfExists('keywords'); }
};
