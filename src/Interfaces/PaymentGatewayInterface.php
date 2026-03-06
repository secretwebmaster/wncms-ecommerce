<?php

namespace Secretwebmaster\WncmsEcommerce\Interfaces;

use Illuminate\Http\Request;
use Secretwebmaster\WncmsEcommerce\Models\Order;

interface PaymentGatewayInterface
{
    public function process($orderId);

    public function notify(Request $request);

    /**
     * Verify callback authenticity before any state mutation.
     *
     * @return array{verified:bool,status:int,message:string,event_id:?string}
     */
    public function verifyCallback(Request $request, ?Order $order = null): array;
}
