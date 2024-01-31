<?php

namespace App\Classes;

use GuzzleHttp\Client;
use Exception;
use Predis\Response\ResponseInterface;

/**
 * @author Ahsaan Muhammad Yousuf <ahsankhatri1992@gmail.com>
 *
 * A wrapper to send two push notification to condition based to handle data push
 * because fcm don't allow us to send different payload on each platform, that's
 * why i had to handle separately.
 */

class PushNotification
{
    // Default attributes will be used explicited only.
    private const DEFAULT_PRIORITY  = 'normal';

    /*
     * Const condition for broadCast Messages
     * */
    private const broadCastCondition =  "'ios1' in topics || 'android1' in topics";

    /**
     * @const The API URL for Firebase
     */
    private const API_URI = 'https://fcm.googleapis.com/fcm/send';

    private $_config;
    private $_payload;
    private $_attributes;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string|array
     */
    private $to;

    /**
     * @var array
     */
    private $notification;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $condition;

    /**
     * @var string
     */
    private $collapseKey;

    /**
     * @var bool
     */
    private $contentAvailable;

    /**
     * @var bool
     */
    private $mutableContent;

    /**
     * @var int
     */
    private $timeToTive;

    /**
     * @var bool
     */
    private $dryRun;

    /**
     * @var string
     */
    private $restrictedPackageName;

    public function __construct()
    {
        $this->bootstrap();

        // TODO: Ability to set default values and overwrite values on demand feature. (LOW)
        // $this->setDefaultValues();
    }

    private function bootstrap() : void
    {
        $this->_attributes = [
            'topic_platforms'   => [
                'ios'     => 'ios1',
                'android' => 'android1',
            ],
            'priority' => self::DEFAULT_PRIORITY,
        ];
    }

    private function setDefaultValues()
    {
        //
    }

    public function generateCondition($userId, $platform, $topic) : string
    {
        // Default condition
        $condition = "'user_".$userId."' in topics && '".$topic."' in topics";

        $conditionMethod = 'generateConditionFor' . ucfirst(strtolower($platform));

        if ( method_exists($this, $conditionMethod) ) {
            $condition = $this->$conditionMethod($userId, $topic);
        }

        return $condition;
    }

    /**
     * Customized condition only for Android
     *
     * @param  array $payload
     * @return array
     */
    private function generatePayloadForAndroid($payload)
    {
        if ( array_key_exists('content', $payload) ) {
            foreach ($payload['content'] as $key => $value) {
                $payload['data']['data_'.$key] = $value;
            }
        }

        unset($payload['content']);

        return $payload;
    }


    /**
     * Generate payload if desired platform is not defined via method
     *
     * @param  array $payload
     * @param  string $platform
     * @return array
     */
    public function generatePayload($payload, $platform, $topic) : array
    {
        $payloadMethod = 'generatePayloadFor' . ucfirst(strtolower($platform));

        if ( method_exists($this, $payloadMethod) ) {
            $payload = $this->$payloadMethod($payload);
        }

        return $payload;
    }


    /*
     * Send methods Start
     * */
    public static function sendToUserConditionally($userId, array $payload) : void
    {
        $static = new static;

        foreach ($static->getAttribute('topic_platforms') as $platform => $topic) {
            $condition = $static->generateCondition($userId, $platform, $topic);
            $payload   = $static->generatePayload($payload, $platform, $topic);

            $static::sendToCondition( $payload, $condition );
        }
    }

    public static function broadCastToUsers(array $payload, array $recipients) : void
    {
        $static = new static;

        foreach ($static->getAttribute('topic_platforms') as $platform => $topic) {

            $deviceTokens = $recipients[$platform] ?? [];

            if(empty($deviceTokens)) {
                continue;
            }

            $payload = $static->generatePayload($payload, $platform, $topic);
            $static::sendToDevice( $recipients[$platform], $payload );
        }
    }

    public static function broadCastToEveryone(array $payload) : void
    {
        $static = new static;

        foreach ($static->getAttribute('topic_platforms') as $platform => $topic) {
            $payload   = $static->generatePayload($payload, $platform, $topic);
            $static::sendToTopic( $topic, $payload );
        }
    }

    public static function sendToDevice($device, array $payload)
    {
        $fcm = (new static)
            ->to( $device );

        if ( array_key_exists('content', $payload) ) {
            $fcm->content( $payload['content'] );
        }

        if ( array_key_exists('data', $payload) ) {
            $fcm->data( $payload['data'] );
        }

        return $fcm->send();
    }

    public static function sendToTopic($topicName, array $payload)
    {
        $fcm = (new static)->to( $topicName, true);

        if ( array_key_exists('content', $payload) ) {
            $fcm->content( $payload['content'] );
        }

        if ( array_key_exists('data', $payload) ) {
            $fcm->data( $payload['data'] );
        }

        return $fcm->send();
    }

    public static function sendToCondition(array $payload, $condition = '')
    {
        $condition = empty($condition) ? self::broadCastCondition : $condition;

        $fcm = (new static)
            ->condition( $condition );

        if ( array_key_exists('content', $payload) ) {
            $fcm->content( $payload['content'] );
        }

        if ( array_key_exists('data', $payload) ) {
            $fcm->data( $payload['data'] );
        }

        return $fcm->send();
    }

    public function send() : \Psr\Http\Message\ResponseInterface
    {
        $apiKey = $this->getApiKey();

        if (empty($apiKey)) {
            throw new \Mockery\Exception('Unable to find FCM Server key in environment.');
        }

        $payload = [
            'headers' => [
                'Authorization' => 'key=' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body' => $this->formatData(),
        ];

        return (new Client)->post(self::API_URI, $payload);
    }

    private function getAttribute($key)
    {
        if ( !array_key_exists($key, $this->_attributes) ) {
            throw new \Mockery\Exception('Container does not have ' . $key . ' attribute');
        }

        return $this->_attributes[$key];
    }

    /**
     * @param string|array $recipient
     * @param bool $recipientIsTopic
     * @return $this
     */
    public function to($recipient, $recipientIsTopic = false) : self
    {
        if ($recipientIsTopic && is_string($recipient)) {
            $this->to = '/topics/'.$recipient;
        }
        else {
            $this->to = $recipient;
        }

        return $this;
    }

    /**
     * The notification object to send to FCM. `title` and `body` are required.
     * @param array $params ['title' => '', 'body' => '', 'sound' => '', 'icon' => '', 'click_action' => '']
     * @return $this
     */
    public function content(array $params) : self
    {
        $this->notification = $params;

        return $this;
    }

    /**
     * @param array|null $data
     * @return $this
     */
    public function data($data = null) : self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $condition
     * @return $this
     */
    public function condition($condition) : self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @param string $collapseKey
     * @return $this
     */
    public function collapseKey($collapseKey) : self
    {
        $this->collapseKey = $collapseKey;

        return $this;
    }

    /**
     * @param bool $contentAvailable
     * @return $this
     */
    public function contentAvailable($contentAvailable) : self
    {
        $this->contentAvailable = $contentAvailable;

        return $this;
    }

    /**
     * @param bool $mutableContent
     * @return $this
     */
    public function mutableContent($mutableContent) : self
    {
        $this->mutableContent = $mutableContent;

        return $this;
    }

    /**
     * @param int $timeToTive
     * @return $this
     */
    public function timeToTive($timeToTive) : self
    {
        $this->timeToTive = $timeToTive;

        return $this;
    }

    /**
     * @param bool $dryRun
     * @return $this
     */
    public function dryRun($dryRun) : self
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    /**
     * @param string $restrictedPackageName
     * @return $this
     */
    public function restrictedPackageName($restrictedPackageName) : self
    {
        $this->restrictedPackageName = $restrictedPackageName;

        return $this;
    }

    public function formatData() : string
    {
        $payload = [
            'priority' => $this->getAttribute('priority'),
        ];

        if (is_array($this->to)) {
            $payload['registration_ids'] = $this->to;
        }
        elseif (!empty($this->to) ) {
            $payload['to'] = $this->to;
        }

        if (isset($this->data) && count($this->data)) {
            $payload['data'] = $this->data;
        }

        if (isset($this->notification) && count($this->notification)) {
            $payload['notification'] = $this->notification;
        }

        if (isset($this->condition) && ! empty($this->condition)) {
            $payload['condition'] = $this->condition;
        }

        if (isset($this->collapseKey) && ! empty($this->collapseKey)) {
            $payload['collapse_key'] = $this->collapseKey;
        }

        if (isset($this->contentAvailable)) {
            $payload['content_available'] = $this->contentAvailable;
        }

        if (isset($this->mutableContent)) {
            $payload['mutable_content'] = $this->mutableContent;
        }

        if (isset($this->timeToTive)) {
            $payload['time_to_live'] = $this->timeToTive;
        }

        if (isset($this->dryRun)) {
            $payload['dry_run'] = $this->dryRun;
        }

        if (isset($this->restrictedPackageName) && ! empty($this->restrictedPackageName)) {
            $payload['restricted_package_name'] = $this->restrictedPackageName;
        }

        return \GuzzleHttp\json_encode($payload);
    }

    /**
     * @return string
     */
    private function getApiKey() : string
    {
        return config('services.fcm.key');
    }
}
