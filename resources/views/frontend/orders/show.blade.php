<h2>@lang('wncms::word.order_detail')</h2>

<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>@lang('wncms::word.order_id')</th>
        <td>{{ $order->slug }}</td>
    </tr>
    <tr>
        <th>@lang('wncms::word.status')</th>
        <td>{{ __('wncms-ecommerce::word.' . $order->status) }}</td>
    </tr>
    <tr>
        <th>@lang('wncms::word.total_amount')</th>
        <td>{{ number_format($order->total_amount, 2) }}</td>
    </tr>
    <tr>
        <th>@lang('wncms::word.payment_gateway')</th>
        <td>{{ $order->payment_gateway->name ?? '-' }}</td>
    </tr>
    <tr>
        <th>@lang('wncms::word.tracking_code')</th>
        <td>{{ $order->tracking_code ?? '-' }}</td>
    </tr>
    <tr>
        <th>@lang('wncms::word.remark')</th>
        <td>{{ $order->remark ?? '-' }}</td>
    </tr>
    <tr>
        <th>@lang('wncms::word.created_at')</th>
        <td>{{ $order->created_at }}</td>
    </tr>
</table>

<br>

<h3>@lang('wncms::word.order_items')</h3>

@if($order->order_items->count())
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>@lang('wncms::word.item_id')</th>
        <th>@lang('wncms::word.type')</th>
        <th>@lang('wncms::word.name')</th>
        <th>@lang('wncms::word.quantity')</th>
        <th>@lang('wncms::word.amount')</th>
    </tr>
    @foreach($order->order_items as $item)
    <tr>
        <td>{{ $item->id }}</td>
        <td>{{ class_basename($item->order_itemable_type) }}</td>
        <td>{{ $item->order_itemable->name ?? '-' }}</td>
        <td>{{ $item->quantity }}</td>
        <td>{{ number_format($item->amount, 2) }}</td>
    </tr>
    @endforeach
</table>
@else
<p>@lang('wncms::word.no_items_found')</p>
@endif

<br>

@if($order->status == 'pending_payment')
    <h3>@lang('wncms::word.payment')</h3>
    <form action="{{ route('frontend.orders.pay', ['slug' => $order->slug]) }}" method="POST">
        @csrf
        <select name="payment_gateway">
            <option value="">@lang('wncms::word.please_select')</option>
            @foreach($paymentGateways as $paymentGateway)
                <option value="{{ $paymentGateway['slug'] }}">{{ $paymentGateway['name'] }}</option>
            @endforeach
        </select>
        <button type="submit">@lang('wncms::word.pay_now')</button>
    </form>
@endif
