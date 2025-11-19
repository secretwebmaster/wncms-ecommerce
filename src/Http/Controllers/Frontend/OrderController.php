<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Frontend\FrontendController;
use Secretwebmaster\WncmsEcommerce\Models\Order;
use Secretwebmaster\WncmsEcommerce\Models\OrderItem;
use Secretwebmaster\WncmsEcommerce\Models\Product;
use Secretwebmaster\WncmsEcommerce\Models\PaymentGateway;

class OrderController extends FrontendController
{
    /**
     * Display list of user's orders.
     */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->latest()->paginate(20);

        return $this->view(
            "frontend.themes.{$this->theme}.orders.index",
            compact('orders'),
            'wncms-ecommerce::frontend.orders.index',
        );
    }

    /**
     * Show a single order detail.
     */
    public function show(string $slug)
    {
        $order = Order::where('user_id', auth()->id())->where('slug', $slug)->first();
        if (!$order || $order->user_id != auth()->id()) {
            return redirect()->route('frontend.orders.index')
                ->with('error', __('wncms::word.no_records_found'));
        }

        $order->load('order_items.order_itemable');
        $paymentGateways = PaymentGateway::where('status', 'active')->get();

        return $this->view(
            "frontend.themes.{$this->theme}.orders.show",
            [
                'order' => $order,
                'paymentGateways' => $paymentGateways,
            ],
            'wncms-ecommerce::frontend.orders.show',
        );
    }

    /**
     * Temporary test method — create a new order for selected product.
     */
    public function create(Request $request)
    {
        $product = Product::findOrFail($request->product_id);

        // Create order
        $order = Order::create([
            'user_id' => auth()->id() ?? 1,
            'status' => 'pending_payment',
            'total_amount' => $product->price,
        ]);

        // Add polymorphic order item
        OrderItem::create([
            'order_id' => $order->id,
            'order_itemable_id' => $product->id,
            'order_itemable_type' => Product::class,
            'quantity' => 1,
            'amount' => $product->price,
        ]);

        return redirect()->route('frontend.orders.show', ['slug' => $order->slug]);
    }

    /**
     * Process payment request (placeholder).
     */
    public function pay(Request $request, $slug)
    {
        $order = Order::where('user_id', auth()->id())->where('slug', $slug)->first();
        if (!$order || $order->user_id != auth()->id()) {
            return redirect()->route('frontend.orders.index')
                ->with('error', __('wncms::word.no_records_found'));
        }

        $paymentGateway = PaymentGateway::where('status', 'active')
            ->where('slug', $request->payment_gateway)
            ->first();

        if ($paymentGateway) {
            return $paymentGateway->processor()->process($order->id);
        }

        return back()->with('error', __('wncms-ecommerce::word.payment_gateway_not_found'));
    }

    /**
     * Display success page after payment.
     */
    public function success(Request $request, $slug)
    {
        $order = Order::where('user_id', auth()->id())->where('slug', $slug)->first();
        if (!$order || $order->user_id != auth()->id()) {
            return redirect()->route('frontend.orders.index')
                ->with('error', __('wncms::word.no_records_found'));
        }
        
        return $this->view(
            "frontend.themes.{$this->theme}.orders.success",
            ['order' => $order],
            'wncms-ecommerce::frontend.orders.success',
        );
    }

    public function status(Request $request)
    {
        $order = Order::where('user_id', auth()->id())->where('slug', $request->slug)->first();
        if (!$order || $order->user_id != auth()->id()) {
            return response()->json([
                'error' => __('wncms::word.no_records_found'),
            ], 404);
        }

        return response()->json([
            'status' => $order->status,
        ]);
    }
}
