<?php

namespace Pizza\Controller;

use Pizza\Entity\Order;
use Pizza\Entity\OrderItem;
use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pizza\Form\OrderItemType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OrderController extends AbstractController
{
    /**
     * @param Application $app
     * @param $serviceId
     */
    public static function addRoutes(Application $app, $serviceId)
    {
        $prefix = '/{_locale}/order';
        $app->get($prefix, $serviceId . ':listAction')->bind('order_list');
        $app->get($prefix . '/create', $serviceId . ':createAction')->bind('order_create');
        $app->get($prefix . '/show/{id}', $serviceId . ':showAction')->assert('id', '\d+')->bind('order_show');
        $app->get($prefix . '/delete/{id}', $serviceId . ':deleteAction')->assert('id', '\d+')->bind('order_delete');
        $app->get($prefix . '/items/{id}', $serviceId . ':listitemAction')->assert('id', '\d+')->bind('order_item_list');
        $app->match($prefix . '/items/{id}/edit/{itemid}', $serviceId . ':edititemAction')
            ->value('itemid', null)
            ->assert('id', '\d+')
            ->assert('itemid', '\d+')
            ->bind('order_item_edit')
        ;
        $app->match($prefix . '/items/{id}/delete/{itemid}', $serviceId . ':deleteitemAction')
            ->assert('id', '\d+')
            ->assert('itemid', '\d+')
            ->bind('order_item_delete')
        ;
    }

    /**
     * @return string
     */
    public function listAction()
    {
        // get orders
        $orders = $this->getDoctrine()->getManager()->getRepository(get_class(new Order()))->findAll();

        // return the rendered template
        return $this->renderView('@Pizza/Order/list.html.twig', array('orders' => $orders));
    }

    /**
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function createAction()
    {
        // check permission
        if (!$this->getSecurity()->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException("You have not the permission to create a order!");
        }

        // create a new order
        $order = new Order();
        $order->setOrderDatetime(new \DateTime());

        // persists the order
        $this->getDoctrine()->getManager()->persist($order);
        $this->getDoctrine()->getManager()->flush();

        // redirect to the edit mask
        return new RedirectResponse($this->getUrlGenerator()->generate('order_item_list', array('id' => $order->getId())), 302);
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function showAction($id)
    {
        // get the order
        $order = $this->getDoctrine()->getManager()->getRepository(get_class(new Order()))->find($id);

        // check if order exists
        if (is_null($order)) {
            throw new NotFoundHttpException("Order with id {$id} not found!");
        }

        // return the rendered template
        return $this->renderView('@Pizza/Order/show.html.twig', array('order' => $order));
    }

    /**
     * @param $id
     * @return RedirectResponse
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function deleteAction($id)
    {
        // check permission
        if (!$this->getSecurity()->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException("You have not the permission to delete a order!");
        }

        // get the order
        $order = $this->getDoctrine()->getManager()->getRepository(get_class(new Order()))->find($id);

        // check if order exists
        if (is_null($order)) {
            throw new NotFoundHttpException("Order with id {$id} not found!");
        }

        // remove the order
        $this->getDoctrine()->getManager()->remove($order);
        $this->getDoctrine()->getManager()->flush();

        // redirect to the list
        return new RedirectResponse($this->getUrlGenerator()->generate('order_list'), 302);
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function listitemAction($id)
    {
        // get the order
        $order = $this->getDoctrine()->getManager()->getRepository(get_class(new Order()))->find($id);

        // check if order exists
        if (is_null($order)) {
            throw new NotFoundHttpException("Order with id {$id} not found!");
        }

        // return the rendered template
        return $this->renderView('@Pizza/Order/listitem.html.twig', array('order' => $order));
    }

    /**
     * @param Request $request
     * @param $id
     * @param  null                    $itemid
     * @return string|RedirectResponse
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function edititemAction(Request $request, $id, $itemid = null)
    {
        if (!is_null($itemid)) {
            // get the orderitem
            $orderItem = $this->getDoctrine()->getManager()->getRepository(get_class(new OrderItem()))->find($itemid);
            /** @var OrderItem $orderItem */

            // check if order exists
            if (is_null($orderItem)) {
                throw new NotFoundHttpException("Orderitem with id {$itemid} of order {$id} not found!");
            }

            if(!$this->getSecurity()->isGranted('ROLE_ADMIN') &&
                $orderItem->getUser()->getId() != $this->getUser()->getId()) {
                throw new AccessDeniedException("You have not the permission to edit a orderitem of another user!");
            }
        } else {
            // create a new order
            $orderItem = new OrderItem();

            // get the order
            $order = $this->getDoctrine()->getManager()->getRepository(get_class(new Order()))->find($id);
            /** @var Order $order */

            // set order
            $orderItem->setOrder($order);
        }

        // create user form
        $orderItemForm = $this->createForm(new OrderItemType(
            $this->getUser(),
            $this->getSecurity()->isGranted('ROLE_ADMIN')
        ), $orderItem);

        if ('POST' == $request->getMethod()) {
            // bind request
            $orderItemForm->bind($request);

            // check if the input is valid
            if ($orderItemForm->isValid()) {
                // persists the order
                $this->getDoctrine()->getManager()->persist($orderItem);
                $this->getDoctrine()->getManager()->flush();

                // redirect to the edit mask
                return new RedirectResponse($this->getUrlGenerator()->generate('order_item_list', array('id' => $orderItem->getOrder()->getId())), 302);
            }
        }

        // return the rendered template
        return $this->renderView('@Pizza/Order/edititem.html.twig', array
        (
            'orderitem' => $orderItem,
            'orderitemform' => $orderItemForm->createView(),
        ));
    }

    /**
     * @param $id
     * @param $itemid
     * @return RedirectResponse
     * @throws AccessDeniedException
     * @throws NotFoundHttpException
     */
    public function deleteitemAction($id, $itemid)
    {
        // get the orderitem
        $orderItem = $this->getDoctrine()->getManager()->getRepository(get_class(new OrderItem()))->find($itemid);
        /** @var OrderItem $orderItem */

        // check if order exists
        if (is_null($orderItem)) {
            throw new NotFoundHttpException("Orderitem with id {$itemid} of order {$id} not found!");
        }

        if(!$this->getSecurity()->isGranted('ROLE_ADMIN') &&
           $orderItem->getUser()->getId() != $this->getUser()->getId()) {
            throw new AccessDeniedException("You have not the permission to delete a orderitem of another user!");
        }

        // remove the orderitem
        $this->getDoctrine()->getManager()->remove($orderItem);
        $this->getDoctrine()->getManager()->flush();

        // redirect to the list
        return new RedirectResponse($this->getUrlGenerator()->generate('order_item_list', array('id' => $id)), 302);
    }
}
