<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.16
 *
 * @link       TBA
 */

namespace Application\Controller;

use Application\Form\ContactForm;

final class ContactController extends IndexController
{
    /**
     * @var ContactForm $contactForm
     */
    private $contactForm = null;

    /**
     * @param ContactForm $contactForm
     */
    public function __construct(ContactForm $contactForm = null)
    {
        parent::__construct();
        $this->contactForm = $contactForm;
    }

    /**
     * Simple contact form.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $this->getView()->setTemplate("application/contact/index");

        /**
         * @var $form ContactForm
         */
        $form = $this->contactForm;

        $form->get("email")->setLabel($this->translate("EMAIL"));
        $form->get("name")->setLabel($this->translate("NAME"))->setAttribute("placeholder", $this->translate("ENTER_NAME"));
        $form->get("subject")->setLabel($this->translate("SUBJECT"))->setAttribute("placeholder", $this->translate("ENTER_SUBJECT"));
        $form->get("captcha")->setLabel($this->translate("CAPTCHA"))->setAttribute("placeholder", $this->translate("ENTER_CAPTCHA"));
        $form->get("message")->setLabel($this->translate("MESSAGE"))->setAttribute("placeholder", $this->translate("ENTER_MESSAGE"));

        $this->getView()->form = $form;
        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                try {
                    $this->Mailing()->sendMail($this->systemSettings("general", "system_email"), '', $formData["subject"], $formData["message"], $formData["email"], $formData["name"]);
                    $this->setLayoutMessages($this->translate("CONTACT_SUCCESS"), 'success');
                } catch (\Exception $e) {
                    $this->setLayoutMessages($this->translate("CONTACT_ERROR"), 'error');
                }
            } else {
                $this->setLayoutMessages($form->getMessages(), 'error');
            }
        }
        return $this->getView();
    }
}
