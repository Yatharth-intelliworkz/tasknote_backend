<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    public function dbCheck(Request $request, $account, $code)
    {
        dd($account);
        try {
            $testConnection = \DB::connection()->getPdo();
            $roles = User::orderBy('id','DESC')->get();
            dd($roles);
            return response()->json(['success' => true, 'message' => 'Database connection successful.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Database connection failed.', 'error' => $e->getMessage()]);
        }
    }
}
