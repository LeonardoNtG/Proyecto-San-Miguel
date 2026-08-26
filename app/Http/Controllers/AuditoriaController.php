<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Auditoria;

class AuditoriaController extends Controller
{
    public function index()
    {
        $auditorias = Auditoria::with('user')->orderBy('created_at', 'desc')->paginate(50);
        return view('auditoria.index', compact('auditorias'));
    }
}
