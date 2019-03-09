@extends('shopper::layouts.dashboard')
@section('title', (isset($record->id)) ? __('Edit order') : __('New order'))

@section('content')

    <div class="wrapper-md">
        <div class="pull-left">
            <div class="btn-group btn-breadcrumb breadcrumb-default">
                <a href="{{ route('shopper.dashboard.home') }}" class="btn btn-default"><i class="fa fa-home"></i></a>
                <a href="{{ route('shopper.shoporders.orders.index') }}" class="btn btn-default visible-lg-block visible-md-block">{{ __('Orders list') }}</a>
                <div class="btn btn-info"><b>{{ (isset($record->id)) ? __('Edit order') : __('New order') }}</b></div>
            </div>
        </div>
        <div class="pull-right">
            @if ((isset($record->id)))
                <form action="{{ route('shopper.shoporders.orders.destroy', $record) }}" class="record-delete" method="POST">
                    {{ method_field("DELETE") }}
                    {{ csrf_field() }}
                    <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> {{ __('Delete') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div id="order-form" class="m-t-md" data-id="@if(isset($record)) {{ $record->id }} @endif" data-devise="{{ setting('site_currency') }}"></div>

@stop
