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

class InsertionController extends AbstractController {

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

    public function Insert(Request $request, $name = null) {
        //respuesta por defecto
        $sens_name;
        $data = [
            'status' => 'error',
            'code' => 500,
            'message' => 'error al insertar los datos'
        ];
        //obtener los datos
        $json = $request->get('json', null);
        $params = json_decode($json);

        $sensor_repo = $this->getDoctrine()->getRepository(Sensors::class);
        $em = $this->getDoctrine()->getManager();
        //obtener id de la estacion
        if ($name != null) {
            //verificar que la estacion exista
            $stations = $this->getDoctrine()->getRepository(Stations::class)->findOneBy([
                'name' => $name
            ]);
            if ($stations != null) {
                //obtener el id de la estacion
                $id = $stations->getId();

                //si la estacion existe
                //recorrer y verificar la existencia de los sensores
                foreach ($params->sensors as $sensor) {
                    $sens_name = $sensor->name;
                    $sens_value = (float) $sensor->value;
                    //echo $sens_name;
                    $sensorExist = $sensor_repo->findOneBy([
                        'name' => $sens_name,
                        'stationid' => $id
                    ]);
                    //Buscar el sensor e insertar los datos
                    if ($sensorExist != null) {
                        //si el sensor existe
                        $idSensor = $sensorExist->getId();
                        //creamos el objeto data
                        $ins = new Data();
                        $ins->setValue($sens_value);
                        $now = new \DateTime('now');
                        $ins->setDate($now);
                        $ins->setSensorid($sensorExist);

                        //insertar los datos
                        $em->persist($ins);
                        $em->flush();
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Datos insertados',
                            'datos' => $ins
                        ];
                    } else {
                        //crear el sensor
                        $sensorNew = new Sensors();
                        $sensorNew->setStationid($stations);
                        $sensorNew->setName($sens_name);
                        $sensorNew->setStatus("Enabled");
                        $sensorNew->setMinValue(null);
                        $sensorNew->setMaxValue(null);

                        //crear el nuevo sensor
                        $em->persist($sensorNew);
                        $em->flush();
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'sensor creado',
                            'sensor' => $sensorNew
                        ];
                    }
                }
            } else {
                //si la estacion no existe
                
                
                $station = new Stations();
                $station->setName($name);
                //crear la nueva estacion
                $em->persist($station);
                $em->flush();
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'estacion creada',
                    'sensor' => $station
                ];
            }
        }

        return $this->resjson($data);
    }

}
