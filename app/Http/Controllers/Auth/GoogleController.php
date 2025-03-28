<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    // Google 認証ページへリダイレクト
    public function redirectToGoogle()
    {
        // Log out current user
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return Socialite::driver("google")->redirect();
    }

    // Google からのコールバック処理
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver("google")->user();

            // 既存ユーザーか確認
            $user = User::where("email", $googleUser->getEmail())->first();

            if (!$user) {
                // ユーザーがいなければ作成
                $user = User::create([
                    "name" => $googleUser->getName(),
                    "email" => $googleUser->getEmail(),
                    "password" => bcrypt(uniqid()), // 仮のパスワード
                    "google_id" => $googleUser->getId(),
                    "avatar" => $googleUser->getAvatar(),
                ]);
            }

            // ログイン処理
            Auth::login($user);

            return redirect()->route("dashboard"); // ログイン後のページへリダイレクト
        } catch (\Exception $e) {
            return redirect("/login")->with(
                "error",
                "Google ログインに失敗しました"
            );
        }
    }
}
