<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class OrderController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $sorts = ['id', 'created_at', 'updated_at', 'total_amount', 'status', 'paid_at', 'failed_at'];

        // Filters
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $sort = (string) $request->input('sort', 'id');
        if (!in_array($sort, $sorts, true)) {
            $sort = 'id';
        }

        $direction = strtolower((string) $request->input('direction', 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }
        $q->orderBy($sort, $direction);

        $orders = $q->paginate($request->page_size ?? 100)->withQueryString();

        return $this->view('wncms-ecommerce::backend.orders.index', [
            'page_title' => wncms()->getModelWord('orders', 'management'),
            'statuses' => $this->modelClass::STATUSES,
            'sorts' => $sorts,
            'userOrders' => $orders,
            'users' => wncms()->getModelClass('user')::all(),
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $model = $this->modelClass::find($id);
            if (!$model) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $model = new $this->modelClass;
        }

        return $this->view('wncms-ecommerce::backend.orders.create', [
            'page_title' => wncms()->getModelWord('orders', 'create'),
            'users' => wncms()->getModelClass('user')::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:' . implode(',', $this->modelClass::STATUSES),
            'total_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $order = $this->modelClass::create(array_merge($validated, [
            'slug' => uniqid('order_'), // Generate a unique slug
        ]));

        $this->flush();

        return redirect()->route('orders.edit', $order)
            ->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $order = $this->modelClass::find($id);
        if (!$order) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('wncms-ecommerce::backend.orders.edit', [
            'page_title' => wncms()->getModelWord('orders', 'edit'),
            'order' => $order,
            'users' => wncms()->getModelClass('user')::all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = $this->modelClass::find($id);
        if (!$order) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string|in:' . implode(',', $this->modelClass::STATUSES),
            'total_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $order->update($validated);

        $this->flush();

        return redirect()->route('orders.edit', $order)->withMessage(__('wncms::word.successfully_updated'));
    }
}
