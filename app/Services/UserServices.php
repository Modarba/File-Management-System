<?php
namespace App\Services;
use App\Enums\HttpStatusCode;
use App\Interfaces\UserInterface;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Request;

class UserServices
{
    protected $userRepository;
    public function __construct(UserInterface $user)
    {
        $this->userRepository=$user;
    }
    public function getAllFileForUser()
    {
   return $this->userRepository->getAllFileForUser();
    }
    public function rootWithFolderBelongsTo()
    {
        return $this->userRepository->rootWithFolderBelongsTo();
    }
    public function childHasManyFolder()
    {
        return $this->userRepository->childWithFolderBelongsTo();
    }
    public function childRecursive()
    {
        return $this->userRepository->childRecursive();
    }

}
