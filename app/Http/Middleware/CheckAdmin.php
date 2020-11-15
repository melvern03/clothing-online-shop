<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;

use Closure;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(Auth::check()){
            if(Auth::user()->jabatan=="Admin" || Auth::user()->jabatan=="Owner"){
                Auth::logout();
            }else if(Auth::user()->status=="Verifikasi"){
                return redirect("/verifikasi")->with("verif","ok");
            }
        }
        return $next($request);
    }
}
