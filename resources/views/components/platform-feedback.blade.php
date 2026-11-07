<div role="status" class="space-y-3">
    @if (session('status'))
        <flux:callout icon="check-circle" variant="success">{{ session('status') }}</flux:callout>
    @endif
    @foreach ($errors->getBags() as $bag)
        @if ($bag->any())
            <flux:callout icon="exclamation-circle" variant="danger">{{ $bag->first() }}</flux:callout>
        @endif
    @endforeach
</div>
