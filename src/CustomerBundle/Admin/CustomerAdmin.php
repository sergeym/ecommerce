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

namespace Sonata\CustomerBundle\Admin;

use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\CoreBundle\Form\Type\DatePickerType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CustomerAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setTranslationDomain('SonataCustomerBundle');
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $now = new \DateTime();

        $formMapper
            ->with('customer.group.general', [
                    'class' => 'col-md-7',
                ])
                ->add('user', ModelListType::class)
                ->add('firstname')
                ->add('lastname')
                ->add('locale', LocaleType::class)
                ->add('birthDate', DatePickerType::class, [
                    'years' => range(1900, $now->format('Y')),
                    'dp_min_date' => '1-1-1900',
                    'dp_max_date' => $now->format('c'),
                ])
                ->add('birthPlace')
            ->end()
            ->with('customer.group.contact', [
                    'class' => 'col-md-5',
                ])
                ->add('email')
                ->add('phoneNumber')
                ->add('mobileNumber')
                ->add('faxNumber')
                ->add('isFake')
            ->end()
        ;
    }

    public function configureListFields(ListMapper $list)
    {
        $list
            ->addIdentifier('name', TextType::class, ['code' => '__toString'])
            ->add('user')
            ->add('email')
            ->add('createdAt')
            ->add('locale')
            ->add('isFake')
        ;
    }

    public function configureShowFields(ShowMapper $filter)
    {
        $filter
            ->with('customer.group.general')
                ->add('user', ModelListType::class)
                ->add('firstname')
                ->add('lastname')
                ->add('locale', LocaleType::class)
                ->add('birthDate')
                ->add('birthPlace')
            ->end()
            ->with('customer.group.contact')
                ->add('email', EmailType::class)
                ->add('phoneNumber')
                ->add('mobileNumber')
                ->add('faxNumber')
                ->add('isFake')
            ->end()
        ;
    }

    public function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('firstname')
            ->add('lastname')
            ->add('user')
            ->add('email')
            ->add('locale', null, [], LocaleType::class)
            ->add('isFake')
        ;
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
            $this->trans('customer.sidemenu.link_customer_edit', [], 'SonataCustomerBundle'),
            $admin->generateMenuUrl('edit', ['id' => $id])
        );

        $menu->addChild(
            $this->trans('customer.sidemenu.link_address_list', [], 'SonataCustomerBundle'),
            $admin->generateMenuUrl('sonata.customer.admin.address.list', ['id' => $id])
        );

        $menu->addChild(
            $this->trans('customer.sidemenu.link_order_list', [], 'SonataCustomerBundle'),
            $admin->generateMenuUrl('sonata.order.admin.order.list', ['id' => $id])
        );
    }
}
