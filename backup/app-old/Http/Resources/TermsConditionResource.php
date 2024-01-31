<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TermsConditionResource extends JsonResource
{
    private $_token = '';

    public function __construct($resource)
    {

        parent::__construct($resource);
//        if(empty($_token)) {
//            $this->_token = request()->bearerToken();
//        }
//         else {
//             $this->_token = $_token;
//         }
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $content="By using the JoeyCo website (“JoeyCo Site“) and its related JoeyCo electronic applications and services ('Services'), you hereby agree to these legally-binding terms and conditions (the 'Terms and Conditions'). Do not access or use the JoeyCo Site if you are unwilling or unable to be bound by them.
        These Terms and Conditions constitute the entire agreement between you ('you' or 'User') and JoeyCo Inc. and its affiliates, subsidiaries, partners, officers, directors, agents and employees (collectively, 'JoeyCo') pertaining to your use of the JoeyCo Site.
        For information on how user information is collected, used and disclosed by JoeyCo in connection with your use of the JoeyCo Site, please review our Privacy Policy.
        JoeyCo reserves the right to suspend or terminate your access to the JoeyCo Site, at any time for convenience, or for any other reason, including without limitation if JoeyCo has determined in its sole discretion that the use of the JoeyCo Site was in breach of these Terms and Conditions.
        JoeyCo reserves the right, in its sole discretion to add to, remove from, modify or otherwise change any part of these Terms and Conditions, in whole or in part at any time. Except as expressly contemplated herein, changes will be effective when notice of such is posted on the JoeyCo Site. Please check regularly for updates.
        If any changes made in accordance with the above are not acceptable to you, you must discontinue your use of the JoeyCo Site immediately. Your continued use of the JoeyCo Site after any such changes are posted will constitute acceptance of them.
        You hereby agree to indemnify and hold harmless JoeyCo from any claims or damages (including without limitation reasonable legal fees) arising out of your breach of these Terms and Conditions, the documents they incorporate by reference, or your violation of any law or the rights of a third party.";

        return [
            'content' => $content
        ];
    }
}
