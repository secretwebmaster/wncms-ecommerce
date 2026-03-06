<div class="container py-4">
    <h1>Redeem Card</h1>
    <form action="{{ route('frontend.users.card.use') }}" method="POST">
        @csrf
        <input name="code" type="text" placeholder="Card code" required>
        <button type="submit">Redeem</button>
    </form>
</div>
