<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class LotificacionScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check() && session()->has('lotificacion_id')) {
            $builder->where($model->getTable() . '.lotificacion_id', session('lotificacion_id'));
        }
    }
}
