<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('プロフィール情報') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("アカウントのプロフィール情報とメールアドレスを更新します。") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('名前')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('メールアドレス')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('メールアドレスが未確認です。') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('ここをクリックして確認メールを再送信します。') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('新しい確認リンクがメールアドレスに送信されました。') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('保存') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('保存しました。') }}</p>
            @endif
        </div>


        {{-- Complete replacement for update-profile-information-form.blade.php reminders section --}}

        {{-- Complete replacement for update-profile-information-form.blade.php reminders section --}}

        <div class="mt-6 border-t border-gray-200 pt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ __('リマインダー設定') }}
            </h3>

            <div class="mt-3">
                <x-input-label for="morning_reminder_time" :value="__('朝のリマインダー時間')" />
                <x-text-input
                    id="morning_reminder_time"
                    name="morning_reminder_time"
                    type="time"
                    class="mt-1 block w-full"
                    :value="old('morning_reminder_time',
                            is_string($user->morning_reminder_time)
                                ? $user->morning_reminder_time
                                : ($user->morning_reminder_time
                                    ? $user->morning_reminder_time->format('H:i')
                                    : '08:00'))"
                />
                <p class="mt-1 text-sm text-gray-600">本日のタスク実施忘れを防止するリマインダーです。</p>
                <x-input-error class="mt-1" :messages="$errors->get('morning_reminder_time')" />
            </div>

            <div class="mt-3">
                <x-input-label for="evening_reminder_time" :value="__('夕方のリマインダー時間')" />
                <x-text-input
                    id="evening_reminder_time"
                    name="evening_reminder_time"
                    type="time"
                    class="mt-1 block w-full"
                    :value="old('evening_reminder_time',
                            is_string($user->evening_reminder_time)
                                ? $user->evening_reminder_time
                                : ($user->evening_reminder_time
                                    ? $user->evening_reminder_time->format('H:i')
                                    : '20:00'))"
                />
                <p class="mt-1 text-sm text-gray-600">翌日のタスク入力忘れを防止するリマインダーです。</p>
                <x-input-error class="mt-1" :messages="$errors->get('evening_reminder_time')" />
            </div>
            <!-- resources/views/profile/partials/update-profile-information-form.blade.php -->
            <!-- Add this inside the form, after the Slack section -->

            <div class="mt-4">
                <h3 class="text-base font-medium text-gray-900 mb-2">
                    {{ __('Line Notify設定') }}
                </h3>
                <div class="mt-3">
                    <x-input-label for="line_notify_token" :value="__('Line Notify Token (任意)')" />
                    <x-text-input
                        id="line_notify_token"
                        name="line_notify_token"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('line_notify_token', $user->line_notify_token)"
                        placeholder="Line Notify personal access token"
                    />
                    <p class="mt-1 text-sm text-gray-600">
                        リマインダーをLineに送信する場合は、Line Notify トークンを入力してください。
                        <a href="https://notify-bot.line.me/my/" target="_blank" class="text-blue-600 hover:underline">
                            Line Notify トークンの取得方法
                        </a>
                    </p>
                    <x-input-error class="mt-1" :messages="$errors->get('line_notify_token')" />
                </div>
            </div>

            <div class="mt-4">
                <h3 class="text-base font-medium text-gray-900 mb-2">
                    {{ __('Slack通知設定') }}
                </h3>
                <div class="mt-3">
                    <x-input-label for="slack_webhook_url" :value="__('Slack Webhook URL (任意)')" />
                    <x-text-input
                        id="slack_webhook_url"
                        name="slack_webhook_url"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('slack_webhook_url', $user->slack_webhook_url)"
                        placeholder="https://hooks.slack.com/services/..."
                    />
                    <p class="mt-1 text-sm text-gray-600">
                        リマインダーをSlackに送信する場合は、Incoming Webhook URLを入力してください。
                        <a href="https://api.slack.com/messaging/webhooks" target="_blank" class="text-blue-600 hover:underline">
                            Slack Incoming Webhooksについて
                        </a>
                    </p>
                    <x-input-error class="mt-1" :messages="$errors->get('slack_webhook_url')" />
                </div>
            </div>
        </div>
    </form>
</section>
