<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //Verificamos si hay usuario y si su rol es administrador
        if($request->user() && $request->user()->role === "admin") {
            return $next($request);
        }
        
        //Si el usuario no es admin denegamos el paso
      return response()->json([
            'message' => 'Acceso denegado. Se requieren permisos de Administrador.'
        ], 403);
    }
}
