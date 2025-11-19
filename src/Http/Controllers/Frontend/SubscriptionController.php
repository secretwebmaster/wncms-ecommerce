<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Frontend\FrontendController;
use Secretwebmaster\WncmsEcommerce\Models\Subscription;

class SubscriptionController extends FrontendController
{
    /**
     * Display all subscriptions of the logged-in user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('frontend.users.login');
        }

        $subscriptions = Subscription::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);


        return $this->view(
            "frontend.themes.{$this->theme}.users.subscriptions",
            [
                'subscriptions' => $subscriptions,
                'user' => $user,
            ],
            'wncms-ecommerce::frontend.subscriptions.index',
        );
    }

    /**
     * Display a single subscription detail.
     */
    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('frontend.users.login');
        }

        $subscription = Subscription::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return $this->view(
            "frontend.themes.{$this->theme}.users.subscriptions.show",
            [
                'subscription' => $subscription,
                'user' => $user,
            ],
            'wncms-ecommerce::frontend.users.subscriptions.show',
        );
    }
}
