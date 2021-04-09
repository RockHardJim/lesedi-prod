<?php
namespace App\Repositories;


use App\Events\ProfileEvent;
use App\Events\RegistrationEvent;
use App\Models\User\Profile;
use App\Models\User\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class UserRepository handles creation, updating and deletion of user related data
 * @package App\Repositories
 */
class UserRepository{


    public function __construct(
        protected User $user, protected Profile $profile
    ){}


    /**
     * Allows any service to save data via the numerous services
     * @param string $endpoint
     * @param array $data
     * @return JsonResponse
     */
    public function save(string $endpoint, array $data): JsonResponse
    {
        return match(true){
            $endpoint === 'user' => $this->create_user($data),

            $endpoint === 'profile' => $this->create_profile($data)

        };
    }

    /**
     * Returns a user
     * @param string $cellphone
     * @return mixed
     */
    public function user(string $cellphone): mixed
    {
        return $this->user::where('cellphone', $cellphone)->first();
    }

    /**
     * Returns a user's profile information
     * @param string $user
     * @return mixed
     */
    public function profile(string $user): mixed
    {
        return $this->user::where('user', $user)->first()->profile;
    }
    /**
     * Adds a new user into the database
     * @param array $data
     * @return JsonResponse
     */
    private function create_user(array $data): JsonResponse
    {
        try {
            // Run DB transaction saving user details into the database while ensuring any error that happens automatically restores the database to its existing data.
            DB::transaction(function() use($data){
                $user = $this->user::create($data);
                //Dispatch an SMS event to welcome a user and send them their new password
                event(new RegistrationEvent($user));
            });
            return response()->json(['status' => true, 'message' => 'Hi we have successfully registered you please check your cellphone for your password', 'url' => route('login')]);
        }catch(\Exception $e){
            Log::error('Hi suffered an error while creating a new user '. $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Hi an error occurred while creating your new account']);
        }
    }

    /**
     * Saves a user's profile information into the database
     * @param $data
     * @return JsonResponse
     */
    private function create_profile($data): JsonResponse
    {
        try{
            DB::transaction(function() use($data){
                $profile = $this->profile::create($data);
                event(new ProfileEvent($profile));
            });
            return response()->json(['status' => true, 'message' => 'Hi we have successfully saved your profile information', 'url' => route('app')]);
        }catch(\Exception $e){
            Log::error('Hi suffered an error while creating a profile '. $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Hi an error occurred while saving your profile details']);
        }
    }
}
