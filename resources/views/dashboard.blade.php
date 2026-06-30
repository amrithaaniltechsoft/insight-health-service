@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@endsection

@section('content')
    <div class="row">
        @foreach($categories as $cat)
        @php
            $colors = [
                4 => ['bg' => '#dc3545', 'icon' => 'fa-tint'],
                1 => ['bg' => '#28a745', 'icon' => 'fa-baby'],
                2 => ['bg' => '#17a2b8', 'icon' => 'fa-stethoscope'],
                3 => ['bg' => '#ffc107', 'icon' => 'fa-dumbbell'],
                5 => ['bg' => '#6f42c1', 'icon' => 'fa-flask'],
            ];
            $style = $colors[$cat->id] ?? ['bg' => '#401ce6', 'icon' => 'fa-concierge-bell'];
        @endphp
        <div class="col-lg-3 col-6">
            <div class="small-box" style="background: {{ $style['bg'] }}; color: white;">
                <div class="inner">
                    <h3>{{ $cat->services_count }}</h3>
                    <p>{{ $cat->name }}</p>
                </div>
                <div class="icon">
                    <i class="fas {{ $style['icon'] }}"></i>
                </div>
                <a href="{{ route('services.admin.category', $cat->id) }}" class="small-box-footer" style="color: rgba(255,255,255,0.8);">
                    View Services <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @endforeach
    </div>
@endsection
