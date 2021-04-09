<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Class AuthenticationService allows controllers to access the repository in a safe manner
 * @package App\Services
 */
class AuthenticationService{

    public function __construct(
        protected UserRepository $userRepository
    ){

    }


    /**
     * Allows a user to register their account on the system
     * @param array $data
     * @return JsonResponse
     */
    public function register(array $data): JsonResponse
    {
        if($this->userRepository->user($data['cellphone'])){
            return response()->json(['status' => false, 'message' => 'Hi, it appears there is already a user registered with that cellphone number in the system']);
        }

        return $this->userRepository->save('user', $data);
    }

    /**
     * Allows a user to login into the system
     * @param array $data
     * @return JsonResponse
     */
    public function login(array $data): JsonResponse
    {
        //First get a user from  the database this will allow us to hash the automatically generated passwords nicely as the system does not do so on creation due to hash being one way
        if($user = $this->userRepository->user($data['cellphone'])){
            //Validate whether submitted password is the same as the one stored in the database then update the model to hash the password for authentication purposes
            if($user->password === $data['password']){
                $user->update(['password' => Hash::make($data['password'])]);

                if(Auth::attempt($data)){
                    return response()->json(['status' => true, 'message' => 'Hi, we have successfully logged you in please wait while we redirect you to your dashboard', 'url' => route('dashboard')]);
                }
            }
            if(Auth::attempt($data)){
                return response()->json(['status' => true, 'message' => 'Hi, we have successfully logged you in please wait while we redirect you to your dashboard', 'url' => route('dashboard')]);
            }

            return response()->json(['status' => false, 'message' => 'Hi, it appears you have entered incorrect login details please try again']);

        }

        return response()->json(['status' => false, 'message' => 'Hi, we could not find your user account']);
    }


}
