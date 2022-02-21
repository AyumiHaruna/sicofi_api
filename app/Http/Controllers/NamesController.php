<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class NamesController extends Controller
{
  // get list of names
  public function list()
  {
    $people = DB::select("SELECT * FROM people ORDER BY name, active");

    // else user not found or incorrect
    return response()->json([
      'res' => true,
      'status' => 404,
      'results' => $people
    ]);
  }

  // create or update name
  public function save(Request $request)
  {
    $input = $request->json()->all();

    if( $input['active'] == null ){
      $input['active'] = 0;
    }

    // if has not id, create
    if( $input['id'] == '' ) {
      // check if name already exists
      $name = DB::select("SELECT * FROM people WHERE name = '".$input['name']."' LIMIT 1");
      // if not exists
      if( count($name) == 0){
        // create name
        DB::insert("INSERT INTO people (name, active) VALUES('".$input['name']."', ".$input['active'].")");
        // return success msg
        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => 'ok'
        ]);
      } else {
        // if exists, return error
        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => 'exists'
        ]);
      }
    }

    // else update
    else {
      DB::update("UPDATE people SET name = '".$input['name']."', active = ".$input['active']." WHERE id = ".$input['id']);
      // return success msg
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }
  }
}
