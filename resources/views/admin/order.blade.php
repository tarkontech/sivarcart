@extends('sivarcart-core::admin.layout')

@section('content')
    @livewire('sivarcart.orders.show', ['order' => $order])
@endsection
