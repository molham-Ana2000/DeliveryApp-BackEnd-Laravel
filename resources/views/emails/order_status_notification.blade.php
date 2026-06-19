<p>Hallo {{ $order->customer->first_name }},</p>

<p>Der Status Ihrer Bestellung <strong>#{{ $order->order_number }}</strong> wurde auf <strong>{{ ucfirst($status) }}</strong> aktualisiert.</p>

@if($note)
<p>Hinweis vom Administrator: {{ $note }}</p>
@endif

<p>Vielen Dank, dass Sie unseren Service nutzen!</p>