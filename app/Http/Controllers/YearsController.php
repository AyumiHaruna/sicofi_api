<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class YearsController extends Controller
{
    // show list of active years
    public function list()
    {
          $activeYears = DB::select("SELECT * FROM activeYears");
          // return ok info object
          return response()->json([
            'res' => true,
            'status' => 200,
            'results' => $activeYears
          ]);
    }

    // create a new active year
    public function create(Request $request)
    {
      $input = $request->json()->all();

      DB::insert("INSERT INTO activeYears (year) VALUES (".$input['year'].")");

      // else user not found or incorrect
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }

    // delete an active year
    public function delete(Request $request)
    {
      $input = $request->json()->all();

      DB::delete("DELETE FROM activeYears WHERE id = ".$input['id']);

      // else user not found or incorrect
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }



    // authorization table functions
    // get authTable SDO_DAS_DataObject
    public function authList()
    {
        $authList = DB::select("SELECT * FROM authTable");
        $authList = $authList[0];

        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => $authList
        ]);
    }

    public function authSave(Request $request)
    {
      $input = $request->json()->all();

      DB::update("UPDATE authTAble SET coord = '".$input['coord']."', admin = '".$input['admin']."',
        auth = '".$input['auth']."', elab = '".$input['elab']."' WHERE id = 1");

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
    }
}
