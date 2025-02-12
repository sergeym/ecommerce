<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\OrderBundle\Admin;

use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\Component\Currency\CurrencyDetectorInterface;
use Sonata\Component\Currency\CurrencyFormType;
use Sonata\Component\Invoice\InvoiceManagerInterface;
use Sonata\Component\Order\OrderManagerInterface;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Sonata\OrderBundle\Form\Type\OrderStatusType;
use Sonata\PaymentBundle\Form\Type\PaymentTransactionStatusType;
use Sonata\ProductBundle\Form\Type\ProductDeliveryStatusType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class OrderAdmin extends AbstractAdmin
{
    /**
     * @var CurrencyDetectorInterface
     */
    protected $currencyDetector;

    /**
     * @var InvoiceManagerInterface
     */
    protected $invoiceManager;

    /**
     * @var OrderManagerInterface
     */
    protected $orderManager;

    public function setCurrencyDetector(CurrencyDetectorInterface $currencyDetector)
    {
        $this->currencyDetector = $currencyDetector;
    }

    public function setInvoiceManager(InvoiceManagerInterface $invoiceManager)
    {
        $this->invoiceManager = $invoiceManager;
    }

    public function setOrderManager(OrderManagerInterface $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->parentAssociationMapping = 'customer';
        $this->setTranslationDomain('SonataOrderBundle');
    }

    /**
     * {@inheritdoc}
     */
    public function configureFormFields(FormMapper $formMapper)
    {
        // define group zoning
        $formMapper
             ->with($this->trans('order.form.group_main_label'), ['class' => 'col-md-12'])->end()
             ->with($this->trans('order.form.group_billing_label'), ['class' => 'col-md-6'])->end()
             ->with($this->trans('order.form.group_shipping_label'), ['class' => 'col-md-6'])->end()
        ;

        if (!$this->isChild()) {
            $formMapper
                ->with($this->trans('order.form.group_main_label', [], 'SonataOrderBundle'))
                    ->add('customer', ModelListType::class)
                ->end()
            ;
        }

        $formMapper
            ->with($this->trans('order.form.group_main_label', [], 'SonataOrderBundle'))
                ->add('currency', CurrencyFormType::class)
                ->add('locale', LocaleType::class)
                ->add('status', OrderStatusType::class, ['translation_domain' => 'SonataOrderBundle'])
                ->add('paymentStatus', PaymentTransactionStatusType::class, ['translation_domain' => 'SonataPaymentBundle'])
                ->add('deliveryStatus', ProductDeliveryStatusType::class, ['translation_domain' => 'SonataDeliveryBundle'])
                ->add('validatedAt', DatePickerType::class, ['dp_side_by_side' => true])
            ->end()
            ->with($this->trans('order.form.group_billing_label', [], 'SonataOrderBundle'), ['collapsed' => true])
                ->add('billingName')
                ->add('billingAddress1')
                ->add('billingAddress2')
                ->add('billingAddress3')
                ->add('billingCity')
                ->add('billingPostcode')
                ->add('billingCountryCode', CountryType::class)
                ->add('billingFax')
                ->add('billingEmail')
                ->add('billingMobile')
            ->end()
            ->with($this->trans('order.form.group_shipping_label', [], 'SonataOrderBundle'), ['collapsed' => true])
                ->add('shippingName')
                ->add('shippingAddress1')
                ->add('shippingAddress2')
                ->add('shippingAddress3')
                ->add('shippingCity')
                ->add('shippingPostcode')
                ->add('shippingCountryCode', CountryType::class)
                ->add('shippingFax')
                ->add('shippingEmail')
                ->add('shippingMobile')
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureListFields(ListMapper $list)
    {
        $currency = $this->currencyDetector->getCurrency()->getLabel();

        $list
            ->addIdentifier('reference');

        if (!$list->getAdmin()->isChild()) {
            $list->addIdentifier('customer');
        }

        $list
            ->add('status', TextType::class, [
                'template' => 'SonataOrderBundle:OrderAdmin:list_status.html.twig',
            ])
            ->add('deliveryStatus', TextType::class, [
                'template' => 'SonataOrderBundle:OrderAdmin:list_delivery_status.html.twig',
            ])
            ->add('paymentStatus', TextType::class, [
                'template' => 'SonataOrderBundle:OrderAdmin:list_payment_status.html.twig',
            ])
            ->add('validatedAt')
            ->add('totalInc', CurrencyFormType::class, ['currency' => $currency])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('reference')
        ;

        if (!$this->isChild()) {
            $filter->add('customer.lastname');
        }

        $filter
            ->add('status', null, [], OrderStatusType::class, ['translation_domain' => $this->translationDomain])
            ->add('deliveryStatus', null, [], ProductDeliveryStatusType::class, ['translation_domain' => 'SonataDeliveryBundle'])
            ->add('paymentStatus', null, [], PaymentTransactionStatusType::class, ['translation_domain' => 'SonataPaymentBundle'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('create');
        $collection->add('generateInvoice');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureSideMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        if (!$childAdmin && !\in_array($action, ['edit'], true)) {
            return;
        }

        $admin = $this->isChild() ? $this->getParent() : $this;

        $id = $admin->getRequest()->get('id');

        $menu->addChild(
            $this->trans('sonata.order.sidemenu.link_order_edit', [], 'SonataOrderBundle'),
            ['uri' => $admin->generateUrl('edit', ['id' => $id])]
        );

        $menu->addChild(
            $this->trans('sonata.order.sidemenu.link_order_elements_list', [], 'SonataOrderBundle'),
            ['uri' => $admin->generateUrl('sonata.order.admin.order_element.list', ['id' => $id])]
        );

        $order = $this->orderManager->findOneBy(['id' => $id]);
        $invoice = $this->invoiceManager->findOneBy(['reference' => $order->getReference()]);

        if (null === $invoice) {
            $menu->addChild(
                $this->trans('sonata.order.sidemenu.link_oRDER_TO_INVOICE_generate', [], 'SonataOrderBundle'),
                ['uri' => $admin->generateUrl('generateInvoice', ['id' => $id])]
            );
        } else {
            $menu->addChild(
                $this->trans('sonata.order.sidemenu.link_oRDER_TO_INVOICE_edit', [], 'SonataOrderBundle'),
                ['uri' => $this->getConfigurationPool()->getAdminByAdminCode('sonata.invoice.admin.invoice')->generateUrl('edit', ['id' => $invoice->getId()])]
            );
        }
    }
}
