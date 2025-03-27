<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\CategoryShareController;

class SharedTasksController extends Controller
{
    /**
     * Display the kanban board for shared tasks
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check if user has a subscription
        // if (!Auth::user()->subscription_id) {
        //     return redirect()
        //         ->route("stripe.subscription")
        //         ->with(
        //             "error",
        //             "この機能を利用するにはサブスクリプションが必要です。"
        //         );
        // }

        return view("tasks.kanban");
    }

    /**
     * Display team members page for task collaboration
     *
     * @return \Illuminate\View\View
     */
    public function teamMembers()
    {
        // Check if user has a subscription
        // if (!Auth::user()->subscription_id) {
        //     return redirect()
        //         ->route("stripe.subscription")
        //         ->with(
        //             "error",
        //             "この機能を利用するにはサブスクリプションが必要です。"
        //         );
        // }

        return view("tasks.team");
    }

    /**
     * Display analytics dashboard for task progress
     *
     * @return \Illuminate\View\View
     */
    public function analytics()
    {
        // Check if user has a subscription
        // if (!Auth::user()->subscription_id) {
        //     return redirect()
        //         ->route("stripe.subscription")
        //         ->with(
        //             "error",
        //             "この機能を利用するにはサブスクリプションが必要です。"
        //         );
        // }

        return view("tasks.analytics");
    }
}
