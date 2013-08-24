@extends('layouts.master')

@section('content')

  <div class="panel panel-primary">

    <div class="panel-heading">
      <h3 class="panel-title">Lån #{{ $loan->id }}</h3>
    </div>

    @if (empty($loan->deleted_at))
    <a href="{{ URL::action('LoansController@getDestroy', $loan->id) }}" style="float:right">Returnér dokument</a>
    @endif

    <div class="row">
      <div class="col-2">
        <strong>Ting:</strong>
      </div>
      <div class="col-6">
        {{ $loan->document->thing->name }}
      </div>
    </div>

    @if ($loan->document->thing->id == 1)
      <div class="row">
        <div class="col-2">
          <strong style="padding-left:10px;">Dokid:</strong>
        </div>
        <div class="col-6">
          <a href="{{ URL::action('DocumentsController@getShow', $loan->document->id) }}" style="padding-left:10px;">
            {{ $loan->document->dokid }}
          </a>
        </div>
      </div>
    @endif

    <div class="row">
      <div class="col-2">
        <strong>Låntaker:</strong>
      </div>
      <div class="col-6">
        <a href="{{ URL::action('UsersController@getShow', $loan->user->id) }}">
          {{ $loan->user->lastname }},
          {{ $loan->user->firstname }}
        </a>
      </div>
    </div>

    <div class="row">
      <div class="col-2">
        <strong>Utlånt:</strong>
      </div>
      <div class="col-6">
        {{ $loan->created_at }}
      </div>
    </div>

    <div class="row">
      <div class="col-2">
        <strong>Forfall:</strong>
      </div>
      <div class="col-6">
        {{ $loan->due_at }}
      </div>
    </div>

    <div class="row">
      <div class="col-2">
        <strong>Returnert:</strong>
      </div>
      <div class="col-6">
        {{ $loan->deleted_at }}
      </div>
    </div>

  </div>

@stop
