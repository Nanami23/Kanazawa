<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\LoginAuthenticator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginAuthenticator $authenticator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if(!empty($form['recaptcha-response'])){
            dump($form);
            $url = 'https://www.google.com/recaptcha/api/siteverify?secret=6LdXo5gaAAAAAAlw1cNLKcsIxOkfrq9xu8IGvWVB&response='.$form['recaptcha-response'];

            if (function_exists('curl_version')){
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($curl, CURLOPT_TIMEOUT, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($curl);
            } else {
                $response= file_get_contents($url);
            }

            if (!empty($response) || !is_null($response)){
                $data = json_decode($response);
                if ($data->success){
                    if ($form->isSubmitted() && $form->isValid()) {
                        // donner le role joueur
                        $user->setRoles(["ROLE_JOUEUR"]);

                        // upload file
                        if ($form['avatar']->getData()!=null) {
                            $file = $form['avatar']->getData();
                            $file->move($this->getParameter('avatar_direct'), $file->getClientOriginalName());
                            $user->setAvatar($file->getClientOriginalName());
                        }

                        // encode the plain password
                        $user->setPassword(
                            $passwordEncoder->encodePassword(
                                $user,
                                $form->get('plainPassword')->getData()
                            )
                        );

                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($user);
                        $entityManager->flush();

                        // generate a signed url and email it to the user
                        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                            (new TemplatedEmail())
                                ->from(new Address('contact@beyondmemories.fr', 'Kanazawa | Beyond Memories'))
                                ->to($user->getEmail())
                                ->subject('Veuillez confirmer votre adresse e-mail')
                                ->htmlTemplate('registration/confirmation_email.html.twig')
                        );
                        // do anything else you need here, like send an email

                        return $guardHandler->authenticateUserAndHandleSuccess(
                            $user,
                            $request,
                            $authenticator,
                            'main' // firewall name in security.yaml
                        );
                    }
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('user_profil');
    }
}
