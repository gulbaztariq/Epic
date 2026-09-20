@props(['project'])

<article class="card">
    <a class="card-media is-wide" href="{{ route('work.projects.show', $project->slug) }}">
        <img src="{{ epic_image($project->image, 'card') }}" alt="{{ $project->title }}" loading="lazy">
        <span class="badge {{ $project->status === 'completed' ? 'badge-outline' : 'badge-green' }}">{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span>
    </a>
    <div class="card-body">
        <h3><a href="{{ route('work.projects.show', $project->slug) }}">{{ $project->title }}</a></h3>
        <p>{{ summarise($project->summary ?: $project->description, 140) }}</p>
        <div class="card-meta">
            @if ($project->category)
                <span>{!! icon('layers') !!}{{ $project->category }}</span>
            @endif
            @if ($project->started_at)
                <span>{!! icon('calendar') !!}{{ $project->started_at->format('Y') }}</span>
            @endif
        </div>
    </div>
</article>
