@extends('adminlte::page')

@section('title', 'Blog Articles')

@section('content_header')
    <h1>Blog Articles</h1>
@endsection

@section('content')
<div class="row">
    <!-- Sidebar with Categories -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Categories</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="{{ route('blogs.index') }}" class="list-group-item list-group-item-action {{ isset($category) ? '' : 'active' }}">
                        All Articles
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blogs.category', $cat->id) }}" class="list-group-item list-group-item-action {{ isset($category) && $category->id === $cat->id ? 'active' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Blog Posts -->
    <div class="col-md-9">
        @if($blogs->count() > 0)
            <div class="row">
                @foreach($blogs as $blog)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            @if($blog->image)
                                <img src="{{ asset($blog->image) }}" class="card-img-top" alt="{{ $blog->title }}" style="height: 200px; object-fit: cover;">
                            @else
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            @endif
                            
                            <div class="card-body d-flex flex-column">
                                <a href="{{ route('blogs.show', $blog->id) }}">
                                    <h5 class="card-title">{{ $blog->title }}</h5>
                                </a>
                                
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-tag mr-1"></i>
                                    <span class="badge badge-primary">{{ $blog->category->name }}</span>
                                </p>
                                
                                <p class="card-text flex-grow-1">
                                    {{ Str::limit($blog->description, 100) }}
                                </p>
                                
                                <a href="{{ route('blogs.show', $blog->id) }}" class="btn btn-sm btn-primary">
                                    Read More <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="row">
                <div class="col-12">
                    {{ $blogs->links() }}
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                No articles found in this category.
            </div>
        @endif
    </div>
</div>
@endsection
