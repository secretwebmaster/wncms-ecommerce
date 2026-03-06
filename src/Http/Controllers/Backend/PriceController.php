<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Secretwebmaster\WncmsEcommerce\Models\Price;
use Wncms\Http\Controllers\Backend\BackendController;

class PriceController extends BackendController
{
    public function index(Request $request)
    {
        $prices = Price::query()->paginate($request->page_size ?? 100);

        return view('backend.prices.index', [
            'page_title' => wncms()->getModelWord('price', 'management'),
            'Prices' => $prices,
        ]);
    }

    public function create($id = null)
    {
        $price = $id ? Price::findOrFail($id) : new Price();

        return view('backend.prices.create', [
            'page_title' => wncms()->getModelWord('price', 'management'),
            'price' => $price,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'priceable_type' => 'required|string',
            'priceable_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|string|in:day,week,month,year',
            'is_lifetime' => 'nullable|boolean',
        ]);

        $price = Price::create([
            'priceable_type' => $validated['priceable_type'],
            'priceable_id' => $validated['priceable_id'],
            'amount' => $validated['amount'],
            'duration' => $validated['duration'] ?? null,
            'duration_unit' => $validated['duration_unit'] ?? null,
            'is_lifetime' => (bool) ($validated['is_lifetime'] ?? false),
        ]);

        wncms()->cache()->flush(['Prices']);

        return redirect()->route('prices.edit', ['id' => $price->id])
            ->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $price = Price::findOrFail($id);

        return view('backend.prices.edit', [
            'page_title' => wncms()->getModelWord('price', 'management'),
            'price' => $price,
        ]);
    }

    public function update(Request $request, $id)
    {
        $price = Price::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|string|in:day,week,month,year',
            'is_lifetime' => 'nullable|boolean',
        ]);

        $price->update([
            'amount' => $validated['amount'],
            'duration' => $validated['duration'] ?? null,
            'duration_unit' => $validated['duration_unit'] ?? null,
            'is_lifetime' => (bool) ($validated['is_lifetime'] ?? false),
        ]);

        wncms()->cache()->flush(['Prices']);

        return redirect()->route('prices.edit', ['id' => $price->id])
            ->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy($id)
    {
        $price = Price::find($id);
        if (!$price) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.price')]));
        }

        $price->delete();

        return redirect()->route('prices.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        $modelIds = is_array($request->model_ids)
            ? $request->model_ids
            : explode(',', (string) $request->model_ids);

        $count = Price::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('prices.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
