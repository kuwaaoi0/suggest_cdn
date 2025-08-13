<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('sites', function (Blueprint $t) {
      $t->string('api_key')->unique()->nullable()->after('site_key');
      $t->string('jwt_secret')->nullable()->after('api_key');
      $t->string('jwt_issuer')->nullable()->after('jwt_secret');
      $t->unsignedInteger('rate_limit_per_min')->default(120)->after('jwt_issuer');
      // allowed_origins が無ければ JSON で用意（既にあるなら不要）
      if (!Schema::hasColumn('sites', 'allowed_origins')) {
        $t->json('allowed_origins')->nullable()->after('rate_limit_per_min');
      }
    });
  }
  public function down(): void {
    Schema::table('sites', function (Blueprint $t) {
      if (Schema::hasColumn('sites','allowed_origins')) $t->dropColumn('allowed_origins');
      $t->dropColumn(['rate_limit_per_min','jwt_issuer','jwt_secret','api_key']);
    });
  }
};
