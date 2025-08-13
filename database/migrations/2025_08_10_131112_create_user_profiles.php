<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('user_profiles', function (Blueprint $t) {
      $t->id();
      $t->foreignId('site_id')->constrained()->cascadeOnDelete();
      $t->string('external_user_id');              // 埋め込み先サイトのユーザーID
      $t->timestamps();
      $t->unique(['site_id','external_user_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('user_profiles'); }
};
