<div class="h-full d-flex">
    <!-- Debug Info -->
    <div class="p-4 bg-yellow-100 border border-yellow-300 rounded mb-4">
        <h4 class="font-bold text-yellow-800">Debug Info:</h4>
        <p><strong>SalesBoard ID:</strong> {{ $salesBoard->id ?? 'NULL' }}</p>
        <p><strong>SalesBoard Name:</strong> {{ $salesBoard->name ?? 'NULL' }}</p>
        <p><strong>Groups Type:</strong> {{ gettype($groups) }}</p>
        <p><strong>Groups Count:</strong> {{ $groups ? $groups->count() : 'NULL' }}</p>
        <p><strong>Groups Empty:</strong> {{ $groups ? ($groups->isEmpty() ? 'YES' : 'NO') : 'NULL' }}</p>
        @if($groups && $groups->count() > 0)
            <p><strong>First Group:</strong> {{ $groups->first()->name ?? 'NO NAME' }}</p>
            <p><strong>First Group Tasks Type:</strong> {{ gettype($groups->first()->tasks ?? null) }}</p>
            <p><strong>First Group Tasks Count:</strong> {{ $groups->first()->tasks ? $groups->first()->tasks->count() : 'NULL' }}</p>
        @endif
    </div>
</div>