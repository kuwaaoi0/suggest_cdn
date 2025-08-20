<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('keyword_clicks', function (Blueprint $t) {
      $t->boolean('is_user')->default(false)->after('keyword_id');
      $t->foreignId('user_keyword_id')->nullable()->after('is_user')->constrained()->nullOnDelete();

      // 一意制約を取り直す（RDBにより dropIndex 名称は調整）
      $t->dropUnique('uniq_clicks_day');
      $t->unique(['site_id','is_user','keyword_id','user_keyword_id','user_profile_id','day'], 'uniq_clicks_day2');
    });
  }
  public function down(): void {
    Schema::table('keyword_clicks', function (Blueprint $t) {
      $t->dropUnique('uniq_clicks_day2');
      $t->dropColumn(['is_user','user_keyword_id']);
      // 必要なら旧uniqを復元
      // $t->unique(['site_id','keyword_id','user_profile_id','day'], 'uniq_clicks_day');
    });
  }
};
