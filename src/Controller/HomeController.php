<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\FranceApiService;

class HomeController extends AbstractController
{

    private $session;
    private $fr;
    private $userRepository;

    public function __construct(
        SessionInterface $session,
        FranceApiService $fr,
        UserRepository $UserRepository)
    {
        $this->session = $session;
        $this->fr = $fr;
        $this->userRepository = $UserRepository;
    }

    /**
    * @Route("/", name="home")
    */
    public function home()
    {
        return $this->render('home.html.twig');
    }

    /**
    * @Route("/personal-home-page", name="personal")
    */
    public function personal()
    {
        return $this->render('personal.html.twig');
    }

    /**
    * @Route("/login", name="login")
    */
    public function login()
    {
        return $this->redirect($this->fr->buildAuthorizeUrl());
    }

    /**
    * @Route("/callback", name="callback")
    */
    public function callback()
    {
        $token = $this->fr->authorizeUser();

        if (!$token) {
            return $this->redirectToRoute('home');
        }

        $email = $token->email;
        $user = $this->userRepository->findOneByEmail($email);

        if (! $user) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($email);
            $user->setToken($token);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }

        // Manually authenticate the user
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));

        return $this->redirectToRoute('home');
    }

    /**
    * @Route("/logout", name="logout")
    */
    public function logout()
    {
        
        return $this->redirect($this->fr->logoutURL());

    }
}