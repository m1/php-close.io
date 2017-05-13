<?php

namespace m1\Tepilo;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

/**
 * Class CloseService
 *
 * @package m1\Tepilo
 * @author  Miles Croxford <hello@milescroxford>
 */
class CloseService
{
    /**
     * Service code constants
     */
    const SERVICE_CODE_OK              = 1;
    const SERVICE_CODE_INVALID_EMAIL   = 2;
    const SERVICE_CODE_INVALID_REQUEST = 3;

    /**
     * The endpoints for the close.io API
     */
    const ENDPOINT_LEAD        = 'lead/';
    const ENDPOINT_LEAD_SINGLE = 'lead/%s/';

    /**
     * Custom field ids for the close.io API
     */
    const CF_NUMBER_OF_VALUATIONS = "lcf_GEg9856gXoijAlF67G7ZXDtjSlycfNDxTaSyM6labnW";

    /**
     * @var Client The guzzle client
     */
    private $client;

    /**
     * Create a new CloseService
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => 'https://app.close.io/api/v1/',
            'timeout'  => 5.0,
            'auth'     => [$apiKey, ''],
            'headers'  => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * @param Lead $lead
     *
     * @return string
     */
    public function updateValuationsOrCreateLead(Lead $lead): string
    {
        if (!$this->isValidEmail($lead->email)) {
            return $this->respond(false, self::SERVICE_CODE_INVALID_EMAIL);
        }

        $searchLead = $this->searchLead($lead->email);

        if (!$searchLead->total_results) {
            return $this->createLead($lead);
        }

        return $this->incrementValuationsLead($searchLead->data[0]);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param bool $success
     * @param int  $statusCode
     * @param null $data
     *
     * @return string
     */
    public function respond(bool $success, int $statusCode, $data = null): string
    {
        $responseData = [
            'status'      => $success ? 'OK' : 'ERROR',
            'status_code' => $statusCode,
        ];

        if (!is_null($data)) {
            $responseData[$success ? 'data' : 'error'] = $data;
        }

        return json_encode($responseData);
    }

    /**
     * @param string $email
     *
     * @return \stdClass
     */
    public function searchLead(string $email): \stdClass
    {
        $response = $this->client->get(self::ENDPOINT_LEAD, [
            'query' => ['query' => sprintf('email:%s', $email)],
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @param Lead $lead
     *
     * @return string
     */
    public function createLead(Lead $lead)
    {
        $success    = true;
        $statusCode = self::SERVICE_CODE_OK;

        try {
            $response = $this->client->post(self::ENDPOINT_LEAD, [
                'json' => $lead->toArray(),
            ]);

            $data = json_decode($response->getBody());
        } catch (RequestException | ClientException $e) {
            $success    = false;
            $statusCode = self::SERVICE_CODE_INVALID_REQUEST;
            $data       = $e->getMessage();
        }

        return $this->respond($success, $statusCode, $data);
    }

    /**
     * @param \stdClass $lead
     *
     * @return string
     */
    public function incrementValuationsLead(\stdClass $lead): string
    {
        $valuations   = 1;
        $cfValuations = sprintf("custom.%s", self::CF_NUMBER_OF_VALUATIONS);

        if (property_exists($lead, $cfValuations)) {
            $valuations += $lead->$cfValuations;
        }

        $success    = true;
        $statusCode = self::SERVICE_CODE_OK;

        try {
            $response = $this->client->put(sprintf(self::ENDPOINT_LEAD_SINGLE, $lead->id), [
                'json' => [$cfValuations => $valuations],
            ]);

            $data = json_decode($response->getBody());
        } catch (RequestException | ClientException $e) {
            $success    = false;
            $statusCode = self::SERVICE_CODE_INVALID_REQUEST;
            $data       = $e->getMessage();
        }

        return $this->respond(
            $success,
            $statusCode,
            $data
        );
    }
}
