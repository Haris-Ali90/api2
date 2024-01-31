<?php

namespace App\Classes;

use App\JoeyRoute;
use App\JoeyRouteLocations;
use App\LogRoutes;
use App\SlotJob;
use \Laravel\Log;
use \Laravel\Config;
use \JoeyCo\Exception\RoutingException;

class RoutificClient {

    private $proto = 'https://';
    private $host = 'api.routific.com';
    private $resource = '/';
    private $version = '1';
    private $jobId = '';

    /**
     * @var array
     */
    private $data = [];

    public function __construct($resource) {
        $this->setResource($resource);
    }

    public function setJobID($jobId) {
        $this->jobId = $jobId;
    }

    public function setResource($resource) {
        $this->resource = $resource;
    }

    public function setData(array $data) {
        $this->data = $data;
    }

    private function getEndpoint() {
        return $this->proto . $this->host . '/v' . $this->version . $this->resource;
    }

    public function getJobId() {
        return $this->jobId;
    }

    public function getJobResults() {

        $host = 'api.routific.com';

        if (json_last_error() != JSON_ERROR_NONE) {
            Log::write('error', 'Failed to JSON encode Routific request body');
            throw new RoutingException();
        }
        $requestBody="";
        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'User-Agent: JoeyCo',
            'Host: ' . $this->host,
            'Authorization: bearer ' . 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI1Njk5ZDJjODUzNWFkMTBkMWQ0YmFlMTgiLCJpYXQiOjE2MDA0NDM0MTB9.ZS_LvnToeLObd3IdAuy5JEviQFjDHiEzaJac5P_w_b0',
            'Content-Length: ' . strlen($requestBody)
        ];

        $ch = curl_init($this->proto . $this->host . "/jobs/". $this->getJobId());
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $responseData = curl_exec($ch);

        $error = curl_error($ch);
        curl_close($ch);

        if (empty($error)) {
            $response = explode("\n", $responseData);
            $httpCode = explode(' ', $response[0]);
            $httpCode = $httpCode[1];

            if ($httpCode >= 300) {
                //   Log::write('error', print_r($responseData, true));
                //throw new RoutingException();
            }

            $results = $response[count($response) - 1];
            $results = json_decode($results, true);

            if (json_last_error() != JSON_ERROR_NONE) {
                //  Log::write('error', print_r($responseData, true));
                //throw new RoutingException();
            }
        } else {
            $results = [];
        }

        return $results;
    }



    /**
     * @throws RoutingException
     */
    public function send() {

        $host = 'api.routific.com';
        $requestBody = json_encode($this->data);

        if (json_last_error() != JSON_ERROR_NONE) {
            Log::write('error', 'Failed to JSON encode Routific request body');
            throw new RoutingException();
        }

        $headers = [
            'Accept-Encoding: utf-8',
            'Accept: application/json; charset=UTF-8',
            'Content-Type: application/json; charset=UTF-8',
            'User-Agent: JoeyCo',
            'Host: ' . $this->host,
            'Authorization: bearer ' . 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJfaWQiOiI1Njk5ZDJjODUzNWFkMTBkMWQ0YmFlMTgiLCJpYXQiOjE2MDA0NDM0MTB9.ZS_LvnToeLObd3IdAuy5JEviQFjDHiEzaJac5P_w_b0',
            'Content-Length: ' . strlen($requestBody)
        ];
// phpinfo();
// die();
        $ch = curl_init($this->getEndpoint());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $responseData = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (empty($error)) {
            $response = explode("\n", $responseData);
            $httpCode = explode(' ', $response[0]);
            $httpCode = $httpCode[1];

            if ($httpCode >= 300) {
                // Log::write('error', print_r($responseData, true));

                // throw new RoutingException();
            }

            $results = $response[count($response) - 1];
            $results = json_decode($results);

            if (json_last_error() != JSON_ERROR_NONE) {
                Log::write('error', print_r($responseData, true));
                // throw new RoutingException();
            }
        } else {
            $results = [];
        }

        return $results;
    }
}
