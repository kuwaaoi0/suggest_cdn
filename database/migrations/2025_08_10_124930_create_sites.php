<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sites', function (Blueprint $t) {
      $t->id();
      $t->string('name');
      $t->string('site_key')->unique();        // 公開キー
      $t->json('allowed_origins')->nullable(); // ["https://example.com"] など
      $t->boolean('is_active')->default(true);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('sites'); }
};
