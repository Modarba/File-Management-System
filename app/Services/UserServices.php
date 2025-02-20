<?php
namespace App\Services;
use App\Interface1\UserInterface;
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
    public function addFolder(\Illuminate\Http\Request $request)
    {
        return   $this->userRepository->addFolder($request);
    }
    public function rootWithFolderBelongsTo()
    {
        return $this->userRepository->rootWithFolderBelongsTo();
    }
    public function childHasManyFolder()
    {
        return $this->userRepository->childWithFolderBelongsTo();
    }
    public function deleteFolder($id)
    {
        return $this->userRepository->deleteFolder($id);
    }
    public function childRecursive()
    {
        return $this->userRepository->childRecursive();
    }
}
