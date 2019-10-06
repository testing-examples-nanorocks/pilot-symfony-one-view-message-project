<?php

namespace App\Controller;

use App\Entity\Message;
use Mailgun\HttpClient\HttpClientConfigurator;
use Mailgun\Mailgun;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Asserts;
use Symfony\Component\Validator\Validation;


class MessageController extends AbstractController
{
    /**
     * @Route("/", name="message", methods={"GET", "POST"})
     */
    public function index(Request $request)
    {

        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }

    /**
     * @Route("/store", name="store_message", methods={"POST"})
     */
    public function store(Request $request, ValidatorInterface $validator)
    {
        $params = $request->request->all();

        $validToken = $this->isCsrfTokenValid('_token', $params['token']);
        if (!$validToken) {
            return $this->render('message/index.html.twig', [
                'errors' => [
                    'Token' => 'Invalid csrf token!!!'
                ],
            ]);
        }

        if (empty($params['email']) || empty($params['message'])) {
            return $this->render('message/index.html.twig', [
                'errors' => [
                    'Email and Message are required!!!'
                ],
            ]);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $msg = new Message();
        $msg->setHash(uniqid(md5($params['email'])));
        $msg->setMessage($params['message']);

        $entityManager->persist($msg);
        $entityManager->flush();

        $link = 'localhost:8000' . '/view/' . $msg->getHash();

        $httpConf = new HttpClientConfigurator();
        $httpConf->setApiKey('153793c2e7a0cfda1caf92ff73d583b3-af6c0cec-0942925e');
        $mgClient = new Mailgun($httpConf);
        $domain = "sandbox3cc4605bb01e4905ba3b2d9bee7dbdf7.mailgun.org";


        $result = $mgClient->messages()->send("$domain",
            [
                'from' => 'One View Message <postmaster@sandbox3cc4605bb01e4905ba3b2d9bee7dbdf7.mailgun.org>',
                'to' => 'Andrej Nankov <andrejnankov@gmail.com>',
                'subject' => 'New Message from One View Message',
                'html' => '<p style="text-decoration: underline;">' . $link . "</p>"
            ]);


        return $this->render('message/index.html.twig', [
            'errors' => [
                'Message success send!!!'
            ],
        ]);
    }

    /**
     * @Route("/view/{hash}", name="view_message", methods={"GET"})
     */
    public function view(Request $request, $hash)
    {
        $msg = new Message();
        $msg->setHash($hash);

        $entityManager = $this->getDoctrine()->getRepository(Message::class)->findHash($hash);
        $message = null;
        if($entityManager != null)
        {
            $message = trim($entityManager->getMessage());

            $deleteMessage = $this->getDoctrine()->
            getRepository(Message::class)->
            findAndDelete($entityManager->getHash());
        }

        return $this->render('message/show.html.twig', [
            'message' => $message,
        ]);
    }
}
