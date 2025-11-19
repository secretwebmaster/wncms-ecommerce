<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Frontend\FrontendController;
use Secretwebmaster\WncmsEcommerce\Facades\OrderManager;
use Secretwebmaster\WncmsEcommerce\Facades\PlanManager;
use Secretwebmaster\WncmsEcommerce\Models\Plan;

class PlanController extends FrontendController
{
    /**
     * Check if package is active
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            if (!wncms()->isPackageActive('wncms-ecommerce')) {

                // debug back
                abort (500,__('wncms::word.package_is_not_activated', ['package_id' => 'wncms-ecommerce']));
                die;

                // return back if has previous url and previous url is not current url
                if (url()->previous() && url()->previous() !== url()->current()) {
                    return back();
                }

                // Otherwise, abort 404
                abort(404);
            }
            return $next($request);
        });
    }


    /**
     * Display list of available plans.
     */
    public function index()
    {
        $plans = Plan::query()->where('status', 'active')->get();

        return $this->view(
            "frontend.themes.{$this->theme}.plans.index",
            compact('plans'),
            'wncms-ecommerce::frontend.themes.default.plans.index'
        );
    }

    /**
     * Display a single plan details.
     */
    public function show($slug)
    {
        $plan = Plan::where('slug', $slug)->first();
        if(!$plan){
            return back()->with('error', __('wncms-ecommerce::word.plan_not_found'));
        }

        $user = auth()->user()->load('subscriptions');
        return $this->view(
            "frontend.themes.{$this->theme}.plans.show",
            compact('plan', 'user'),
            'wncms-ecommerce::frontend.themes.default.plans.show'
        );
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request)
    {
        $plan = Plan::find($request->plan_id);
        if (!$plan) {
            return back()->with('error', __('wncms-ecommerce::word.plan_not_found'));
        }

        $price = $plan->prices()->where('id', $request->price_id)->first();
        if (!$price) {
            return back()->with('error', __('wncms-ecommerce::word.price_not_found'));
        }

        $user = auth()->user();

        // Already active subscription check
        if ($user->subscriptions()->where([
            'plan_id' => $plan->id,
            'price_id' => $price->id,
            'status' => 'active'
        ])->exists()) {
            return back()->with('error', __('wncms-ecommerce::word.already_subscribed'));
        }

        // Direct subscribe if balance enough
        if ($user->balance >= $price->amount) {
            $result = PlanManager::subscribe($user, $plan, $price);
            if (isset($result['error'])) {
                return back()->with('error', $result['error']);
            }

            $user->credits()->where('type', 'balance')->first()?->decrement('amount', $price->amount);
            return redirect()->route('frontend.users.subscription')->with('message', __('wncms-ecommerce::word.subscribed_successfully'));
        }

        // Otherwise, create order
        $order = OrderManager::create($user, $price);
        return redirect()->route('frontend.orders.show', ['slug' => $order->slug]);
    }

    /**
     * Unsubscribe from a plan.
     */
    public function unsubscribe(Request $request)
    {
        $subscriptionId = $request->subscription_id;
        if (!$subscriptionId) {
            return back()->with('error', __('wncms-ecommerce::word.subscription_id_required'));
        }

        try {
            PlanManager::unsubscribe(auth()->user(), $subscriptionId);
            return redirect()->route('frontend.users.subscription')->with('message', __('wncms-ecommerce::word.unsubscribed_successfully'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
