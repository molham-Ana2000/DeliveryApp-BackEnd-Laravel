<h2>Neue Bestellung erhalten!</h2>

<p>Bestellnummer: <strong>{{ $order->order_number }}</strong></p>

<p>
    Restaurant:
    <strong>{{ $order->restaurant->name ?? 'Unbekanntes Restaurant' }}</strong>
</p>

<p>
    Kunde:
    <strong>{{ $order->customer->first_name }} {{ $order->customer->last_name }}</strong>
</p>

<p>Lieferadresse: {{ $order->delivery_address }}</p>

<h4>Bestellte Artikel:</h4>

<ul>
    @foreach($order->items as $item)
        <li>
            {{ $item->quantity }} x {{ $item->item_name }}
            — {{ number_format($item->line_total, 2) }} €

            @if(!empty($item->customer_note))
                <br>
                <small>
                    <strong>Kundenhinweis:</strong> {{ $item->customer_note }}
                </small>
            @endif
        </li>
    @endforeach
</ul>

<p>
    <strong>Gesamtbetrag: {{ number_format($order->order_total, 2) }} €</strong>
</p>

<p>Bitte bearbeiten Sie diese Bestellung so schnell wie möglich.</p>