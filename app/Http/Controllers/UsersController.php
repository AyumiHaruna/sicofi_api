<?php

namespace App\Http\Controllers;

use DB;
// use App\Users;
use Illuminate\Http\Request;

class UsersController extends Controller
{
  // test user login
  public function login(Request $request)
  {
    // get input data
    $input = $request->json()->all();
    // make DB search
    $user = DB::select("SELECT id, user, name, level, mail, fullAccess FROM users
        WHERE user = '".$input[0]['user']."'
        AND password = '".$input[0]['password']."'
        AND active = 1");

    // if searched user is found
    if( count($user) > 0 ){
        //if user found, check if hasn't fullAccess & get their projects
        if( $user[0]->fullAccess == 0 ){
          $projectList = DB::select("SELECT projectNumber FROM useraccess WHERE userId = ".$user[0]->id);
          //generate array from projectList
          $arrayProy = [];
          foreach ($projectList as $proy) {
            $arrayProy[] = $proy->projectNumber;
          }
          //push in array
          $user[0]->projectList = $arrayProy;
        }

        // return ok info object
        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => $user[0]
        ]);
    }

    // else user not found or incorrect
    return response()->json([
      'res' => true,
      'status' => 404,
      'message' => 'Usuario o contraseña incorrectos'
    ]);
  }

  // list all users and projects
  public function list()
  {
      // get list of users
      $users = DB::select("SELECT id, user, password, name, level, mail, fullAccess, active FROM users ORDER BY active DESC, name");

      // get list of projects for non fullAccess users
      foreach ($users as $key => $user) {
        if( !$user->fullAccess ){
          // $user->projectList = DB::select("SELECT * FROM useraccess WHERE userId = $user->id");
          $projects = DB::select("SELECT projectNumber FROM useraccess WHERE userId = $user->id");

          foreach ($projects as $key => $project) {
            $user->projectList[] = $project->projectNumber;
          }

        }
      }

      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => $users
      ]);
  }

  // create a new user
  public function create(Request $request)
  {
      $input = $request->json()->all();

      if( $input['fullAccess'] == null ){   $input['fullAccess'] = 0;   }
      if( $input['active'] == null ){   $input['active'] = 0;   }

      // check if user already exists
      $user = DB::select("SELECT user FROM users WHERE user = '".$input['user']."' LIMIT 1");

      // if not exists
      if( count($user) == 0){
        // create user
        $query = "INSERT INTO users (user, password, name, level, mail, fullAccess, active)
          VALUES('".$input['user']."', '".$input['password']."', '".$input['name']."', ".$input['level'].", '".$input['mail']."', ".$input['fullAccess'].", ".$input['active'].")";
        DB::insert($query);

        // if fullAcess == 0 create projectList
        // if( $input['fullAccess'] == 0 ){
        // if( isset($input['projectList']) ) {
        if( count($input['projectList']) > 0 ){
          // get inserted user id
          $userId = DB::select(" SELECT id FROM users WHERE user = '".$input['user']."' LIMIT 1");
          $userId = $userId[0]->id;

          $query = "INSERT INTO userAccess (userId, projectNumber) VALUES ";
          foreach ($input['projectList'] as $key => $project) {
            $query .= "( $userId, '$project' )";
            if( $key != count($input['projectList']) - 1){
              $query .= ", ";
            }
          }
          $query .= ';';
          DB::insert($query);
        }

        // return success msg
        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => 'ok'
        ]);
      }

      // if exists, return error
      else {
        // print_r($projects);
        return response()->json([
          'res' => true,
          'status' => 200,
          'results' => 'exists'
        ]);
      }

      // return $input;
  }

  // update an existing user
  public function update(Request $request)
  {
      $input = $request->json()->all();
      // print_r($input);
      if( $input['fullAccess'] == null ){   $input['fullAccess'] = 0;   }
      if( $input['active'] == null ){   $input['active'] = 0;   }

      // update main user data
      $query = "UPDATE users SET name='".$input['name']."', password='".$input['password']."',
        level=".$input['level'].", mail='".$input['mail']."', fullAccess=".$input['fullAccess'].",
        active=".$input['active']." WHERE id = ".$input['id'];
      DB::update($query);

      // delete old user projects
      $query = "DELETE FROM useraccess WHERE userId = ".$input['id'];
      DB::delete($query);

      // if( isset($input['projectList']) ) {
      // if($input['fullAccess'] == 0){
      if( count($input['projectList']) > 0 ){
        $query = "INSERT INTO userAccess (userId, projectNumber) VALUES ";
        foreach ($input['projectList'] as $key => $project) {
          $query .= "( ".$input['id'].", '$project' )";
          if( $key != count($input['projectList']) - 1){
            $query .= ", ";
          }
        }
        $query .= ';';
        DB::insert($query);
      }

      // return success msg
      return response()->json([
        'res' => true,
        'status' => 200,
        'results' => 'ok'
      ]);
  }

  // public function show($id)
  // {
  //     // return Usuarios::findOrFAil($id);
  //     $users = DB::select(DB::raw('select * from users WHERE no = '.$id));
  //     return $users;
  // }


}
