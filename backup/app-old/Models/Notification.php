<?php

namespace App\Models;

use App\Classes\PushNotification;
use App\Events\NewNotificationAdded;
use App\Models\Interfaces\NotificationInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class Notification extends Model implements NotificationInterface
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'notification',
        'notification_type',
        'notification_data',
        'payload',
        'is_silent',
        'is_read'

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'notification_data' => 'array',
        'payload'           => 'array',
        'is_read'           => 'boolean',
    ];

    /**
     * Container to hold attributes
     *
     * @var string
     */
    private $notificationModelData;

    /**
     * Notification channel
     * Allows: push, email, sms
     *
     * @var array
     */
    private $notifications = [];

    /**
     * @var User
     */
    private $owner;

    /**
     * @var boolean
     */
    private $isSilent;

    /**
     * @var boolean
     */
    private $isRead;


    /**
     * @var string
     */
    private $sound;


    /**
     * @var boolean
     */
    private $userType;

    /*
     * Broadcast the notification
     * */
    public static function broadCastNotification($text, array $notification_data=[])
    {
        $notification = (new self)->createNotification([
            'notification'      => $text,
            'notification_type' => array_key_exists('type', $notification_data) ? $notification_data['type'] : '',
            'notification_data' => array_diff_key($notification_data, ['type' => '']), // InApp payload
        ]);

        return $notification;
    }

    public function createNotification(array $data)
    {
        return $this->setNotificationModelData($data);
    }

    /**
     * Make this notification non-actionable by default.
     *
     * @return Notification
     */
    public function notActionable() : Notification
    {
        $payload = $this->getNotificationModelData();

        $payload['notification_data']['actionable'] = false;

        return $this->setNotificationModelData($payload);
    }

    public function makeSilent()
    {
        $this->isSilent = true;

        return $this;
    }

    public function markAsRead()
    {
        $this->isRead = true;

        return $this;
    }

    public function changeSound($sound = 'default')
    {
        $this->sound = $sound;

        return $this;
    }

    public function customPayload(array $payload)
    {
        $previousPayload = $this->getNotificationModelData();

        // Adjust payload data so that notification data can be saved appropriately
        $payload['data_title']    = $previousPayload['notification'];
        $payload['data_body']  = $previousPayload['notification_data']['body'] ?? '';

        if ( isset($payload['click_action']) ) {
            $payload['data_click_action'] = $payload['click_action'];
        }

        unset($payload['click_action']);

        $finalPayload = [
            'payload' => [
                'notification' => [
                    'title' => $payload['data_title'],
                    'body'  => $payload['data_body'],
                ],
                'data' => $payload
            ]
        ];

        if ( isset($payload['data_click_action']) ) {
            $finalPayload['payload']['notification']['click_action'] = $payload['data_click_action'];
        }

        $payload = array_merge($previousPayload, $finalPayload);

        return $this->setNotificationModelData($payload);
    }

    public function additionalColumn(array $associativeArray)
    {
        foreach ($associativeArray as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Do all the processing for notification
     *
     * @return Notification
     */
    public function build()
    {
        if ( is_null($this->getOwner()) ) {
            throw new Exception('Please set owner first before proceeding.');
        }

        $attributes = $this->buildAttributes() + $this->toArray();

        if ($this->isSilent()) {
            unset(
                $attributes['payload']['notification'],
                $attributes['payload']['data']['data_message'],
                $attributes['payload']['data']['data_title']
            );
        }
       // dd($attributes);
        $notification = $this->getOwner()->notifications()->create( $attributes );

        event(new NewNotificationAdded($notification, $this->getOwner()));

        $notification->throwNotifications($this->getNotifications());

        return $notification;
    }

    public function throwNotifications(array $notificationsVia)
    {
        if ( in_array('push', $notificationsVia) ) {
            $payload             = collect($this->payload['data']);
            $notificationPayload = $this->isSilent() ? null : collect($this->payload['notification']);

            // Chnaged to proxy push, it seems to be more feasilble way to handle push and badges.
            // NOTE: Badges will be handled by firebase itself, no need define here.
            /*FirebaseHandler::update('/notifications/'.$notification->user_id, [
                'data' => $payload->toArray(),
                'notification' => $this->isSilent() ? null : $notificationPayload->toArray() + [
                    'sound' => 'default',
                ],
            ]);*/

            $pushPayload = [
                'data' => $payload->toArray(),
                'content' => $this->isSilent() ? null : [
                    'title'        => $this->notification,
                    'message'      => $notificationPayload->get('message', $this->notification),
                    'click_action' => $notificationPayload->get('click_action', ''),
                    'sound'        => 'default',
                    'badge'        => $this->getOwner()->getMetaDefault('unread_notifications', 0),
                ],
            ];

            $pushPayload = array_filter($pushPayload, function ($a) {return null !== $a;});

            PushNotification::sendToUserConditionally($this->user_id, $pushPayload);
        }
    }

    public function disableThrowing()
    {
        return $this->throwNotificationsVia([]);
    }

    /*
     *
     * Setter and Getters
     * @param $value
     * */
    public function setUserType($value) :self
    {
        $this->userType  = $value;

        return $this;
    }

    private function getUserType() : ?bool
    {
        return $this->userType;
    }

    private function getNotifications() : array
    {
        return $this->notifications;
    }

    public function setNotificationModelData(array $data)
    {
        $this->notificationModelData = $data;

        return $this;
    }

    public function getNotificationModelData() : array
    {
        return (array)$this->notificationModelData;
    }

    public function getSound() : ?string
    {
        return $this->sound ?? 'default';
    }

    public function setOwner(User $user)
    {
        $this->owner = $user;

        return $this;
    }

    public function setUsers($users)
    {
        $this->owner = $users;

        return $this;
    }

    public function getOwner()
    {
        return $this->owner ?: $this->user;
    }

    public function getIsSilent() : ?bool
    {
        return $this->isSilent;
    }

    public function getIsRead() : ?bool
    {
        return $this->isRead;
    }

    /*
     * Main Functions
     * */
    //This function is used to check which Notification type user want to send
    public function throwNotificationsVia($value)
    {
        if ( !is_array($value) ) {
            $value = [$value];
        }

        $this->notifications = $value;

        return $this;
    }


    //This function is used to send notification using \App\Classes\PushNotification
    public function send() : void
    {
        $user           = $this->getOwner();
        $pushPayload    = array_filter($this->generatePayload($user), static function ($a) {return null !== $a;});
        $notifications  = $this->getNotifications();

        if(count($notifications) > 1) {

            foreach ($notifications as $notification) {

                $notificationMethod = 'throw' . ucfirst(strtolower($notification)) . 'Notification';

                if ( method_exists($this, $notificationMethod) ) {
                    $this->$notificationMethod($user, $pushPayload);
                }
            }
        }
        elseif(count($notifications) === 1) {

            $notificationMethod = 'throw' . ucfirst(strtolower($notifications[0])) . 'Notification';

            if ( method_exists($this, $notificationMethod) ) {
                $this->$notificationMethod($pushPayload, $user);
            }
        }
    }


    //This function is uses to save data in the database
    public function saveData()
    {
        if ( null === $this->getOwner() ) {
            throw new Exception('Please set owner first before proceeding.');
        }

        $attributes = $this->buildAttributes();

        $this->getOwner()->notifications()->create( $attributes );
        $this->getOwner()->setUnreadNotificationCount();

        return $this;
    }

    //This function is uses to save data in the database
    public function saveForUsers()
    {
        $attributes = $this->buildAttributes();
        $userIds    = $this->getOwner() ?? [];

        $title              = $attributes['notification'];
        $notification_type  = $attributes['notification_type'];
        $notification_data  = isset($attributes['notification_data']) ? json_encode($attributes['notification_data'])  : '';
        $payload            = isset($attributes['payload']) ? json_encode($attributes['payload'])  : '';
        $dateTime           = now()->toDateTimeString();
        $isRead             = null === $this->getIsRead() ? 0 : 1;

        $query = "INSERT INTO `notifications` (
                  user_id,
                  user_type,
                  notification,
                  notification_type,
                  notification_data,
                  payload,
                  is_read,
                  created_at
                )
                SELECT
                  id, '', ?, ?, ?, ?, ?, ?
                FROM
                  users
                WHERE is_active = ?
                  AND deleted_at IS NULL";

        if(!empty($userIds) && is_array($userIds)) {
            $userIds     = implode(',',  $userIds);
            $query.=" AND id IN ($userIds)";
        }
        /*else {
            $roles = implode(',', [User::ROLE_USER]);
            $query.=" AND role_id IN ($roles) ";
        }*/

        DB::select($query, [$title, $notification_type, $notification_data, $payload, $isRead, $dateTime, 1]);

        return $this;
    }

    /*
     * Protected Functions
     * */
    protected function buildAttributes() : array
    {
        $attributes = $this->getNotificationModelData();

        // Fill type of if found empty string
        if ( '' === $attributes['notification_type'] && isset($attributes['payload']['notification']['click_action']) ) {
            $attributes['notification_type'] = $attributes['payload']['notification']['click_action'];
        }

        if($this->getUserType()) {
            $attributes['user_type'] = $this->getUserType();
        }

        if($this->getIsRead()) {
            $attributes['is_read'] = $this->getIsRead();
        }

        return $attributes;
    }

    protected function generatePayload($user) : array
    {
        $attributes = $this->buildAttributes();

        if ($this->getIsSilent()) {
            unset($attributes['payload']['data']['data_body'], $attributes['payload']['data']['data_title']);
        }

        $pushPayload = [
            'data' => $attributes['payload']['data'],
            'content' => $this->getIsSilent() ? null : [
                'title'        => $attributes['payload']['notification']['title'],
                'body'         => $attributes['payload']['notification']['body'],
                'click_action' => $attributes['payload']['notification']['click_action'],
                'sound'        => $this->getSound(),
                'badge'        => null === $user || is_array($user) ? 0 : $user->getMetaDefault('unread_notifications', 0),
            ],
        ];

        return $pushPayload;
    }

    protected function throwTopicNotification($pushPayload, $user) : void
    {
        PushNotification::sendToUserConditionally($user->id, $pushPayload);
    }

    protected function throwTokenNotification($pushPayload, $user) : void
    {
        $user->devices->map(static function ($record) use ($pushPayload) {

            PushNotification::sendToDevice($record->device_token, $pushPayload);

        });

    }

    protected function throwBroadcastNotification($pushPayload, $users) : void
    {
        if(is_array($users)) {
            $userTokens = $this->getDeviceTokenFromUserIds($users);
            PushNotification::broadCastToUsers($pushPayload, $userTokens);
        }
        else {
            PushNotification::broadCastToEveryone($pushPayload);
        }
    }

    private function getDeviceTokenFromUserIds($userIds = []) : array
    {
        if(empty($userIds)) {
            return [];
        }

        $devices = UserDevice::whereIn('user_id', $userIds)->whereHas('user', static function($q) {
            $q->where('is_notification', 1)->active();
        })->get();

        foreach ($devices as $device) {
            $recipients[$device->device_type][] = $device->device_token;
        }

        return $recipients ?? [];
    }

    public function isSilent()
    {
        return array_key_exists('is_silent', $this->attributes) ? ((bool) $this->attributes['is_silent']) : false;
    }

    /*
     * @Relations
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

/*
    public function getNotificationDataAttribute() {
        return  json_decode($this->attributes['notification_data']);
    }*/

}
