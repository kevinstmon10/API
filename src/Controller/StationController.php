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
use App\Entity\Sensors;
use App\Entity\Data;
use App\Services\JwtAuth;

class StationController extends AbstractController {

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

    public function getStations(Request $request, JwtAuth $jwt_auth = null) {
        //respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 500,
            'message' => 'error no existen estaciones'
        ];

        //recoger el token
        $token = $request->headers->get('Authorization', null);

        //comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            //recuperamos los datos de la estacion 
            $em = $this->getDoctrine()->getManager();
            $data = $this->getDoctrine()->getRepository(Stations::class)->findAll();
        }

        return $this->resjson($data);
    }

    public function getSensorsByStation(Request $request, JwtAuth $jwt_auth = null, $id = null) {
        //respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 500,
            'message' => 'error no existen sensores en la estacion'
        ];
        //recoger el token
        $token = $request->headers->get('Authorization', null);
        //comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);
        if ($authCheck) {

            if ($id != null) {
                $em = $this->getDoctrine()->getManager();
                $data = $this->getDoctrine()->getRepository(Sensors::class)->findBy([
                    'stationid' => $id
                ]);
            }
            //recuperar los sensores de la estacion
        }
        return $this->resjson($data);
    }

    public function editSensorsById(Request $request, JwtAuth $jwt_auth = null, $id = null) {
        //respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 500,
            'message' => 'error al actualizar el sensor'
        ];
        //recoger el token
        $token = $request->headers->get('Authorization', null);
        //comprobar si es correcto
        $authCheck = $jwt_auth->checkToken($token);
        if ($authCheck) {
            //recuperar los datos 
            $json = $request->get('json', null);
            $params = json_decode($json);

            if ($id != null) {

                if (!empty($json)) {
                    $maxV = (!empty($params->maxvalue)) ? $params->maxvalue : null;
                    $minV = (!empty($params->minvalue)) ? $params->minvalue : null;

                    if (!empty($maxV) && !empty($minV)) {
                        //buscar el sensor a editar
                        $em = $this->getDoctrine()->getManager();
                        $sensor_repo = $this->getDoctrine()->getRepository(Sensors::class);
                        $sensor = $sensor_repo->findOneBy([
                            'id' => $id
                        ]);
                        //asginar los nuevos valores
                        $sensor->setMinValue($minV);
                        $sensor->setMaxValue($maxV);

                        //guardar los datos
                        $em->persist($sensor);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Sensor actualizado',
                            'sensor' => $sensor
                        ];
                    }
                }
            }
        }
        return $this->resjson($data);
    }

    public function historial(Request $request, JwtAuth $jwt_auth, $idStation = null, $id = null) {
        //respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 500,
            'message' => 'error al recuperar los datos'
        ];
        $em = $this->getDoctrine()->getManager();
        $sensor_repo = $this->getDoctrine()->getRepository(Sensors::class);
        $data_repo = $this->getDoctrine()->getRepository(Data::class);
        //recogemos el token
        $token = $request->headers->get('Authorization', null);

        //verificamos el token
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            //verificamos el sensor
            $sensorExist = $sensor_repo->findOneBy([
                'id' => $id,
                'stationid' => $idStation
            ]);
            if ($sensorExist != null) {

                //recuperamos los datos
                $values = $data_repo->findBy([
                    'sensorid' => $id
                ]);
                //procesamos para highcharts
                ////recuperamos los datos
                foreach ($values as $value) {
                    $datos[] = array($value->getDate()->getTimestamp(), (int) $value->getValue());
                }
                $data = $datos;
            } else {
                $data = [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'no existe el sensor'
                ];
            }
        }

        return new JsonResponse($data);
    }

    public function tiempo_real(Request $request, JwtAuth $jwt_auth, $option = null, $idStation = null, $id = null) {
        //data por defecto
        $data = [
            'status' => 'error',
            'code' => 500,
            'message' => 'error al recuperar los datos'
        ];
        //recogemos el token
        $token = $request->headers->get('Authorization');
        //verificamos el token
        $authToken = $jwt_auth->checkToken($token);
        if ($authToken) {
            if ($option != null && $idStation != null && $id != null) {
                $em = $this->getDoctrine()->getManager();
                $conn = $em->getConnection();
                (int) $id;
                (int) $idStation;
                //recogemos la informacion
                switch ($option) {

                    case 1:
                        $sql = "CALL TiempoReal_Data1(:id, :idStation)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(['id' => $id, 'idStation' => $idStation]);
                        $datos = $stmt->fetchAll();
                        //preparamos los datos para un highcharts
                        if ($datos != null) {
                            //regresamos la respuesta
                            $data = $datos;
                        }
                        break;
                    default:
                        $sql = "CALL TiempoReal_DataDefault(:id, :idStation)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(['id' => $id, 'idStation' => $idStation]);
                        $datos = $stmt->fetchAll();
                        //preparamos los datos para un highcharts
                        if ($datos != null) {
                            //regresamos la respuesta
                            $data = $datos;
                        }
                       
                        break;
                }
            }
        }
        return new JsonResponse($data);
    }

}
