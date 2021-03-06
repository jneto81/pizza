<?php

namespace Pizza\Controller;

use Pizza\Entity\User;
use Psr\Log\LoggerInterface;
use Saxulum\DoctrineOrmManagerRegistry\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validator;

abstract class AbstractController
{
    /**
     * @var \Pimple
     */
    protected $container;

    /**
     * @param \Pimple $container
     */
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Twig_Environment
     */
    protected function getTwig()
    {
        return $this->container['twig'];
    }

    /**
     * @return UrlGenerator
     */
    protected function getUrlGenerator()
    {
        return $this->container['url_generator'];
    }

    /**
     * @return Session
     */
    protected function getSession()
    {
        return $this->container['session'];
    }

    /**
     * @return Validator
     */
    protected function getValidator()
    {
        return $this->container['validator'];
    }

    /**
     * @return FormFactory
     */
    protected function getFormFactory()
    {
        return $this->container['form.factory'];
    }

    /**
     * @return HttpCache
     */
    protected function getHttpCache()
    {
        return $this->container['http_cache'];
    }

    /**
     * @return SecurityContext
     */
    protected function getSecurity()
    {
        return $this->container['security'];
    }

    /**
     * @return \Swift_Mailer
     */
    protected function getMailer()
    {
        return $this->container['mailer'];
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->container['monolog'];
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->container['translator'];
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->container['doctrine'];
    }

    /**
     * @param  string               $type
     * @param  null                 $data
     * @param  array                $options
     * @param  FormBuilderInterface $parent
     * @return Form
     */
    protected function createForm($type = 'form', $data = null, array $options = array(), FormBuilderInterface $parent = null)
    {
        return $this->getFormFactory()->createBuilder($type, $data, $options, $parent)->getForm();
    }

    /**
     * @param $view
     * @param  array  $parameters
     * @return string
     */
    protected function renderView($view, array $parameters = array())
    {
        return $this->getTwig()->render($view, $parameters);
    }

    /**
     * @return User|Null|string
     */
    protected function getUser()
    {
        if (is_null($this->getSecurity()->getToken())) {
            return null;
        }

        $user = $this->getSecurity()->getToken()->getUser();

        if ($user instanceof User) {
            $user = $this->getDoctrine()->getManager()->getRepository(get_class($user))->find($user->getId());
        }

        return $user;
    }
}
