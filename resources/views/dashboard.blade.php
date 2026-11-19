@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_description', 'Selamat datang, ' . auth()->user()->name)

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
        <i class="fa-solid fa-house text-5xl text-slate-300"></i>
        <h3 class="mt-4 text-lg font-semibold text-slate-700">Dashboard</h3>
        <p class="mt-1 text-slate-400">Ringkasan data akan ditampilkan di sini (Fase 5).</p>
    </div>
@endsection
