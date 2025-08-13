<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('user_keyword_aliases', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_keyword_id')->constrained()->cascadeOnDelete();
      $t->string('alias');
      $t->string('alias_norm');
      $t->timestamps();

      $t->unique(['user_keyword_id','alias_norm']);
      $t->index('alias_norm');
    });
  }
  public function down(): void { Schema::dropIfExists('user_keyword_aliases'); }
};
