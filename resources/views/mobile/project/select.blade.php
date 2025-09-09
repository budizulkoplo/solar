@extends('layouts.mobile')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="pageTitle">Pilih Project</div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:50px">
    <form action="{{ route('mobile.project.set') }}" method="POST">

        @csrf
        <div class="mb-2">
            <select name="project_id" class="form-control" required>
                <option value="">-- Pilih Project --</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->nama_project }} ({{ $project->lokasi }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-3">Masuk</button>
    </form>
</div>
@endsection
