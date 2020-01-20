<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\Stations;
use App\Entity\User;

use App\Services\JwtAuth;

class UserController extends AbstractController {

    private function resjson($data) {
//Serializar datos con servicio serializer
        $json = $this->get('serializer')->serialize($data, 'json');
//response con http fundation
        $response = new Response();
//Asignar contenido de la respuesta
        $response->setContent($json);
//indicar formato de repuesta
        $response->headers->set('Content-Type', 'application/json');
//devolver la respuesta
        return $response;
    }

    public function index() {
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $users = $user_repo->findAll();

        return $this->resjson($users);
    }

    public function create(Request $request) {
///recoger los parametros por post
        $json = $request->get('json', null);
//decode json
        $params = json_decode($json);

//respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'error no se ha creado el usuario'
        ];


//comprobar y validar los datos
        if ($json != null) {

            $name = (!empty($params->name)) ? $params->name : null;
            $surname = (!empty($params->surname)) ? $params->surname : null;
            $username = (!empty($params->username)) ? $params->username : null;
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);


            if (!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($name) && !empty($surname) && !empty($username)) {

                //crear el objeto del usuario
                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setRole('ROLE_USER');
                $user->setCreatedAt(new \Datetime('now'));

                //cifrar la password
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                //comprobar si el no usuario existe
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();

                $user_repo = $doctrine->getRepository(User::class);

                $isset_email = $user_repo->findBy(array(
                    'email' => $email
                ));


                $isset_username = $user_repo->findBy(array(
                    'username' => $username
                ));


                if (count($isset_email) == 0 && count($isset_username)==0) {
                    //guardar en la bd
                    $em->persist($user);
                    $em->flush();
                    //devolver una respuesta
                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'el usuario se ha creado.',
                        'error' => $user
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'el usuario o correo ya existe'
                    ];
                }
            }
        }
        return new JsonResponse($data);
    }
    
    public function login(Request $request, JwtAuth $jwt_auth){
        //recibir los datos por post
        $json = $request->get('json', null);
        $params = json_decode($json);
        //Array por defecto
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'Usuario no identificado'
        ];
        //comprobar y validar
        if ($json != null) {
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if (!empty($email) && !empty($password) && count($validate_email) == 0) {
                //cifrar la contraeña
                $pwd = hash('sha256', $password);

                //identificar al usuario devolver token
              
                if ($gettoken) {
                    $signup = $jwt_auth->signup($email, $pwd, $gettoken);
                } else {
                    $signup = $jwt_auth->signup($email, $pwd);
                }

                //crear servicio de jwt

                return new JsonResponse($signup);
            }
        }
        //respuesta 
        return $this->resjson($data);
    }
    
      public function edit(Request $request, JwtAuth $jwt_auth)
    {

        //recoger la cabecera de auth
        $token = $request->headers->get('Authorization');
        //crear un metodo para comprobar si el token e correcto
        $authCheck = $jwt_auth->checkToken($token);

        //respuesta por defecto

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario no actualizado'
        ];


        if ($authCheck) {
            //Actualizacion del usuario

            //conseguir entity manager

            $em = $this->getDoctrine()->getManager();
            //conseguir lo datos del usuario identificado
            $identity = $jwt_auth->checkToken($token, true);

            //conseguir el usuario a actualizar completo
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'id' => $identity->sub
            ]);
            //recoger lo datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);
            if (!empty($json)) {
                //recoger y validar lo datos
                $name = (!empty($params->name)) ? $params->name : null;
                $surname = (!empty($params->surname)) ? $params->surname : null;
                $email = (!empty($params->email)) ? $params->email : null;

                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);

                if (!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname)) {
                    //asignar nuevos datos 
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);
                    //comprobar duplicados
                    $isset_user =  $user_repo->findBy([
                        'email' => $email
                    ]);
                       
                    if (count($isset_user) == 0 || $identity->email == $email) {
                        //guardar cambios
                        $em->persist($user);
                        $em->flush();
                        
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Usuario actualizado',
                            'user' => $user
                        ];
                    }else{
                        $data = [
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'No puedes usar ese email'
                        ];
                    }
                }
            }
        }


        return $this->resjson($data);
    }
       

}
