<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('keyword_clicks', function (Blueprint $t) {
      $t->id();
      $t->foreignId('site_id')->constrained()->cascadeOnDelete();
      $t->foreignId('keyword_id')->constrained()->cascadeOnDelete();
      $t->foreignId('user_profile_id')->nullable()->constrained()->nullOnDelete();
      $t->date('day');                     // 集計日
      $t->unsignedInteger('count')->default(0);
      $t->timestamps();

      $t->unique(['site_id','keyword_id','user_profile_id','day'], 'uniq_clicks_day');
      $t->index(['site_id','keyword_id','day']);
    });
  }
  public function down(): void { Schema::dropIfExists('keyword_clicks'); }
};
