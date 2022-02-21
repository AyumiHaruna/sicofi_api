<?php

namespace App\Http\Controllers;

use DB;

class PeopleController extends Controller
{
    public function list()
    {
          $people = DB::select("SELECT * FROM people WHERE active = 1 ORDER BY vip DESC, name");
          // return ok info object
          return response()->json([
            'res' => true,
            'status' => 200,
            'results' => $people
          ]);
    }
}
