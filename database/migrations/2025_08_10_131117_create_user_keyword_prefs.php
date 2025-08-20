<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('user_keyword_prefs', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_profile_id')->constrained()->cascadeOnDelete();
      $t->foreignId('keyword_id')->constrained()->cascadeOnDelete();
      $t->enum('visibility', ['default','force_show','force_hide'])->default('default');
      $t->integer('boost')->default(0); // -100〜+100 目安
      $t->timestamps();
      $t->unique(['user_profile_id','keyword_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('user_keyword_prefs'); }
};
