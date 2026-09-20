<div class="search-panel" role="dialog" aria-label="Search">
    <form action="{{ route('search') }}" method="get">
        <input type="search" name="q" placeholder="Search publications, events, projects and news…" value="{{ request('q') }}" aria-label="Search">
        <button class="btn btn-primary" type="submit">{!! icon('search') !!} Search</button>
    </form>
    <p class="search-hint">Press <strong>Esc</strong> to close</p>
</div>
