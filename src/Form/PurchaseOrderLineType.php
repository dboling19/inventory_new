<?php

namespace App\Form;

use App\Entity\Item;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderLine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseOrderLineType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('po_status')
      ->add('qty_ordered')
      ->add('qty_received')
      ->add('qty_rejected')
      ->add('qty_vouchered')
      ->add('item_cost')
      ->add('po_due_date')
      ->add('po_received_date')
      ->add('po_received')
      ->add('po_paid')
      ->add('item_quantity')
      ->add('po', EntityType::class, [
        'class' => PurchaseOrder::class,
        'choice_label' => 'id',
      ])
      ->add('item', EntityType::class, [
        'class' => Item::class,
        'choice_label' => 'id',
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => PurchaseOrderLine::class,
    ]);
  }
}
