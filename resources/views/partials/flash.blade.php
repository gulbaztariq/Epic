@if (session('success'))
    <div class="alert alert-success">{!! icon('check') !!}<div>{{ session('success') }}</div></div>
@endif

@if (session('error'))
    <div class="alert alert-error">{!! icon('close') !!}<div>{{ session('error') }}</div></div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        {!! icon('close') !!}
        <div>
            <strong>Please check the form:</strong>
            <ul style="margin:.4rem 0 0;padding-left:1.1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
