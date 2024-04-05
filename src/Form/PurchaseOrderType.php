<?php

namespace App\Form;

use App\Entity\PurchaseOrder;
use App\Entity\Status;
use App\Entity\Terms;
use App\Entity\Vendor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PurchaseOrderType extends AbstractType
{
  public function __construct(
    private UrlGeneratorInterface $router,
  ) {}

  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('po_num', TextType::class, [
        'label' => 'PO Num',
        'required' => false,
      ])
      ->add('po_ship_code', TextType::class, [
        'label' => 'Ship Code',
        'required' => false,
      ])

      ->add('po_freight', TextType::class, [
        'label' => 'Freight',
        'required' => false,
      ])
      ->add('po_received', TextType::class, [
        'label' => 'Received',
        'required' => false,
      ])
      ->add('po_paid', TextType::class, [
        'label' => 'Paid',
        'required' => false,
      ])
      // single_text widget enables the browser datepicker
      ->add('po_date', DateType::class, [
        'widget' => 'single_text',
        'required' => false,
      ])
      ->add('po_total_cost', TextType::class, [
        'label' => 'Total Cost',
        'required' => false,
      ])
      ->add('po_vendor', EntityType::class, [
        'class' => Vendor::class,
        'choice_label' => 'vendor_code',
        'label' => 'Vendor',
        'placeholder' => '',
        'required' => false,
      ])
      ->add('po_status', EntityType::class, [
        'class' => Status::class,
        'choice_label' => 'status_code',
        'label' => 'Status',
        'placeholder' => '',
        'required' => false,
      ])
      ->add('po_terms', EntityType::class, [
        'class' => Terms::class,
        'choice_label' => 'terms_code',
        'label' => 'Terms',
        'placeholder' => '',
        'required' => false,
      ])
      ->add('new', SubmitType::class, [
          'label' => 'New',
          'attr' => [
            'form' => $builder->getName() . '_form',
            'formaction' => $this->router->generate('po_list'),
          ],
      ])
      ->add('search', SubmitType::class, [
        'label' => 'Search',
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('po_search'),
          'formmethod' => 'post',
        ],
      ])
      ->add('save', SubmitType::class, [
        'label' => 'Save',
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('po_save'),
        ],
      ])
      ->add('delete', SubmitType::class, [
        'label' => 'Delete',
        'disabled' => true,
        'attr' => [
          'form' => $builder->getName() . '_form',
          'formaction' => $this->router->generate('po_delete'),
        ],
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => PurchaseOrder::class,
      'attr' => ['id' => 'purchase_order_form'],
    ]);
  }
}
