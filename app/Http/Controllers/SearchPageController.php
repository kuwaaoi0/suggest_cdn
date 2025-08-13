<?php

namespace App\Http\Controllers;

use App\Support\SuggestJwt;
use Illuminate\Support\Facades\Auth; // ← 追加

class SearchPageController extends Controller
{
    public function __invoke()
    {
        // ログインしていれば会員ID、していなければセッションIDを使う
        $externalUserId = Auth::check()
            ? (string) Auth::id()
            : 'guest:' . session()->getId();

        $suggestJwt = SuggestJwt::issueFor($externalUserId, 600); // 10分

        return view('search', compact('suggestJwt'));
    }
}
