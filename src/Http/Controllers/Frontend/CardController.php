<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Frontend;


use Illuminate\Http\Request;
use Secretwebmaster\WncmsEcommerce\Models\Card;
use Wncms\Http\Controllers\Frontend\FrontendController;

class CardController extends FrontendController
{
    /**
     * Show card page
     */
    public function show()
    {
        return $this->view(
            "frontend.themes.{$this->theme}.users.card",
            [],
            'wncms::frontend.themes.default.users.card',
        );
    }

    public function use(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'code' => 'required|string|exists:cards,code', // Ensure the card code exists in the database
        ]);

        // Retrieve the card
        $card = Card::where('code', $validated['code'])->first();

        // Check if the card is active
        if ($card->status !== 'active') {
            return redirect()->back()->with('error', __('This card is not active or has already been used.'));
        }

        // Update the user's balance or related data
        $user = auth()->user();
        if ($card->type === 'credit' && $card->value) {

            $credit = $user->credits()->firstOrNew([
                'type' => 'balance',
            ]);

            $credit->amount += $card->value;
            $credit->save();
        } elseif ($card->type === 'plan' && $card->plan_id) {
            dd('get plan and assign to user or extend expired_at');
        } elseif ($card->type === 'product' && $card->product_id) {
            dd('get product');
            // Logic for assigning product to the user
            // $user->products()->attach($card->product_id); // Assuming a many-to-many relationship
        }

        // Save user changes
        $user->save();

        // Mark the card as redeemed
        $card->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
            'user_id' => $user->id, // Optional: track which user used the card
        ]);

        // Redirect back with a success message
        return redirect()->back()->withMessage(__('wncms::word.successfully_used_card'));
    }
}