<div class="h-full d-flex">
    <!-- Minimale Debug-Version -->
    <div class="p-4">
        <h1>Sales Board Debug</h1>
        
        <h2>SalesBoard Info:</h2>
        <p>ID: {{ $salesBoard->id ?? 'NULL' }}</p>
        <p>Name: {{ $salesBoard->name ?? 'NULL' }}</p>
        
        <h2>Groups Info:</h2>
        <p>Groups Type: {{ gettype($groups) }}</p>
        <p>Groups Class: {{ $groups ? get_class($groups) : 'NULL' }}</p>
        <p>Groups Count: {{ $groups ? $groups->count() : 'NULL' }}</p>
        
        @if($groups && $groups->count() > 0)
            <h3>Gruppen Details:</h3>
            @foreach($groups as $index => $group)
                <div class="border p-2 mb-2">
                    <p><strong>Gruppe {{ $index + 1 }}:</strong></p>
                    <p>ID: {{ $group->id ?? 'NULL' }}</p>
                    <p>Name: {{ $group->name ?? 'NULL' }}</p>
                    <p>Label: {{ $group->label ?? 'NULL' }}</p>
                    <p>Deals Type: {{ gettype($group->deals ?? null) }}</p>
                    <p>Deals Class: {{ $group->deals ? get_class($group->deals) : 'NULL' }}</p>
                    <p>Deals Count: {{ $group->deals ? $group->deals->count() : 'NULL' }}</p>
                </div>
            @endforeach
        @endif
    </div>
</div>