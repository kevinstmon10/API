<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth
{

    public $manager;
    public $key;

    public function __construct($manager)
    {
        $this->manager = $manager;
        $this->key = 'clave-super-secreta-2019';
    }

    public function signup($email, $password, $gettoken = null)
    {
        
        //comprobar si el usuario existe 
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);
        $signup = false;
        if (is_object($user)) {
            $signup = true;
        }

        if ($signup) {
            //generar el token 

            $token = [
                'sub' => $user->getId(),
                'name' => $user->getName(),
                'username' => $user->getUsername(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            ];
            $jwt = JWT::encode($token, $this->key, 'HS256');
            //comprobar el flag gettoken
            if (!empty($gettoken)) {
                $data = $jwt;
            } else {
                $decode = JWT::decode($jwt, $this->key, ['HS256']);
                $data = $decode;
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'login incorrecto'
            ];
        }
        //devolver lo datos
        return $data;
    }


    public function checkToken($jwt, $identity = false){
        $auth = false;
        if($jwt != null){
         
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);

            if(isset($decoded)&& !empty($decoded) && is_object($decoded) && isset($decoded->sub)){
                $auth = true;
            }
            
            if($identity){
                return $decoded;
            }
   
        }   
        return $auth;
    }
}

