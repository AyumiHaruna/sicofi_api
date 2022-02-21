<?php 

namespace App; 


use Illuminate\Database\Eloquent\Model;

class Usuarios extends Model
{
    protected $table = 'usuarios'; 
    protected $fillable = [
        'id',
        'password',
        'nombre', 
        'nivel',
        'mail',
        'proy1',
        'val'
    ];
}
