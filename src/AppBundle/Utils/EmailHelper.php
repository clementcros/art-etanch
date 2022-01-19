<?php

namespace AppBundle\Utils;


use eZ\Publish\Core\Repository\Values\Content\ContentProxy;
use Symfony\Component\Templating\EngineInterface;

/**
 * Class Helper.
 */
class EmailHelper
{

    /**
     * @var EngineInterface
     */
    private $template;
    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var SearchHelper
     */
    private $searchHelper;

    public function __construct(
        EngineInterface $templating,
        \Swift_Mailer $mailer,
        SearchHelper $searchHelper
    ) {
        $this->template = $templating;
        $this->mailer = $mailer;
        $this->searchHelper = $searchHelper;
    }

    public function sendEmail(array $contactForm, $origin) : bool
    {
        $this->sendEmailCustomer($contactForm, $origin);

        $headers = "From: contact@art-etanch.com\r\n";
        $headers .= "Reply-To: fred.art.etanch@gmail.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        mail("fred.art.etanch@gmail.com", 'art-etanch.com nouvelle demande de contact', $this->template->render(
            'emails/contact.html.twig',
            ['contactForm' => $contactForm]
        ), $headers);

        return true;
    }


    private function sendEmailCustomer($contactForm, $origin) : bool
    {
        $homePage = $this->searchHelper->locationsList(
            1,
            ['homepage'],
            [],
            1
        );

        $homePage = current($homePage);
        /* @var ContentProxy $homePageContent */
        $homePageContent = $homePage->getContent();
        $logoHeader = $origin . $homePageContent->getFieldValue('image_background')->uri;
        $logo = $origin . $homePageContent->getFieldValue('logo_homepage')->uri;

        $kitchenFamily = $this->searchHelper->locationsList(
            2,
            ['list_family_kitchen'],
            [],
            1
        );

        $kitchenFamily = current($kitchenFamily);
        /* @var ContentProxy $kitchenFamilyContent */
        $kitchenFamilyContent = $kitchenFamily->getContent();


        $message = (new \Swift_Message('Art-etanch contact [ne pas répondre]'))
            ->setFrom('send@example.com')
            ->setTo($contactForm['email'])
            ->setBody(
                $this->template->render(
                    'emails/contact_customer.html.twig',
                    [
                        'contactForm' => $contactForm,
                        'logoHeader' => $logoHeader,
                        'logo' => $logo,
                        'kitchen' => $kitchenFamilyContent->getVersionInfo()->getContentInfo()->mainLocationId,
                        'origin' => $origin
                    ]
                ),
                'text/html'
            );
        $this->mailer->send($message);

        $headers = "From: contact@art-etanch.com\r\n";
        $headers .= "Reply-To: ". $contactForm['email'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        mail($contactForm['email'], 'Contact art-etanch [ NE PAS REPONDRE ]', $this->template->render(
            'emails/contact_customer.html.twig',
            [
                'contactForm' => $contactForm,
                'logoHeader' => $logoHeader,
                'logo' => $logo,
                'kitchen' => $kitchenFamilyContent->getVersionInfo()->getContentInfo()->mainLocationId,
                'origin' => $origin
            ]
        ), $headers);

        return true;
    }
}