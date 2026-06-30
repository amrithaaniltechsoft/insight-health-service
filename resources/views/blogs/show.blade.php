@extends('adminlte::page')

@section('title', $blog->title)

@section('content_header')
    <div class="row">
        <div class="col-md-10">
            <h1>{{ $blog->title }}</h1>
        </div>
        <div class="col-md-2 text-right">
            <a href="{{ route('blogs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back to Articles
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="row no-gutters">
                @if($blog->image)
                <div class="col-md-5">
                    <img src="{{ str_starts_with($blog->image, 'http') ? $blog->image : asset($blog->image) }}" class="card-img h-100" alt="{{ $blog->title }}" style="object-fit: cover; max-height: 300px;">
                </div>
                @endif
                <div class="{{ $blog->image ? 'col-md-7' : 'col-md-12' }}">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center mb-3 text-muted small">
                            <a href="{{ route('blogs.category', $blog->category->id) }}" class="badge badge-primary">{{ $blog->category->name }}</a>
                        </div>
                        <div class="blog-content">
                            <p>{!! nl2br(e($blog->description)) !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">SEO Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Meta Title:</strong>
                    <p class="mb-0 text-muted">{{ $blog->meta_title ?? 'N/A' }}</p>
                </div>
                <div class="mb-2">
                    <strong>Meta Description:</strong>
                    <p class="mb-0 text-muted">{{ $blog->meta_description ?? 'N/A' }}</p>
                </div>
                <div class="mb-0">
                    <strong>Meta Keywords:</strong>
                    <p class="mb-0 text-muted">{{ $blog->meta_keywords ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if($relatedBlogs->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Related Articles</h5>
                </div>
                <div class="card-body p-0">
                    @foreach($relatedBlogs as $related)
                        <a href="{{ route('blogs.show', $related->id) }}" class="d-flex align-items-center p-3 border-bottom text-dark text-decoration-none">
                            <div class="mr-3">
                                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-file-alt text-white"></i>
                                </div>
                            </div>
                            <div class="small">
                                <strong>{{ Str::limit($related->title, 35) }}</strong>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
