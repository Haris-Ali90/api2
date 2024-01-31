<?php
namespace App\Classes;

use App\Models\Interfaces\Location;
use App\Models\Interfaces\Vehicle;
use Mail;

class JoeyLoginResponse
{
    private $email;
    private $firstName;
    private $nickname;
    private $id;
    private $lastName;
    private $about;
    private $phoneNumber;
    private $photo;
    private $publicKey;
    private $privateKey;

    /**
     * @var \JoeyCo\Vehicle
     */
    private $vehicle;

    public function startExport() {

    }

    public function endExport() {

    }

    public function setCurrentLocation( Location $location = null) {

    }

    public function setLocation(Location $location) {

    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }

    public function setNickname($nickname) {
        $this->nickname = $nickname;
    }

    public function setDisplayName($type) {

    }

    public function setOnDuty($flag) {

    }

    public function setAbout($about) {
        $this->about = $about;
    }

    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
    }

    public function setProfilePhoto($photo) {
        $this->photo = $photo;
    }

    public function setPublicKey($key) {
        $this->publicKey = $key;
    }

    public function setPrivateKey($key) {
        $this->privateKey = $key;
    }

    public function getData() {

        return [
            'id' => (int) $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phoneNumber,
            'about' => $this->about,
            'vehicle' => [
                'id' => (int) $this->vehicle->getId(),
                'name' => $this->vehicle->get_attribute('name')
            ],
            'hub_id' => $this->hubId,
            'public_key' => $this->publicKey,
            'private_key' => $this->privateKey
        ];
    }

    public function setVehicle(Vehicle $vehicle) {
        $this->vehicle = $vehicle;
    }

    public function setRating(IRating $rating) {

    }

    public function setHubId($hubId)
    {

        $this->hubId = $hubId;
    }
}
