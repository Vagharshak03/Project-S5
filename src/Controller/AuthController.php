<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/register', name:'register', methods:['GET','POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $fullname = $data['fullname'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            $repo = $em->getRepository(User::class);
            $existing = $repo->findOneBy(['email'=>$email]);
            if ($existing) {
                return $this->json(['success'=>false,'message'=>'Email already registered']);
            }

            $user = new User();
            $user->setFullname($fullname);
            $user->setEmail($email);
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setCreatedAt(new \DateTime());

            $em->persist($user);
            $em->flush();

            return $this->json(['success'=>true,'message'=>'Account created successfully!']);
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/login', name:'login', methods:['GET','POST'])]
    public function login(Request $request, EntityManagerInterface $em, SessionInterface $session, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            $repo = $em->getRepository(User::class);
            $user = $repo->findOneBy(['email'=>$email]);

            if (!$user) return $this->json(['success'=>false,'message'=>'User not found']);

            if ($passwordHasher->isPasswordValid($user, $password)) {
                // store in session
                $session->set('user_id', $user->getId());
                $session->set('user_fullname', $user->getFullname());
                $session->set('user_email', $user->getEmail());

                return $this->json(['success'=>true,'message'=>'Login successful','user'=>$user->getFullname()]);
            }

            return $this->json(['success'=>false,'message'=>'Invalid password']);
        }

        return $this->render('auth/login.html.twig');
    }

    #[Route('/logout', name:'logout')]
    public function logout(SessionInterface $session)
    {
        $session->clear();
        return $this->redirect('/');
    }
}
