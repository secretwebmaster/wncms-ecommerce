<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class DiscountController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        if ($search = $request->get('search')) {
            $q->where('name', 'like', "%{$search}%");
        }

        $discounts = $q->orderBy('id', 'desc')->paginate($request->page_size ?? 100);

        return $this->view('wncms-ecommerce::backend.discounts.index', [
            'page_title' => wncms()->getModelWord('discount', 'management'),
            'discounts' => $discounts,
            'statuses' => $this->modelClass::STATUSES,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $discount = $this->modelClass::find($id);
            if (!$discount) {
                return back()->withMessage(__('wncms::word.model_not_found', [
                    'model_name' => __('wncms::word.' . $this->singular)
                ]));
            }
        } else {
            $discount = new $this->modelClass;
        }

        return $this->view('wncms-ecommerce::backend.discounts.create', [
            'page_title' => wncms()->getModelWord('discount', 'create'),
            'discount' => $discount,
            'statuses' => $this->modelClass::STATUSES,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:discounts,name',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'status' => 'required|in:active,inactive',
        ],[
            'name.required' => __('wncms::validation.required', ['attribute' => __('wncms::word.name')]),
            'name.unique' => __('wncms::validation.unique', ['attribute' => __('wncms::word.name')]),
            'type.required' => __('wncms::validation.required', ['attribute' => __('wncms::word.type')]),
            'type.in' => __('wncms::validation.in', ['attribute' => __('wncms::word.type')]),
            'value.required' => __('wncms::validation.required', ['attribute' => __('wncms::word.value')]),
            'value.numeric' => __('wncms::validation.numeric', ['attribute' => __('wncms::word.value')]),
            'value.min' => __('wncms::validation.min.numeric', ['attribute' => __('wncms::word.value'), 'min' => 0]),
            'started_at.date' => __('wncms::validation.date', ['attribute' => __('wncms::word.started_at')]),
            'ended_at.date' => __('wncms::validation.date', ['attribute' => __('wncms::word.ended_at')]),
            'ended_at.after_or_equal' => __('wncms::validation.after_or_equal', ['attribute' => __('wncms::word.ended_at'), 'date' => __('wncms::word.started_at')]),
            'status.required' => __('wncms::validation.required', ['attribute' => __('wncms::word.status')]),
            'status.in' => __('wncms::validation.in', ['attribute' => __('wncms::word.status')]),
        ]);

        $discount = $this->modelClass::create($validated);

        $this->flush();

        return redirect()->route('discounts.edit', ['id' => $discount->id])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $discount = $this->modelClass::find($id);
        if (!$discount) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular)
            ]));
        }

        return $this->view('wncms-ecommerce::backend.discounts.edit', [
            'page_title' => wncms()->getModelWord('discount', 'edit'),
            'discount' => $discount,
            'statuses' => $this->modelClass::STATUSES,
            'types' => $this->modelClass::TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $discount = $this->modelClass::find($id);
        if (!$discount) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular)
            ]));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:discounts,name,' . $discount->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'status' => 'required|in:active,inactive',
        ]);

        $discount->update($validated);

        $this->flush();

        return redirect()->route('discounts.edit', ['id' => $discount->id])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy($id)
    {
        $discount = $this->modelClass::find($id);
        if (!$discount) {
            return back()->withMessage(__('wncms::word.model_not_found', [
                'model_name' => __('wncms::word.' . $this->singular)
            ]));
        }

        $discount->delete();

        $this->flush();

        return redirect()->route('discounts.index')->withMessage(__('wncms::word.successfully_deleted'));
    }
}
