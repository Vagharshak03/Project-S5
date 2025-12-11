<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserRepository $repo;
    private UserPasswordHasherInterface $hasher;
    private Request $request;

    public function __construct(
        EntityManagerInterface      $em,
        UserRepository              $repo,
        UserPasswordHasherInterface $hasher
    )
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->hasher = $hasher;
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, SessionInterface $session)
    {
        $errors = [];
        if ($request->isMethod('POST')) {

            $fullname = $request->request->get('name', '');
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');
            $confPassword = $request->request->get('confPassword', '');
            $errors = [];

            // Validation logic ...
            if (empty($fullname)) {
                $errors['fullname'] = 'Full name is required.';
            }
            if (empty($email)) {
                $errors['email'] = 'Email is required.';
            } elseif ($this->repo->findOneBy(['email' => $email])) {
                $errors['email'] = 'Email already registered.';
            }
            if (empty($password)) {
                $errors['password'] = 'Password is required.';
            }
            if($password !== $confPassword){
                $errors['confPassword'] = "Passwords do not match";
            }

            if (empty($errors)) {
                $user = new User();
                $user->setFullname($fullname);
                $user->setEmail($email);
                $hashed = $this->hasher->hashPassword($user, $password);
                $user->setPassword($hashed);

                $this->em->persist($user);
                $this->em->flush();

                $session->set('user', [
                    'id' => $user->getId(),
                    'name' => $user->getFullname(),
                    'email' => $user->getEmail(),
                ]);


                return $this->redirectToRoute('home');
            }
        }
        return $this->render('georoots/register.html.twig', [
            'errors' => $errors,
            'data' => $request->request->all(),
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, SessionInterface $session)
    {
        $errors = [];
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');

            $user = $this->repo->findOneBy(['email' => $email]);
            if (!$user) {
                $errors['email'] = 'User not found.';
            } elseif (!$this->hasher->isPasswordValid($user, $password)) {
                $errors['password'] = 'Invalid password.';
            } else {
                $session->set('user', [
                    'id' => $user->getId(),
                    'name' => $user->getFullname(),
                    'email' => $user->getEmail(),
                ]);
                return $this->redirectToRoute('home');
            }
        }

        return $this->render('georoots/login.html.twig', [
            'errors' => $errors,
            'data' => $request->request->all(),
        ]);

    }

    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session)
    {
        $session->clear();
        $session->invalidate();
        return $this->redirectToRoute('home');
    }
}
