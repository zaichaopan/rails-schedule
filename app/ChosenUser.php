<?php

namespace App;

class ChosenUser
{
    public $userJobPreference;

    public function setUserJobPreference(UserJobPreference $userJobPreference)
    {
        $this->userJobPreference = $userJobPreference;
        return $this;
    }

    public function user(): User
    {

        return $this->userJobPreference->user;
    }
}
