@extends('layouts.app')
@section('title', 'Welcome to the Ticket Tracker')

@section('content')

{{-- 1. Hero Section --}}
<div class="p-5 mb-4 bg-light rounded-3 shadow-sm">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold text-primary">Tickets! Your Project, Simplified.</h1>
        <p class="col-md-8 fs-4 text-muted">
            The modern, professional platform for tracking tasks, managing milestones, and coordinating releases with your team. Focus on delivery, not friction.
        </p>
        <a href="/tickets" class="btn btn-primary btn-lg mt-3 shadow-sm">
            <i class="fas fa-list-alt me-2"></i> View All Tickets
        </a>
        <a href="#github-section" class="btn btn-outline-secondary btn-lg mt-3 ms-2">
            <i class="fab fa-github me-2"></i> Report an Issue
        </a>
    </div>
</div>

{{-- 2. Feature Section --}}
<h2 class="text-center mb-5 fw-bold text-dark">Powerful Features, Built for Speed</h2>

<div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
    
    {{-- Feature 1: Milestone Tracking --}}
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="fs-1 text-info mb-3">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="card-title h4 fw-bold">Milestone Management</h3>
                <p class="card-text text-muted">Plan and track sprints, releases, and deadlines with granular control. See progress instantly across all associated tickets.</p>
            </div>
        </div>
    </div>
    
    {{-- Feature 2: Team Roles --}}
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="fs-1 text-success mb-3">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3 class="card-title h4 fw-bold">Clear Role Assignments</h3>
                <p class="card-text text-muted">Assign owners and scrum masters to milestones and tickets, ensuring accountability and smooth workflow for every task.</p>
            </div>
        </div>
    </div>
    
    {{-- Feature 3: Real-Time Updates --}}
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="fs-1 text-warning mb-3">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="card-title h4 fw-bold">Detailed Reporting</h3>
                <p class="card-text text-muted">Log estimated and actual time on tasks, track story points, and review activity history for accurate project health metrics.</p>
            </div>
        </div>
    </div>
</div>

{{-- 3. GitHub/Issue Reporting Section --}}
<div id="github-section" class="p-5 mb-4 bg-dark text-white rounded-3 shadow-lg">
    <div class="container-fluid py-5">
        <h2 class="fw-bold mb-4">Found a Bug? Have an Idea?</h2>
        <p class="fs-5 mb-4">
            We use GitHub for all external contributions, feature requests, and bug reports. Here’s how you can help improve the platform:
        </p>
        
        <div class="row g-4">
            {{-- Step 1 --}}
            <div class="col-md-4">
                <h3 class="h5">1. <i class="fab fa-github me-2"></i> Visit the Repository</h3>
                <p class="small text-white-50">Navigate to our official project repository on GitHub.</p>
                <a href="https://github.com/velkymx/tickets" target="_blank" class="btn btn-outline-light btn-sm">
                    Go to Repo <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            
            {{-- Step 2 --}}
            <div class="col-md-4">
                <h3 class="h5">2. <i class="fas fa-bug me-2"></i> Open a New Issue</h3>
                <p class="small text-white-50">Click the 'Issues' tab and then click 'New issue'. Choose the appropriate template (Bug Report or Feature Request).</p>
            </div>

            {{-- Step 3 --}}
            <div class="col-md-4">
                <h3 class="h5">3. <i class="fas fa-edit me-2"></i> Describe Your Finding</h3>
                <p class="small text-white-50">Provide all necessary details, including steps to reproduce the bug or a clear explanation of your feature idea. Submit and we'll take a look!</p>
            </div>
        </div>
    </div>
</div>

@endsection