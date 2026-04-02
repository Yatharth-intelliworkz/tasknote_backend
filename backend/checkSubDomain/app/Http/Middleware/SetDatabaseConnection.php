<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SetDatabaseConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve 'code' from the route parameters
        $code = $request->route('code');

        // Find the user based on the 'code' (adjust the query according to your model)
        $user = User::where('custom_code', $code)->first();

        if ($user) {
            // Set the database based on user properties
            Config::set('database.connections.mysql.database', $user->db_name);
            Config::set('database.connections.mysql.username', $user->db_username);
            Config::set('database.connections.mysql.password', $user->db_password);

            // Purge and reconnect the database connection
            DB::purge('mysql');
            DB::reconnect('mysql');

            // Check if connection is valid
            try {
                DB::connection('mysql')->getPdo();
                // Log success or perform additional actions as needed
            } catch (\Exception $e) {
                // Log or handle errors
                return response()->json(['error' => 'Failed to connect to the database: ' . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'No user found with the specified code'], 404);
        }

        return $next($request);
    }
}
