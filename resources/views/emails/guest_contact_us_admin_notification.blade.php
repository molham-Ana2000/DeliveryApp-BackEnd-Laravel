<h2>Neue Kontaktanfrage von einem Gast</h2>

<p>Eine neue Nachricht wurde über das Kontaktformular von einem Gast gesendet.</p>

<p>
    Name:
    <strong>{{ $contact->name }}</strong>
</p>

<p>
    E-Mail:
    <strong>{{ $contact->email }}</strong>
</p>

@if(!empty($contact->phone))
    <p>
        Telefon:
        <strong>{{ $contact->phone }}</strong>
    </p>
@endif

<p>
    Betreff:
    <strong>{{ $contact->subject }}</strong>
</p>

<p>
    Nachricht:
</p>

<p>
    {{ $contact->message }}
</p>

<p>
    Status:
    <strong>Neu</strong>
</p>

<p>Bitte bearbeiten Sie diese Anfrage so schnell wie möglich.</p>