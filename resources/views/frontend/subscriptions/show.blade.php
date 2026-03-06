<div class="container py-4">
    <h1>Subscription #{{ $subscription->id }}</h1>
    <p>Plan: {{ $subscription->plan?->name ?? 'N/A' }}</p>
    <p>Status: {{ $subscription->status }}</p>
    <p>Next billing: {{ $subscription->next_billing_at?->format('Y-m-d H:i') ?? '-' }}</p>
</div>
